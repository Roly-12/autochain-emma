<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    protected $fillable = [
        'vehicle_id',
        'type',
        'garage',
        'date',
        'details',
        'status',
        'mileage',
        'parts_changed',
        'garage_user_id',
        'maintenance_hash',
        'transaction_hash',
        'synced_onchain_at',
        'blockchain_status',
    ];

    protected $casts = [
        'date' => 'date',
        'synced_onchain_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function garageUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'garage_user_id');
    }
}
