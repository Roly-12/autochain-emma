<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->hasRole(UserRole::Chauffeur)) {
            return (int) $vehicle->current_driver_id === (int) $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasRole(UserRole::SuperAdmin);
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->roleEnum()->canManageFleet() && $vehicle->status !== 'sold';
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->hasRole(UserRole::SuperAdmin);
    }

    public function assign(User $user, Vehicle $vehicle): bool
    {
        return $user->is_active
            && $user->hasRole(UserRole::SuperAdmin)
            && $vehicle->status !== 'sold';
    }

    public function updateStatus(User $user, Vehicle $vehicle): bool
    {
        return $this->assign($user, $vehicle);
    }

    public function reportMileage(User $user, Vehicle $vehicle): bool
    {
        if ($user->hasRole(UserRole::SuperAdmin)
            || ($user->hasRole(UserRole::GaragisteAgree) && $user->is_verified_onchain)
        ) {
            return true;
        }

        return $user->hasRole(UserRole::Chauffeur) && (int) $vehicle->current_driver_id === (int) $user->id;
    }
}
