<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $role = $user?->roleEnum();
        $siteOwner = Schema::hasTable('users')
            ? User::query()
                ->where('role', UserRole::SuperAdmin->value)
                ->whereNotNull('company_logo_path')
                ->oldest('id')
                ->first()
            : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'branding' => [
                'logo_url' => $siteOwner?->company_logo_url,
                'app_name' => config('app.name', 'AutoChain Emma+'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'permissions' => [
                'manage_fleet' => $role?->canManageFleet() ?? false,
                'create_vehicle' => $role === \App\Enums\UserRole::SuperAdmin,
                'create_maintenance' => $role === \App\Enums\UserRole::GaragisteAgree
                    && (bool) $user?->is_verified_onchain,
                'view_fuel' => in_array($role, [
                    \App\Enums\UserRole::SuperAdmin,
                    \App\Enums\UserRole::GestionnaireParc,
                    \App\Enums\UserRole::Chauffeur,
                ], true),
                'view_alerts' => in_array($role, [
                    \App\Enums\UserRole::SuperAdmin,
                    \App\Enums\UserRole::GestionnaireParc,
                    \App\Enums\UserRole::Chauffeur,
                ], true),
                'view_sales' => in_array($role, [
                    \App\Enums\UserRole::SuperAdmin,
                    \App\Enums\UserRole::Auditeur,
                ], true),
                'manage_users' => $role?->canManageUsers() ?? false,
                'certify_maintenance' => $role?->canCertifyMaintenance() ?? false,
                'report_mileage' => $role?->canReportMileage() ?? false,
                'upload_documents' => $role?->canUploadDocuments() ?? false,
                'read_only' => $role?->isReadOnly() ?? false,
                'role' => $role?->value,
                'role_label' => $role?->label(),
            ],
        ];
    }
}
