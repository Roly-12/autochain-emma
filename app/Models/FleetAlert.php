<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetAlert extends Model
{
    protected $fillable = [
        'vehicle_id',
        'type',
        'severity',
        'title',
        'message',
        'due_date',
        'notified_at',
        'resolved_at',
        'fingerprint',
    ];

    protected $casts = [
        'due_date' => 'date',
        'notified_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('resolved_at');
    }
}
