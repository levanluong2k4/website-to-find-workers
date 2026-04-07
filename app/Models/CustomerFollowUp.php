<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerFollowUp extends Model
{
    protected $fillable = [
        'customer_id',
        'booking_id',
        'created_by_admin_id',
        'assigned_admin_id',
        'title',
        'channel',
        'priority',
        'status',
        'scheduled_at',
        'completed_at',
        'note',
        'outcome_note',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function booking()
    {
        return $this->belongsTo(DonDatLich::class, 'booking_id');
    }

    public function createdByAdmin()
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }
}
