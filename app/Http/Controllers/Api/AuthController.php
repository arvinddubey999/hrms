<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::query()->where($field, $data['login'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid phone/email or password'], 422);
        }
        if ($user->status !== 'active') {
            return response()->json(['message' => 'Account is archived'], 403);
        }

        if ($request->filled('fcm_token')) {
            $user->forceFill(['fcm_token' => $request->input('fcm_token')])->save();
        }

        $token = $user->issueApiToken();
        $setting = Setting::current();

        return response()->json([
            'token' => $token,
            'user' => $this->payload($user),
            'company' => [
                'name' => $setting->company_name,
                'address' => $setting->company_address,
                'lat' => (float) $setting->office_lat,
                'lng' => (float) $setting->office_lng,
                'radius' => (int) $setting->geofence_radius_m,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $setting = Setting::current();

        return response()->json([
            'user' => $this->payload($request->user()),
            'company' => [
                'name' => $setting->company_name,
                'address' => $setting->company_address,
                'lat' => (float) $setting->office_lat,
                'lng' => (float) $setting->office_lng,
                'radius' => (int) $setting->geofence_radius_m,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->forceFill(['api_token' => null])->save();

        return response()->json(['ok' => true]);
    }

    public function team(Request $request)
    {
        if (! $request->user()->isManager()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $staff = User::query()
            ->where('status', 'active')
            ->where('role', 'employee')
            ->orderBy('first_name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->displayName(),
                'phone' => $u->phone,
                'photo' => $u->profile_photo ? url('storage/'.$u->profile_photo) : null,
                'ai_selfie' => $u->ai_selfie,
                'mobile_attendance' => $u->mobile_attendance,
                'keypad' => ! $u->mobile_attendance,
            ]);

        return response()->json(['data' => $staff]);
    }

    public function updateProfile(Request $request)
    {
        $actor = $request->user();
        $targetId = $request->get('user_id') ?: $actor->id;

        if ($targetId != $actor->id && !$actor->isManager()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $targetUser = User::findOrFail($targetId);

        $data = $request->validate([
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'employee_code' => 'nullable|string',
            'designation' => 'nullable|string',
            'department' => 'nullable|string',
            'vendor_name' => 'nullable|string',
            'password' => 'nullable|string',
            'birthday' => 'nullable|date',
            'date_of_joining' => 'nullable|date',
            'salary' => 'nullable|numeric',
            'pay_type' => 'nullable|string',
            'profile_photo' => 'nullable|file|max:10240',
        ]);

        if (!empty($data['first_name'])) {
            $targetUser->first_name = $data['first_name'];
        }
        if (isset($data['last_name'])) {
            $targetUser->last_name = $data['last_name'];
        }
        $targetUser->name = trim(($targetUser->first_name ?? '') . ' ' . ($targetUser->last_name ?? ''));

        if (!empty($data['phone'])) {
            $targetUser->phone = $data['phone'];
        }
        if (!empty($data['email'])) {
            $targetUser->email = $data['email'];
        }
        if (isset($data['employee_code'])) {
            $targetUser->employee_code = $data['employee_code'];
        }
        if (isset($data['designation'])) {
            $targetUser->designation = $data['designation'];
        }
        if (isset($data['department'])) {
            $targetUser->department = $data['department'];
        }
        if (isset($data['vendor_name'])) {
            $targetUser->vendor_name = $data['vendor_name'];
        }

        // Handle password safely without corrupting login password!
        if (!empty($data['password']) && $data['password'] !== '********' && trim($data['password']) !== '') {
            $targetUser->password = Hash::make($data['password']);
        }

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profiles', 'public');
            $targetUser->profile_photo = $path;
        }

        // Toggles
        foreach (['mobile_attendance', 'multiple_attendance', 'ai_selfie', 'live_tracking', 'self_odometer', 'esi_applicable', 'overtime_applicable'] as $flag) {
            if ($request->has($flag)) {
                $val = $request->input($flag);
                $targetUser->$flag = filter_var($val, FILTER_VALIDATE_BOOLEAN);
            }
        }

        if ($request->filled('fcm_token')) {
            $targetUser->forceFill(['fcm_token' => $request->input('fcm_token')]);
        }

        $targetUser->save();

        return response()->json([
            'ok' => true,
            'message' => 'Profile updated successfully!',
            'user' => $this->payload($targetUser),
        ]);
    }

    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->displayName(),
            'phone' => $user->phone,
            'email' => $user->email,
            'employee_code' => $user->employee_code,
            'role' => $user->role,
            'designation' => $user->designation,
            'department' => $user->department,
            'vendor_name' => $user->vendor_name,
            'mobile_attendance' => $user->mobile_attendance,
            'multiple_attendance' => $user->multiple_attendance,
            'ai_selfie' => $user->ai_selfie,
            'live_tracking' => $user->live_tracking,
            'self_odometer' => $user->self_odometer,
            'esi_applicable' => $user->esi_applicable,
            'overtime_applicable' => $user->overtime_applicable,
            'punch_from' => $user->punch_from,
            'view_self_salary' => $user->view_self_salary,
            'photo' => $user->profile_photo ? url('storage/'.$user->profile_photo) : null,
        ];
    }
}
