<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDocument extends Model
{
    protected $appends = ['gateway_url'];
    protected $fillable = [
        'vehicle_id',
        'uploaded_by',
        'type',
        'title',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'content_hash',
        'ipfs_cid',
        'is_public',
        'expires_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'expires_at' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getGatewayUrlAttribute(): ?string
    {
        return $this->ipfs_cid
            ? rtrim((string) config('ipfs.gateway_url'), '/').'/'.$this->ipfs_cid
            : null;
    }
}
