<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMagic extends Model
{
    protected $table = 'chat_magic';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'guest_token',
        'sender',
        'text',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

