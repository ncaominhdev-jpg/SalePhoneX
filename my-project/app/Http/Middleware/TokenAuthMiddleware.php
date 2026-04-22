<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TokenAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        Log::info('TokenAuthMiddleware: token received: ' . $token);

        if (!$token) {
            return response()->json(['message' => 'Token không được cung cấp.'], 401);
        }

        $user = User::where('remember_token', $token)->first();

        if (!$user) {
            Log::info('TokenAuthMiddleware: no user found with token: ' . $token);
            return response()->json(['message' => 'Token không hợp lệ.'], 401);
        }

        Log::info('TokenAuthMiddleware: user found: ' . $user->email);

        // Gán user vào request để controller có thể lấy
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
