<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerFeedbackCase extends Model
{
    protected $fillable = [
        'source_type',
        'source_id',
        'customer_id',
        'booking_id',
        'worker_id',
        'priority',
        'status',
        'assigned_admin_id',
        'assigned_at',
        'deadline_at',
        'assignment_note',
        'resolved_at',
        'resolution_note',
        'last_snapshot',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'deadline_at' => 'datetime',
        'resolved_at' => 'datetime',
        'last_snapshot' => 'array',
    ];

    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function booking()
    {
        return $this->belongsTo(DonDatLich::class, 'booking_id');
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
