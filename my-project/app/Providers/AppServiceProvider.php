<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use App\Models\Import; // Thêm dòng này
use App\Observers\ImportObserver; // Thêm dòng này
class AppServiceProvider extends ServiceProvider
{
  

    /**
     * Bootstrap any application services.
     */
public function boot()
{
    Import::observe(ImportObserver::class); // Thêm dòng này
    ResetPassword::createUrlUsing(function ($notifiable, $token) {
        return config('app.frontend_url').'/reset-password?'
            . http_build_query([
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset()
            ]);
    });
}

    
}
