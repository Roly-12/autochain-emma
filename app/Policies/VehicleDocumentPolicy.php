<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleDocument;

class VehicleDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, VehicleDocument $document): bool
    {
        $publicAndAvailable = $document->is_public
            && (! $document->expires_at || $document->expires_at->isToday() || $document->expires_at->isFuture());

        return $user->is_active
            && ($publicAndAvailable || $user->roleEnum()->canManageFleet());
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->roleEnum()->canUploadDocuments();
    }

    public function delete(User $user, VehicleDocument $document): bool
    {
        return $user->is_active && $user->roleEnum()->canManageFleet();
    }
}
