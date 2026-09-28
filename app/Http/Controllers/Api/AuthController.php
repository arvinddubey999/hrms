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

    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->displayName(),
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role,
            'designation' => $user->designation,
            'department' => $user->department,
            'mobile_attendance' => $user->mobile_attendance,
            'multiple_attendance' => $user->multiple_attendance,
            'ai_selfie' => $user->ai_selfie,
            'live_tracking' => $user->live_tracking,
            'punch_from' => $user->punch_from,
            'view_self_salary' => $user->view_self_salary,
            'photo' => $user->profile_photo ? url('storage/'.$user->profile_photo) : null,
        ];
    }
}
