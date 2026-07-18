<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MileageLog extends Model
{
    protected $fillable = [
        'vehicle_id',
        'recorded_by',
        'odometer',
        'context',
        'notes',
        'transaction_hash',
        'synced_onchain_at',
        'blockchain_status',
    ];

    protected $casts = [
        'synced_onchain_at' => 'datetime',
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
