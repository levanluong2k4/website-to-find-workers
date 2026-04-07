<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerTag extends Model
{
    protected $fillable = [
        'label',
        'slug',
        'color',
    ];

    public function customers()
    {
        return $this->belongsToMany(User::class, 'customer_tag_assignments', 'tag_id', 'customer_id')
            ->withPivot('admin_id')
            ->withTimestamps();
    }
}
