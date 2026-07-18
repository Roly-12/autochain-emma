<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  string  ...$roles  Role values (e.g. super_admin) or "manage_fleet"
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Compte inactif ou non authentifié.');
        }

        $userRole = $user->roleEnum();

        foreach ($roles as $role) {
            if ($role === 'manage_fleet' && $userRole->canManageFleet()) {
                return $next($request);
            }

            if ($role === 'manage_users' && $userRole->canManageUsers()) {
                return $next($request);
            }

            if ($role === 'certify_maintenance' && $userRole->canCertifyMaintenance()) {
                return $next($request);
            }

            if ($role === 'report_mileage' && $userRole->canReportMileage()) {
                return $next($request);
            }

            if ($user->role === $role || $userRole === UserRole::tryFrom($role)) {
                return $next($request);
            }
        }

        abort(403, 'Vous n\'avez pas les permissions nécessaires.');
    }
}
