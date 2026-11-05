<?php

use App\Support\AuthModels;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('otp:purge', function () {
    $otpModel = AuthModels::otpCode();
    $deleted = $otpModel::query()->where('expires_at', '<=', now())->delete();
    $this->info("Removed {$deleted} expired OTP record(s).");
})->purpose('Remove expired email OTP codes');

Schedule::command('otp:purge')->everyMinute();
