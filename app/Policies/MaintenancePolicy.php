<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Maintenance;
use App\Models\User;

class MaintenancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Maintenance $maintenance): bool
    {
        if ($user->hasRole(UserRole::Auditeur)) {
            return $maintenance->blockchain_status === 'confirmed';
        }

        if ($user->hasRole(UserRole::Chauffeur)) {
            return (int) $maintenance->vehicle?->current_driver_id === (int) $user->id;
        }

        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active
            && $user->hasRole(UserRole::GaragisteAgree)
            && $user->is_verified_onchain
            && $user->wallet_verified_at !== null;
    }

    public function update(User $user, Maintenance $maintenance): bool
    {
        return $user->roleEnum()->canManageFleet()
            && $maintenance->blockchain_status !== 'confirmed';
    }

    public function delete(User $user, Maintenance $maintenance): bool
    {
        return $this->update($user, $maintenance);
    }
}
