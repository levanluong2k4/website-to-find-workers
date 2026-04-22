<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMemory extends Model
{
    protected $fillable = [
        'user_id',
        'guest_token',
        'actor_type',
        'actor_key',
        'memory_type',
        'memory_key',
        'label',
        'value',
        'summary',
        'confidence',
        'source_message_id',
        'is_active',
        'last_used_at',
        'meta',
    ];

    protected $casts = [
        'confidence' => 'decimal:3',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'meta' => 'array',
    ];
}
