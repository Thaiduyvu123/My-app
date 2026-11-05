<?php

namespace App\Support;

use App\Models\Consent;
use App\Models\OtpCode;
use App\Models\PersonalAccessToken;
use App\Models\SqliteConsent;
use App\Models\SqliteOtpCode;
use App\Models\SqlitePersonalAccessToken;
use App\Models\SqliteUser;
use App\Models\User;

class AuthModels
{
    public static function usesMongoDb(): bool
    {
        return config('database.default') === 'mongodb';
    }

    public static function user(): string
    {
        return self::usesMongoDb() ? User::class : SqliteUser::class;
    }

    public static function otpCode(): string
    {
        return self::usesMongoDb() ? OtpCode::class : SqliteOtpCode::class;
    }

    public static function consent(): string
    {
        return self::usesMongoDb() ? Consent::class : SqliteConsent::class;
    }

    public static function personalAccessToken(): string
    {
        return self::usesMongoDb()
            ? PersonalAccessToken::class
            : SqlitePersonalAccessToken::class;
    }
}
