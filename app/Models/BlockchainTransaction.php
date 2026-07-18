<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BlockchainTransaction extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'transactionable_type',
        'transactionable_id',
        'action',
        'expected_event',
        'wallet_address',
        'transaction_hash',
        'chain_id',
        'status',
        'payload',
        'receipt',
        'error_message',
        'submitted_at',
        'confirmed_at',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'receipt' => 'array',
        'chain_id' => 'integer',
        'submitted_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
