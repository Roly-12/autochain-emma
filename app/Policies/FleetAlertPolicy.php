<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FleetAlert;
use App\Models\User;

class FleetAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasRole([
            UserRole::SuperAdmin,
            UserRole::GestionnaireParc,
            UserRole::Chauffeur,
        ]);
    }

    public function view(User $user, FleetAlert $alert): bool
    {
        if ($user->roleEnum()->canManageFleet()) {
            return true;
        }

        return $user->hasRole(UserRole::Chauffeur)
            && (int) $alert->vehicle?->current_driver_id === (int) $user->id;
    }

    public function resolve(User $user, FleetAlert $alert): bool
    {
        return $user->is_active && $user->roleEnum()->canManageFleet();
    }
}
