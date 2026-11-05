<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SqliteOtpCode extends Model
{
    protected $table = 'otp_codes';

    protected $fillable = ['email', 'code_hash', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
