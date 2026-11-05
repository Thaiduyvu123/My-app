<?php

namespace App\Models;

use App\Support\AuthModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SqliteConsent extends Model
{
    public $timestamps = false;

    protected $table = 'consents';

    protected $fillable = ['user_id', 'type', 'version', 'granted', 'created_at'];

    protected function casts(): array
    {
        return [
            'granted' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        $userModel = AuthModels::user();

        return $this->belongsTo($userModel);
    }
}
