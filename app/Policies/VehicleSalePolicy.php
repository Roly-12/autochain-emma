<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\VehicleSale;

class VehicleSalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && ($user->hasRole(UserRole::SuperAdmin) || $user->hasRole(UserRole::Auditeur));
    }

    public function view(User $user, VehicleSale $sale): bool
    {
        return $user->hasRole(UserRole::SuperAdmin)
            || (int) $sale->buyer_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasRole(UserRole::SuperAdmin);
    }

    public function accept(User $user, VehicleSale $sale): bool
    {
        return $user->is_active
            && (int) $sale->buyer_id === (int) $user->id
            && ! in_array($sale->status, ['completed', 'cancelled'], true);
    }

    public function cancel(User $user, VehicleSale $sale): bool
    {
        return $user->is_active && $user->hasRole(UserRole::SuperAdmin);
    }
}
