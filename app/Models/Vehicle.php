<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'blockchain_vehicle_id',
        'license_plate',
        'vin',
        'brand',
        'model',
        'year',
        'fuel_type',
        'photo_path',
        'status',
        'last_certified_mileage',
        'mileage_certified_at',
        'registration_hash',
        'insurance_contract_hash',
        'current_driver_id',
        'assigned_at',
        'technical_control_deadline',
        'insurance_expiry',
        'next_maintenance_date',
        'next_maintenance_mileage',
        'transaction_hash',
        'blockchain_status',
    ];

    protected $casts = [
        'mileage_certified_at' => 'datetime',
        'assigned_at' => 'datetime',
        'technical_control_deadline' => 'date',
        'insurance_expiry' => 'date',
        'next_maintenance_date' => 'date',
    ];

    protected $appends = ['mileage', 'photo_url'];

    public function getMileageAttribute(): ?int
    {
        return $this->last_certified_mileage;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? '/storage/'.$this->photo_path : null;
    }

    public function getRouteKeyName(): string
    {
        return 'blockchain_vehicle_id';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        abort_unless(
            is_string($value) && preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $value),
            404
        );

        return static::query()->where($field, $value)->firstOrFail();
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class)->orderByDesc('date');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VehicleDocument::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class)->orderByDesc('filled_at');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(FleetAlert::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(VehicleSale::class);
    }

    public function mileageLogs(): HasMany
    {
        return $this->hasMany(MileageLog::class)->orderByDesc('created_at');
    }

    public function blockchainEvents(): HasMany
    {
        return $this->hasMany(BlockchainEvent::class)->orderByDesc('block_number');
    }

    public function currentDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_driver_id');
    }

    public function averageConsumption(): ?float
    {
        $logs = FuelLog::query()
            ->where('vehicle_id', $this->id)
            ->orderBy('odometer')
            ->get();

        if ($logs->count() < 2) {
            return null;
        }

        $first = $logs->first();
        $last = $logs->last();
        $km = $last->odometer - $first->odometer;
        $liters = $logs->skip(1)->sum('liters');

        if ($km <= 0 || $liters <= 0) {
            return null;
        }

        return round(($liters / $km) * 100, 2);
    }
}
