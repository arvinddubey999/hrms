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
}
