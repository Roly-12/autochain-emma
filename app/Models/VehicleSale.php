<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleSale extends Model
{
    protected $fillable = [
        'vehicle_id',
        'initiated_by',
        'buyer_id',
        'buyer_wallet',
        'status',
        'admin_signed_at',
        'buyer_signed_at',
        'completed_at',
        'transaction_hash',
        'proposal_transaction_hash',
        'acceptance_transaction_hash',
        'proposal_confirmed_at',
        'acceptance_confirmed_at',
        'notes',
    ];

    protected $casts = [
        'admin_signed_at' => 'datetime',
        'buyer_signed_at' => 'datetime',
        'completed_at' => 'datetime',
        'proposal_confirmed_at' => 'datetime',
        'acceptance_confirmed_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function bothSigned(): bool
    {
        return $this->admin_signed_at !== null && $this->buyer_signed_at !== null;
    }
}
