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

        if ($w < 60 || $h < 60) {
            imagedestroy($image);
            return ['detected' => false, 'reason' => 'Image resolution too low for face recognition.'];
        }

        // Downsample to 48x48 to find skin centroid in upper 75% of frame
        $thumbW = 48;
        $thumbH = 48;
        $thumb = imagecreatetruecolor($thumbW, $thumbH);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbW, $thumbH, $w, $h);

        $skinCount = 0;
        $sumSkinX = 0;
        $sumSkinY = 0;

        for ($ty = 0; $ty < (int)($thumbH * 0.75); $ty++) {
            for ($tx = 0; $tx < $thumbW; $tx++) {
                $rgb = imagecolorat($thumb, $tx, $ty);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $cb = 128 - 0.168736 * $r - 0.331264 * $g + 0.5 * $b;
                $cr = 128 + 0.5 * $r - 0.418688 * $g - 0.081312 * $b;

                if ($cb >= 75 && $cb <= 140 && $cr >= 128 && $cr <= 185 && $r > $b && $r > 35) {
                    $skinCount++;
                    $sumSkinX += $tx;
                    $sumSkinY += $ty;
                }
            }
        }
        imagedestroy($thumb);

        // Center on skin cluster or face upper center
        if ($skinCount > 20) {
            $centerOrigX = (int)(($sumSkinX / $skinCount) * ($w / $thumbW));
            $centerOrigY = (int)(($sumSkinY / $skinCount) * ($h / $thumbH));
        } else {
            $centerOrigX = (int)($w * 0.50);
            $centerOrigY = (int)($h * 0.38);
        }

        // Tightly crop a 1:1 SQUARE centered strictly on the face (excluding clothes/shirt)
        $boxSize = (int)(min($w, $h) * 0.60);
        $cropX = max(0, min($w - $boxSize, (int)($centerOrigX - $boxSize / 2)));
        $cropY = max(0, min($h - $boxSize, (int)($centerOrigY - $boxSize / 2)));

        $faceCrop = imagecreatetruecolor(64, 64);
        imagecopyresampled($faceCrop, $image, 0, 0, $cropX, $cropY, 64, 64, $boxSize, $boxSize);

        // 1. Human skin tone & feature contrast analysis
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

                // Human skin tone in YCbCr color space
                $cb = 128 - 0.168736 * $r - 0.331264 * $g + 0.5 * $b;
                $cr = 128 + 0.5 * $r - 0.418688 * $g - 0.081312 * $b;

                if ($cb >= 75 && $cb <= 138 && $cr >= 128 && $cr <= 182 && $r > $b && $r > 35) {
                    $skinPixels++;
                }
            }
        }

        $skinRatio = $skinPixels / $totalPixels;

        // Calculate variance/contrast in the face area
        $meanLum = array_sum($luminances) / $totalPixels;
        $variance = 0.0;
        foreach ($luminances as $l) {
            $variance += ($l - $meanLum) ** 2;
        }
        $stdDev = sqrt($variance / $totalPixels);

        // STRICT REJECTION OF NON-FACE OBJECTS (Table, Chair, Walls, Floor, Dark room)
        if ($skinRatio < 0.20) {
            imagedestroy($faceCrop);
            imagedestroy($image);
            return ['detected' => false, 'reason' => 'No human face detected! Skin tone not found. Please position your face clearly inside the circular guide.'];
        }

        if ($stdDev < 18) {
            imagedestroy($faceCrop);
            imagedestroy($image);
            return ['detected' => false, 'reason' => 'No human face detected! The photo appears blank or lacks facial features. Please face the camera directly in good lighting.'];
        }

        if ($skinRatio > 0.96 && $stdDev < 20) {
            imagedestroy($faceCrop);
            imagedestroy($image);
            return ['detected' => false, 'reason' => 'Facial features (eyes, nose, mouth) not clearly visible. Please position your face inside the circle.'];
        }

        // 2. Generate normalized 16x16 feature matrix (256 points)
        $norm16 = imagecreatetruecolor(16, 16);
        imagecopyresampled($norm16, $faceCrop, 0, 0, 0, 0, 16, 16, 64, 64);

        $matrix16 = [];
        $grid2D = [];
        $rowSums = array_fill(0, 16, 0.0);
        $colSums = array_fill(0, 16, 0.0);

        for ($y = 0; $y < 16; $y++) {
            $grid2D[$y] = [];
            for ($x = 0; $x < 16; $x++) {
                $rgb = imagecolorat($norm16, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $lumVal = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
                $matrix16[] = $lumVal;
                $grid2D[$y][$x] = $lumVal;
                $rowSums[$y] += $lumVal;
                $colSums[$x] += $lumVal;
            }
        }

        // Zero-center the 16x16 matrix (NCC normalization for lighting invariance across days)
        $mean16 = array_sum($matrix16) / 256.0;
        $centered16 = [];
        $sumSq16 = 0.0;
        for ($i = 0; $i < 256; $i++) {
            $val = $matrix16[$i] - $mean16;
            $centered16[] = $val;
            $sumSq16 += $val * $val;
        }
        $normFactor = sqrt($sumSq16);
        if ($normFactor < 1e-5) $normFactor = 1.0;
        $unitMatrix = [];
        for ($i = 0; $i < 256; $i++) {
            $unitMatrix[] = $centered16[$i] / $normFactor;
        }

        // Profile projections (horizontal & vertical facial feature lines)
        $profH = self::normalizeVector($rowSums);
        $profV = self::normalizeVector($colSums);

        // 3. Difference Hash (dHash) 8x8 (64-bit gradient direction, invariant to overall lighting)
        $norm9x8 = imagecreatetruecolor(9, 8);
        imagecopyresampled($norm9x8, $faceCrop, 0, 0, 0, 0, 9, 8, 64, 64);
        $dhash = '';
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $rgb1 = imagecolorat($norm9x8, $x, $y);
                $lum1 = 0.299 * (($rgb1 >> 16) & 0xFF) + 0.587 * (($rgb1 >> 8) & 0xFF) + 0.114 * ($rgb1 & 0xFF);

                $rgb2 = imagecolorat($norm9x8, $x + 1, $y);
                $lum2 = 0.299 * (($rgb2 >> 16) & 0xFF) + 0.587 * (($rgb2 >> 8) & 0xFF) + 0.114 * ($rgb2 & 0xFF);

                $dhash .= ($lum1 >= $lum2) ? '1' : '0';
            }
        }

        imagedestroy($norm9x8);
        imagedestroy($norm16);
        imagedestroy($faceCrop);
        imagedestroy($image);

        return [
            'detected' => true,
            'signature' => [
                'grid_2d' => $grid2D,
                'unit_matrix' => $unitMatrix,
                'prof_h' => $profH,
                'prof_v' => $profV,
                'dhash' => $dhash,
                'mean_lum' => $meanLum,
                'skin_ratio' => $skinRatio,
                'std_dev' => $stdDev,
            ],
        ];
    }

    /**
     * Compute similarity score (0.0 to 1.0) between two face signatures.
     * Uses 2D multi-shift Pearson NCC with face mask + facial profile correlation + dHash.
     */
    public static function compareSignatures(array $sig1, array $sig2): float
    {
        $grid1 = $sig1['grid_2d'] ?? null;
        $grid2 = $sig2['grid_2d'] ?? null;

        $bestPearson = 0.0;

        if (is_array($grid1) && is_array($grid2) && count($grid1) === 16 && count($grid2) === 16) {
            // Multi-shift cross correlation (25 spatial alignments: dx in [-2..2], dy in [-2..2])
            for ($dy = -2; $dy <= 2; $dy++) {
                for ($dx = -2; $dx <= 2; $dx++) {
                    $vals1 = [];
                    $vals2 = [];
                    for ($y = 0; $y < 16; $y++) {
                        $y2 = $y + $dy;
                        if ($y2 < 0 || $y2 >= 16) continue;
                        for ($x = 0; $x < 16; $x++) {
                            $x2 = $x + $dx;
                            if ($x2 < 0 || $x2 >= 16) continue;

                            // Elliptical face mask: ignore corners/background
                            $nx = ($x - 7.5) / 7.5;
                            $ny = ($y - 7.5) / 7.5;
                            if (($nx * $nx + $ny * $ny) > 1.25) continue;

                            $vals1[] = $grid1[$y][$x];
                            $vals2[] = $grid2[$y2][$x2];
                        }
                    }

                    $n = count($vals1);
                    if ($n >= 80) {
                        $mean1 = array_sum($vals1) / $n;
                        $mean2 = array_sum($vals2) / $n;
                        $cov = 0.0;
                        $var1 = 0.0;
                        $var2 = 0.0;
                        for ($k = 0; $k < $n; $k++) {
                            $d1 = $vals1[$k] - $mean1;
                            $d2 = $vals2[$k] - $mean2;
                            $cov += $d1 * $d2;
                            $var1 += $d1 * $d1;
                            $var2 += $d2 * $d2;
                        }
                        if ($var1 > 1e-4 && $var2 > 1e-4) {
                            $r = $cov / sqrt($var1 * $var2);
                            if ($r > $bestPearson) {
                                $bestPearson = $r;
                            }
                        }
                    }
                }
            }
        } else {
            // Fallback to 1D Pearson correlation if grid_2d not present
            $u1 = $sig1['unit_matrix'] ?? null;
            $u2 = $sig2['unit_matrix'] ?? null;
            if ($u1 && $u2 && count($u1) === 256 && count($u2) === 256) {
                for ($i = 0; $i < 256; $i++) {
                    $bestPearson += $u1[$i] * $u2[$i];
                }
            }
        }

        // 2. Horizontal & Vertical Facial Feature Profile Correlation
        $ph1 = $sig1['prof_h'] ?? [];
        $ph2 = $sig2['prof_h'] ?? [];
        $pv1 = $sig1['prof_v'] ?? [];
        $pv2 = $sig2['prof_v'] ?? [];
        $profSim = 0.0;
        if (!empty($ph1) && !empty($ph2) && !empty($pv1) && !empty($pv2)) {
            $simH = self::vectorDot($ph1, $ph2);
            $simV = self::vectorDot($pv1, $pv2);
            $profSim = (0.5 * max(0.0, $simH)) + (0.5 * max(0.0, $simV));
        }

        // 3. dHash Hamming Distance Similarity
        $dh1 = $sig1['dhash'] ?? '';
        $dh2 = $sig2['dhash'] ?? '';
        $dhashSim = 0.0;
        if (strlen($dh1) === 64 && strlen($dh2) === 64) {
            $dist = 0;
            for ($i = 0; $i < 64; $i++) {
                if ($dh1[$i] !== $dh2[$i]) {
                    $dist++;
                }
            }
            $dhashSim = max(0.0, 1.0 - ($dist / 32.0));
        }

        // Composite match score (0.0 to 1.0)
        $score = (0.60 * max(0.0, $bestPearson)) + (0.25 * $profSim) + (0.15 * $dhashSim);
        return (float) min(1.0, max(0.0, $score));
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
                'reason' => $probe['reason'] ?? 'Face not detected in photo. Please ensure face is centered inside the circle.',
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

        // Verification threshold: 0.38 (38%) matches same person across different days/clothes while rejecting others
        $threshold = 0.38;
        if ($maxScore >= $threshold) {
            return [
                'matched' => true,
                'confidence' => round($maxScore * 100, 1),
            ];
        }

        return [
            'matched' => false,
            'reason' => "Face verification failed! Captured face does not match registered image in Edit Staff Member. (Match score: " . round($maxScore * 100, 1) . "%, required: 38%)",
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

        // 1-to-many recognition threshold: 0.45 (45%) to balance recognition with false positives
        $threshold = 0.45;
        if ($bestUser && $highestScore >= $threshold) {
            return [
                'recognized' => true,
                'user' => $bestUser,
                'confidence' => round($highestScore * 100, 1),
            ];
        }

        return [
            'recognized' => false,
            'reason' => 'Face not recognized! No registered employee matched the captured face with sufficient confidence. (Match score: ' . round($highestScore * 100, 1) . '%, required: 45%)',
            'confidence' => round($highestScore * 100, 1),
        ];
    }

    /**
     * Normalize 1D vector to zero-mean and unit variance
     */
    protected static function normalizeVector(array $vec): array
    {
        $count = count($vec);
        if ($count === 0) return [];
        $mean = array_sum($vec) / $count;
        $centered = [];
        $sumSq = 0.0;
        foreach ($vec as $v) {
            $c = $v - $mean;
            $centered[] = $c;
            $sumSq += $c * $c;
        }
        $norm = sqrt($sumSq);
        if ($norm < 1e-5) $norm = 1.0;
        $result = [];
        foreach ($centered as $c) {
            $result[] = $c / $norm;
        }
        return $result;
    }

    /**
     * Compute dot product of two normalized vectors
     */
    protected static function vectorDot(array $v1, array $v2): float
    {
        $dot = 0.0;
        $len = min(count($v1), count($v2));
        for ($i = 0; $i < $len; $i++) {
            $dot += $v1[$i] * $v2[$i];
        }
        return $dot;
    }

    /**
     * Helper to load GD image from file path
     */
    protected static function loadImage(string $path)
    {
        $info = @getimagesize($path);
        if (!$info) {
            return false;
        }

        $mime = $info['mime'] ?? '';
        $img = null;
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $img = @imagecreatefromjpeg($path);
                break;
            case 'image/png':
                $img = @imagecreatefrompng($path);
                break;
            case 'image/webp':
                $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
                break;
            case 'image/bmp':
                $img = function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($path) : false;
                break;
            default:
                return false;
        }

        if (!$img) {
            return false;
        }

        // Auto-orient based on EXIF for JPEG/JPG (mobile front camera selfies are captured rotated)
        if (function_exists('exif_read_data') && ($mime === 'image/jpeg' || $mime === 'image/jpg')) {
            try {
                $exif = @exif_read_data($path);
                if (!empty($exif['Orientation'])) {
                    switch ((int)$exif['Orientation']) {
                        case 3:
                            $rotated = imagerotate($img, 180, 0);
                            if ($rotated) { imagedestroy($img); $img = $rotated; }
                            break;
                        case 6:
                            $rotated = imagerotate($img, -90, 0);
                            if ($rotated) { imagedestroy($img); $img = $rotated; }
                            break;
                        case 8:
                            $rotated = imagerotate($img, 90, 0);
                            if ($rotated) { imagedestroy($img); $img = $rotated; }
                            break;
                    }
                }
            } catch (\Throwable $e) {}
        }

        return $img;
    }
}
