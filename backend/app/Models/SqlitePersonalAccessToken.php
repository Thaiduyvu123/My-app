<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class SqlitePersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $table = 'personal_access_tokens';
}
