<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = ['email', 'code_hash', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
