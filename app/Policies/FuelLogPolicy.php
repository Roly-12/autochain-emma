<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FuelLog;
use App\Models\User;
use App\Models\Vehicle;

class FuelLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasRole([
            UserRole::SuperAdmin,
            UserRole::GestionnaireParc,
            UserRole::Chauffeur,
        ]);
    }

    public function view(User $user, FuelLog $fuelLog): bool
    {
        if ($user->roleEnum()->canManageFleet()) {
            return true;
        }

        return $user->hasRole(UserRole::Chauffeur)
            && (int) $fuelLog->recorded_by === (int) $user->id;
    }

    public function createForVehicle(User $user, Vehicle $vehicle): bool
    {
        if ($user->roleEnum()->canManageFleet()) {
            return true;
        }

        return $user->hasRole(UserRole::Chauffeur)
            && (int) $vehicle->current_driver_id === (int) $user->id;
    }
}
