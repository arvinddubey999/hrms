<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken() ?: $request->header('X-Api-Token');
        if (! $token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = User::query()->where('api_token', $token)->where('status', 'active')->first();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $request->setUserResolver(fn () => $user);
        auth()->setUser($user);

        return $next($request);
    }
}
