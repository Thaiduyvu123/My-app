<?php

namespace App\Models;

use Laravel\Sanctum\Contracts\HasAbilities;
use MongoDB\Laravel\Eloquent\Model;

class PersonalAccessToken extends Model implements HasAbilities
{
    protected $table = 'personal_access_tokens';

    protected $fillable = ['name', 'token', 'abilities', 'expires_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function tokenable()
    {
        return $this->morphTo('tokenable');
    }

    public static function findToken($token): ?static
    {
        if (! is_string($token)) {
            return null;
        }

        if (! str_contains($token, '|')) {
            return static::query()->where('token', hash('sha256', $token))->first();
        }

        [$id, $plainTextToken] = explode('|', $token, 2);
        $accessToken = static::query()->find($id);

        if ($accessToken === null || ! hash_equals($accessToken->token, hash('sha256', $plainTextToken))) {
            return null;
        }

        return $accessToken;
    }

    public function can($ability): bool
    {
        return in_array('*', $this->abilities ?? [], true)
            || in_array($ability, $this->abilities ?? [], true);
    }

    public function cant($ability): bool
    {
        return ! $this->can($ability);
    }
}
