<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaceImage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FaceImageController extends Controller
{
    /**
     * Upload one or multiple face images for staff face recognition.
     * Supports multipart/form-data files and Base64 encoded strings.
     */
    public function upload(Request $request)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found or unauthenticated. Please pass Bearer token, user_id, or employee_code.',
            ], 401);
        }

        // If replace flag is true, remove existing face images first
        $replace = $request->boolean('replace') || $request->input('replace') === '1' || $request->input('replace') === 1;
        if ($replace) {
            foreach ($user->faceImages as $oldImg) {
                if ($oldImg->path && Storage::disk('public')->exists($oldImg->path)) {
                    Storage::disk('public')->delete($oldImg->path);
                }
                $oldImg->delete();
            }
        }

        $createdImages = [];
        $firstPath = null;

        // 1. Process Multipart File Uploads
        $filesToProcess = [];

        // Check common file keys
        $possibleKeys = ['face_images', 'images', 'photos', 'face_image', 'image', 'photo', 'file'];
        foreach ($possibleKeys as $key) {
            if ($request->hasFile($key)) {
                $files = $request->file($key);
                if (is_array($files)) {
                    foreach ($files as $f) {
                        if ($f && $f->isValid()) {
                            $filesToProcess[] = $f;
                        }
                    }
                } elseif ($files && $files->isValid()) {
                    $filesToProcess[] = $files;
                }
            }
        }

        foreach ($filesToProcess as $file) {
            // Validate image extension / mime
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'heic'])) {
                continue;
            }

            $path = $file->store('faces', 'public');
            $record = $user->faceImages()->create([
                'path' => $path,
            ]);
            $createdImages[] = $record;
            if (!$firstPath) {
                $firstPath = $path;
            }
        }

        // 2. Process Base64 Encoded Images
        $base64Inputs = [];
        $b64Keys = ['image_base64', 'face_base64', 'base64_image', 'photo_base64', 'face_image_base64'];
        foreach ($b64Keys as $bKey) {
            if ($request->filled($bKey)) {
                $val = $request->input($bKey);
                if (is_array($val)) {
                    $base64Inputs = array_merge($base64Inputs, $val);
                } elseif (is_string($val)) {
                    $base64Inputs[] = $val;
                }
            }
        }

        if ($request->has('base64_images') && is_array($request->input('base64_images'))) {
            $base64Inputs = array_merge($base64Inputs, $request->input('base64_images'));
        }

        foreach ($base64Inputs as $b64) {
            if (!is_string($b64) || empty(trim($b64))) {
                continue;
            }

            $ext = 'jpg';
            if (preg_match('/^data:image\/(\w+);base64,/', $b64, $matches)) {
                $ext = strtolower($matches[1]);
                if ($ext === 'jpeg') $ext = 'jpg';
                $b64 = substr($b64, strpos($b64, ',') + 1);
            }

            $decoded = base64_decode($b64);
            if ($decoded !== false && strlen($decoded) > 0) {
                $filename = 'faces/' . Str::random(40) . '.' . $ext;
                Storage::disk('public')->put($filename, $decoded);

                $record = $user->faceImages()->create([
                    'path' => $filename,
                ]);
                $createdImages[] = $record;
                if (!$firstPath) {
                    $firstPath = $filename;
                }
            }
        }

        if (empty($createdImages)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid face image files or base64 data found. Please send "face_images" (file/array) or "image_base64".',
            ], 422);
        }

        // Optionally set first uploaded face as user's profile photo
        $setAsAvatar = $request->boolean('set_as_avatar') || $request->boolean('set_profile_photo');
        if ($setAsAvatar && $firstPath) {
            $user->update(['profile_photo' => $firstPath]);
        }

        // Refresh face images relation
        $user->load('faceImages');

        return response()->json([
            'success' => true,
            'message' => count($createdImages) . ' face image(s) uploaded successfully.',
            'user' => [
                'id' => $user->id,
                'employee_code' => $user->employee_code,
                'name' => $user->displayName(),
                'department' => $user->department ?? '',
                'designation' => $user->designation ?? '',
                'avatar_url' => $user->avatarUrl(),
            ],
            'uploaded_count' => count($createdImages),
            'face_images' => $user->faceImages,
        ], 201);
    }

    /**
     * Get list of face images for an employee.
     */
    public function index(Request $request)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found or unauthenticated.',
            ], 401);
        }

        $images = $user->faceImages()->latest()->get();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'employee_code' => $user->employee_code,
                'name' => $user->displayName(),
                'avatar_url' => $user->avatarUrl(),
            ],
            'total' => $images->count(),
            'face_images' => $images,
        ]);
    }

    /**
     * Delete a specific face image by ID.
     */
    public function destroy(Request $request, $id)
    {
        $user = $this->resolveUser($request);
        $image = FaceImage::find($id);

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Face image not found.',
            ], 404);
        }

        // Check ownership if user is resolved and not admin
        if ($user && !$user->isAdmin() && !$user->isManager() && $image->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this face image.',
            ], 403);
        }

        if ($image->path && Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Face image deleted successfully.',
        ]);
    }

    /**
     * Delete multiple face images or single face image via POST.
     */
    public function deleteMultiple(Request $request)
    {
        $user = $this->resolveUser($request);
        $ids = $request->input('ids');
        if (!$ids && $request->filled('id')) {
            $ids = [$request->input('id')];
        }

        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide an array of image "ids" or single "id" to delete.',
            ], 422);
        }

        $query = FaceImage::whereIn('id', $ids);
        if ($user && !$user->isAdmin() && !$user->isManager()) {
            $query->where('user_id', $user->id);
        }

        $images = $query->get();
        $deletedCount = 0;

        foreach ($images as $img) {
            if ($img->path && Storage::disk('public')->exists($img->path)) {
                Storage::disk('public')->delete($img->path);
            }
            $img->delete();
            $deletedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} face image(s) deleted successfully.",
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * Get all active staff who have registered face images.
     * Useful for Face Recognition Terminals / Tablet Kiosk scanning.
     */
    public function registeredStaff(Request $request)
    {
        $query = User::where('status', 'active')
            ->has('faceImages')
            ->with(['faceImages', 'department', 'company']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        $staff = $query->get()->map(function (User $u) {
            return [
                'id' => $u->id,
                'employee_code' => $u->employee_code,
                'name' => $u->displayName(),
                'department' => $u->department?->name ?? (string)$u->department,
                'designation' => $u->designation ?? '',
                'company' => $u->company?->name ?? '',
                'avatar_url' => $u->avatarUrl(),
                'face_images' => $u->faceImages->map(fn($f) => [
                    'id' => $f->id,
                    'path' => $f->path,
                    'url' => $f->url,
                    'created_at' => $f->created_at,
                ]),
            ];
        });

        return response()->json([
            'success' => true,
            'total_staff' => $staff->count(),
            'staff' => $staff,
        ]);
    }

    /**
     * Resolve target user from authentication, request parameters, or headers.
     */
    protected function resolveUser(Request $request): ?User
    {
        // 1. Explicit user_id or employee_id passed in request
        $userId = $request->input('user_id') ?? $request->input('employee_id');
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                return $user;
            }
        }

        // 2. Explicit employee_code passed
        if ($request->filled('employee_code')) {
            $user = User::where('employee_code', $request->input('employee_code'))->first();
            if ($user) {
                return $user;
            }
        }

        // 3. Authenticated user from api.token middleware or session
        $authUser = $request->user();
        if ($authUser) {
            return $authUser;
        }

        // 4. Bearer token in header or X-Api-Token
        $token = $request->bearerToken() ?: $request->header('X-Api-Token');
        if ($token) {
            $user = User::where('api_token', $token)->first();
            if ($user) {
                return $user;
            }
        }

        // 5. Phone or Email passed
        if ($request->filled('phone')) {
            $user = User::where('phone', $request->input('phone'))->first();
            if ($user) {
                return $user;
            }
        }
        if ($request->filled('email')) {
            $user = User::where('email', $request->input('email'))->first();
            if ($user) {
                return $user;
            }
        }

        return null;
    }
}
