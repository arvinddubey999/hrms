<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($data['login']);
        $user = User::query()
            ->where(function ($q) use ($loginInput) {
                $q->where('email', $loginInput)
                  ->orWhere('phone', $loginInput)
                  ->orWhere('employee_code', $loginInput);
            })
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['login' => 'Invalid credentials (Employee Code / Phone / Email or Password)'])->withInput();
        }

        if ($user->status === 'archived') {
            return back()->withErrors(['login' => 'Your account is archived. Please contact system admin.'])->withInput();
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route($user->defaultLandingRoute());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showProfileSettings(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        return view('auth.profile-settings', [
            'user' => $user,
        ]);
    }

    public function updateProfileSettings(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $rules = [
            'phone' => ['required', 'string', 'max:20', \Illuminate\Validation\Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['nullable', 'email', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ];

        $data = $request->validate($rules, [
            'phone.unique' => 'This phone number is already registered to another employee.',
            'email.unique' => 'This email address is already registered to another employee.',
            'password.confirmed' => 'New password confirmation does not match.',
        ]);

        $updateData = [
            'phone' => $data['phone'],
            'email' => !empty($data['email']) ? trim($data['email']) : null,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return back()->with('ok', 'Profile settings updated successfully!');
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $rules = [
            'new_password' => 'required|string|min:6|confirmed',
        ];

        if (!$user->isAdmin() && $user->password) {
            $rules['current_password'] = 'required|current_password';
        }

        $request->validate($rules);

        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return back()->with('ok', 'Password changed successfully!');
    }
}
