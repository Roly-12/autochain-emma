<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAccessLog extends Model
{
    protected $fillable = [
        'vehicle_document_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'integrity_valid',
    ];

    protected $casts = [
        'integrity_valid' => 'boolean',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(VehicleDocument::class, 'vehicle_document_id');
    }
}
