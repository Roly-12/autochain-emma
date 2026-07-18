<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainEvent extends Model
{
    protected $fillable = [
        'vehicle_id',
        'blockchain_transaction_id',
        'transaction_hash',
        'block_number',
        'log_index',
        'event_name',
        'topics',
        'data',
    ];

    protected $casts = [
        'topics' => 'array',
        'block_number' => 'integer',
        'log_index' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
