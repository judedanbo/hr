<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'method',
        'path',
        'status',
        'user_id',
        'token_name',
        'ip',
        'user_agent',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'status' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
