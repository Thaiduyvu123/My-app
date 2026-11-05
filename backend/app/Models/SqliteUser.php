<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SqliteUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = ['email', 'display_name', 'birth_date'];

    protected $hidden = ['rating', 'stats'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'age_verified_at' => 'datetime',
            'birth_date' => 'date',
            'rating' => 'array',
            'stats' => 'array',
        ];
    }
}
