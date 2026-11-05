<?php

namespace App\Providers;

use App\Support\AuthModels;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(AuthModels::personalAccessToken());

        RateLimiter::for('otp-request', function ($request) {
            return Limit::perHour(3)
                ->by(hash('sha256', strtolower(trim((string) $request->input('email')))));
        });

        RateLimiter::for('otp-verify', function ($request) {
            return Limit::perHour(10)
                ->by(hash('sha256', strtolower(trim((string) $request->input('email')))));
        });
    }
}
