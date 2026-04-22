<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Cấu hình URL reset password
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('app.frontend_url').'/reset-password?'
                . http_build_query([
                    'token' => $token,
                    'email' => $user->email
                ]);
        });
    }
}