<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerNote extends Model
{
    protected $fillable = [
        'customer_id',
        'admin_id',
        'category',
        'content',
        'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
