<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\FleetAlert;
use App\Models\FuelLog;
use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Models\VehicleSale;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Home');
    }

    public function about(): Response
    {
        return Inertia::render('About');
    }

    public function services(): Response
    {
        return Inertia::render('Services');
    }

    public function contact(): Response
    {
        return Inertia::render('Contact');
    }

    public function dashboard(): Response
    {
        $user = auth()->user();
        $vehicles = fn () => Vehicle::query()
            ->when(
                $user->hasRole(UserRole::Chauffeur),
                fn ($query) => $query->where('current_driver_id', $user->id)
            )
            ->when(
                $user->hasRole(UserRole::Auditeur),
                fn ($query) => $query->where('blockchain_status', 'confirmed')
            );
        $maintenances = fn () => Maintenance::query()
            ->when(
                $user->hasRole(UserRole::Auditeur),
                fn ($query) => $query->where('blockchain_status', 'confirmed')
            )
            ->when(
                $user->hasRole(UserRole::Chauffeur),
                fn ($query) => $query->whereHas(
                    'vehicle',
                    fn ($vehicleQuery) => $vehicleQuery->where('current_driver_id', $user->id)
                )
            )
            ->when(
                $user->hasRole(UserRole::GaragisteAgree),
                fn ($query) => $query->where('garage_user_id', $user->id)
            );
        $alerts = fn () => FleetAlert::query()
            ->when(
                ! $user->roleEnum()->canManageFleet() && ! $user->hasRole(UserRole::Chauffeur),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->when(
                $user->hasRole(UserRole::Chauffeur),
                fn ($query) => $query->whereHas(
                    'vehicle',
                    fn ($vehicleQuery) => $vehicleQuery->where('current_driver_id', $user->id)
                )
            );

        $stats = [
            [
                'label' => 'Véhicules',
                'value' => (string) $vehicles()->count(),
                'detail' => $vehicles()->where('status', 'available')->count().' disponibles',
            ],
            [
                'label' => 'En mission',
                'value' => (string) $vehicles()->where('status', 'in_mission')->count(),
                'detail' => $vehicles()->where('status', 'maintenance')->count().' en atelier',
            ],
            [
                'label' => 'Entretiens',
                'value' => (string) $maintenances()->count(),
                'detail' => $maintenances()->where('blockchain_status', 'confirmed')->count().' certifiés on-chain',
            ],
            [
                'label' => 'Alertes ouvertes',
                'value' => (string) $alerts()->open()->count(),
                'detail' => $alerts()->open()->where('severity', 'critical')->count().' critiques',
            ],
        ];

        $fleet = [
            'available' => $vehicles()->where('status', 'available')->count(),
            'in_mission' => $vehicles()->where('status', 'in_mission')->count(),
            'maintenance' => $vehicles()->where('status', 'maintenance')->count(),
            'broken' => $vehicles()->where('status', 'broken')->count(),
            'sold' => $vehicles()->where('status', 'sold')->count(),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'fleet' => $fleet,
            'recentVehicles' => $vehicles()->orderByDesc('updated_at')->limit(5)->get([
                'id', 'blockchain_vehicle_id', 'license_plate', 'brand', 'model', 'status', 'last_certified_mileage', 'transaction_hash',
            ]),
            'openAlerts' => $alerts()->with('vehicle')->open()->orderBy('due_date')->limit(5)->get(),
            'pendingSales' => VehicleSale::with('vehicle')
                ->when(! $user->hasRole(UserRole::SuperAdmin), fn ($query) => $query->where('buyer_id', $user->id))
                ->whereIn('status', ['pending', 'admin_signed', 'buyer_signed'])
                ->limit(5)
                ->get(),
            'fuelCount' => FuelLog::query()
                ->when(
                    ! $user->roleEnum()->canManageFleet() && ! $user->hasRole(UserRole::Chauffeur),
                    fn ($query) => $query->whereRaw('1 = 0')
                )
                ->when($user->hasRole(UserRole::Chauffeur), fn ($query) => $query->where('recorded_by', $user->id))
                ->count(),
            'blockchainReady' => (bool) config('blockchain.contract_address'),
        ]);
    }
}
