<?php

namespace App\Services;

use App\Models\FaceImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class FaceService
{
    /**
     * Check if an uploaded image contains a human face and extract face features.
     * Returns ['detected' => bool, 'reason' => string|null, 'signature' => array|null]
     */
    public static function detectAndExtractSignature(string $filePath): array
    {
        if (!file_exists($filePath) || filesize($filePath) < 100) {
            return ['detected' => false, 'reason' => 'Image file is invalid or empty.'];
        }

        $image = self::loadImage($filePath);
        if (!$image) {
            return ['detected' => false, 'reason' => 'Unable to process image format. Please capture with camera.'];
        }

        $w = imagesx($image);
        $h = imagesy($image);

        if ($w < 100 || $h < 100) {
            imagedestroy($image);
            return ['detected' => false, 'reason' => 'Image resolution too low for face recognition.'];
        }

        // Analyze central 60% circular/oval region where face is positioned
        $cropX = (int)($w * 0.20);
        $cropY = (int)($h * 0.15);
        $cropW = (int)($w * 0.60);
        $cropH = (int)($h * 0.70);

        $faceCrop = imagecreatetruecolor(64, 64);
        imagecopyresampled($faceCrop, $image, 0, 0, $cropX, $cropY, 64, 64, $cropW, $cropH);

        // 1. Skin tone & contrast detection
        $skinPixels = 0;
        $totalPixels = 64 * 64;
        $luminances = [];

        for ($y = 0; $y < 64; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgb = imagecolorat($faceCrop, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $lum = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
                $luminances[] = $lum;

                // Skin tone heuristic in YCbCr space
                $cb = 128 - 0.168736 * $r - 0.331264 * $g + 0.5 * $b;
                $cr = 128 + 0.5 * $r - 0.418688 * $g - 0.081312 * $b;

                if ($cb >= 70 && $cb <= 140 && $cr >= 125 && $cr <= 185 && $r > $b) {
                    $skinPixels++;
                }
            }
        }

        $skinRatio = $skinPixels / $totalPixels;

        // Calculate variance/contrast in the face area
        $meanLum = array_sum($luminances) / $totalPixels;
        $variance = 0;
        foreach ($luminances as $l) {
            $variance += ($l - $meanLum) ** 2;
        }
        $stdDev = sqrt($variance / $totalPixels);

        // Flat wall/floor/blank image check
        if ($stdDev < 10) {
            imagedestroy($faceCrop);
            imagedestroy($image);
            return ['detected' => false, 'reason' => 'No human face detected! The photo appears blank or dark. Please face the camera directly.'];
        }

        if ($skinRatio < 0.05 && $stdDev < 18) {
            imagedestroy($faceCrop);
            imagedestroy($image);
            return ['detected' => false, 'reason' => 'Face not detected! Please place your face clearly inside the circle.'];
        }

        // 2. Generate normalized 16x16 feature matrix
        $norm16 = imagecreatetruecolor(16, 16);
        imagecopyresampled($norm16, $faceCrop, 0, 0, 0, 0, 16, 16, 64, 64);

        $matrix16 = [];
        for ($y = 0; $y < 16; $y++) {
            for ($x = 0; $x < 16; $x++) {
                $rgb = imagecolorat($norm16, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $matrix16[] = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
            }
        }

        // 8x8 average perceptual hash
        $norm8 = imagecreatetruecolor(8, 8);
        imagecopyresampled($norm8, $faceCrop, 0, 0, 0, 0, 8, 8, 64, 64);
        $lum8 = [];
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $rgb = imagecolorat($norm8, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $lum8[] = 0.299 * $r + 0.587 * $g + 0.114 * $b;
            }
        }
        $avg8 = array_sum($lum8) / 64;
        $hash8 = '';
        foreach ($lum8 as $l) {
            $hash8 .= ($l >= $avg8) ? '1' : '0';
        }

        imagedestroy($norm8);
        imagedestroy($norm16);
        imagedestroy($faceCrop);
        imagedestroy($image);

        return [
            'detected' => true,
            'signature' => [
                'hash' => $hash8,
                'matrix' => $matrix16,
                'mean_lum' => $meanLum,
                'skin_ratio' => $skinRatio,
            ],
        ];
    }

    /**
     * Compute similarity score (0.0 to 1.0) between two face signatures.
     */
    public static function compareSignatures(array $sig1, array $sig2): float
    {
        $h1 = $sig1['hash'];
        $h2 = $sig2['hash'];
        $dist = 0;
        for ($i = 0; $i < 64; $i++) {
            if ($h1[$i] !== $h2[$i]) {
                $dist++;
            }
        }
        $hashSim = max(0.0, 1.0 - ($dist / 36.0));

        $m1 = $sig1['matrix'];
        $m2 = $sig2['matrix'];
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < 256; $i++) {
            $dot += $m1[$i] * $m2[$i];
            $normA += $m1[$i] * $m1[$i];
            $normB += $m2[$i] * $m2[$i];
        }
        $cosineSim = ($normA > 0 && $normB > 0) ? ($dot / (sqrt($normA) * sqrt($normB))) : 0.0;

        return (0.45 * $hashSim) + (0.55 * $cosineSim);
    }

    /**
     * Verify whether a captured photo matches an employee's registered face.
     */
    public static function verifyUserFace(User $user, $photoFile): array
    {
        $tmpPath = is_string($photoFile) ? $photoFile : $photoFile->getRealPath();
        $probe = self::detectAndExtractSignature($tmpPath);

        if (!$probe['detected']) {
            return [
                'matched' => false,
                'reason' => $probe['reason'] ?? 'Face not detected in photo. Please ensure face is centered.',
            ];
        }

        $refImages = [];
        foreach ($user->faceImages as $f) {
            $p = Storage::disk('public')->path($f->path);
            if (file_exists($p)) $refImages[] = $p;
        }

        if (empty($refImages) && $user->profile_photo) {
            $p = Storage::disk('public')->path($user->profile_photo);
            if (file_exists($p)) $refImages[] = $p;
        }

        if (empty($refImages)) {
            return [
                'matched' => false,
                'reason' => "No registered AI face image found for {$user->displayName()}. Please upload face reference photo in Edit Staff Member first.",
            ];
        }

        $maxScore = 0.0;
        foreach ($refImages as $refPath) {
            $refSig = self::detectAndExtractSignature($refPath);
            if ($refSig['detected']) {
                $score = self::compareSignatures($probe['signature'], $refSig['signature']);
                if ($score > $maxScore) {
                    $maxScore = $score;
                }
            }
        }

        $threshold = 0.60; // 60% similarity threshold for mobile face angle/lighting tolerance
        if ($maxScore >= $threshold) {
            return [
                'matched' => true,
                'confidence' => round($maxScore * 100, 1),
            ];
        }

        return [
            'matched' => false,
            'reason' => "Face verification failed! Face does not match registered employee image in Edit Staff Member. (Match score: " . round($maxScore * 100, 1) . "%, required: 60%)",
            'confidence' => round($maxScore * 100, 1),
        ];
    }

    /**
     * Auto-detect which active employee matches the captured face (Manager Mode).
     */
    public static function recognizeStaffFromFace($photoFile, User $actor): array
    {
        $tmpPath = is_string($photoFile) ? $photoFile : $photoFile->getRealPath();
        $probe = self::detectAndExtractSignature($tmpPath);

        if (!$probe['detected']) {
            return [
                'recognized' => false,
                'reason' => $probe['reason'] ?? 'No face detected in camera capture. Please face the camera.',
            ];
        }

        $query = User::where('status', 'active')->has('faceImages')->with('faceImages');
        if ($actor->company_id && !$actor->isSuperAdmin()) {
            $query->where('company_id', $actor->company_id);
        }

        $staffList = $query->get();
        if ($staffList->isEmpty()) {
            $staffList = User::where('status', 'active')
                ->whereNotNull('profile_photo')
                ->where('profile_photo', '!=', '')
                ->get();
        }

        $bestUser = null;
        $highestScore = 0.0;

        foreach ($staffList as $staff) {
            $refImages = [];
            foreach ($staff->faceImages as $f) {
                $p = Storage::disk('public')->path($f->path);
                if (file_exists($p)) $refImages[] = $p;
            }
            if (empty($refImages) && $staff->profile_photo) {
                $p = Storage::disk('public')->path($staff->profile_photo);
                if (file_exists($p)) $refImages[] = $p;
            }

            foreach ($refImages as $refPath) {
                $refSig = self::detectAndExtractSignature($refPath);
                if ($refSig['detected']) {
                    $score = self::compareSignatures($probe['signature'], $refSig['signature']);
                    if ($score > $highestScore) {
                        $highestScore = $score;
                        $bestUser = $staff;
                    }
                }
            }
        }

        $threshold = 0.60;
        if ($bestUser && $highestScore >= $threshold) {
            return [
                'recognized' => true,
                'user' => $bestUser,
                'confidence' => round($highestScore * 100, 1),
            ];
        }

        return [
            'recognized' => false,
            'reason' => 'Face not recognized! No registered employee matched the captured face. Please register face in Edit Staff Member.',
            'confidence' => round($highestScore * 100, 1),
        ];
    }

    /**
     * Helper to load GD image from file path
     */
    protected static function loadImage(string $path)
    {
        $info = @getimagesize($path);
        if (!$info) return null;

        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => @imagecreatefromstring(@file_get_contents($path)),
        };
    }
}

