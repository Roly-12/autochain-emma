<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelLog extends Model
{
    protected $fillable = [
        'vehicle_id',
        'recorded_by',
        'filled_at',
        'liters',
        'amount',
        'odometer',
        'station',
        'notes',
    ];

    protected $casts = [
        'filled_at' => 'date',
        'liters' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
