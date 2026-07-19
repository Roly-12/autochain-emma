<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'wallet_address',
    'onchain_identifier',
    'is_active',
    'is_verified_onchain',
    'phone_number',
    'company_name',
    'bio',
    'avatar_path',
    'company_logo_path',
    'theme_preference',
    'notification_email',
    'mfa_enabled',
])]
#[Hidden(['password', 'remember_token', 'wallet_nonce'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'mfa_enabled' => 'boolean',
        'notification_email' => 'boolean',
        'is_active' => 'boolean',
        'is_verified_onchain' => 'boolean',
        'last_onchain_activity' => 'datetime',
        'wallet_nonce_expires_at' => 'datetime',
        'wallet_verified_at' => 'datetime',
        'role' => UserRole::class,
    ];

    protected $appends = ['avatar_url', 'company_logo_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->avatar_path);
    }

    public function getCompanyLogoUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->company_logo_path);
    }

    public function roleEnum(): UserRole
    {
        return $this->role instanceof UserRole
            ? $this->role
            : UserRole::tryFrom((string) $this->role) ?? UserRole::Auditeur;
    }

    public function hasRole(UserRole|string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $current = $this->roleEnum()->value;

        foreach ($roles as $role) {
            $value = $role instanceof UserRole ? $role->value : (string) $role;
            if ($current === $value) {
                return true;
            }
        }

        return false;
    }

    public function requiresMfa(): bool
    {
        return true;
    }

    public function assignedVehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'current_driver_id');
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class, 'recorded_by');
    }

    private function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = (string) config('filesystems.media_disk', 'public');

        return $disk === 'public'
            ? '/storage/'.$path
            : Storage::disk($disk)->url($path);
    }
}
