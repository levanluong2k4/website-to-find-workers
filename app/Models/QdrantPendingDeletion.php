<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QdrantPendingDeletion extends Model
{
    protected $fillable = [
        'collection',
        'point_id',
        'reason',
        'attempt_count',
        'available_at',
        'last_error',
    ];

    protected $casts = [
        'available_at' => 'datetime',
    ];
}
