<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use App\Models\FleetAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FleetAlert::class);

        $alerts = FleetAlert::with('vehicle')
            ->when(
                $request->user()->hasRole(UserRole::Chauffeur),
                fn ($query) => $query->whereHas(
                    'vehicle',
                    fn ($vehicleQuery) => $vehicleQuery->where('current_driver_id', $request->user()->id)
                )
            )
            ->when(! $request->boolean('show_resolved'), fn ($q) => $q->open())
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Alerts/Index', [
            'alerts' => $alerts,
            'filters' => [
                'show_resolved' => $request->boolean('show_resolved'),
            ],
        ]);
    }

    public function resolve(FleetAlert $alert): RedirectResponse
    {
        $this->authorize('resolve', $alert);

        $alert->update(['resolved_at' => now()]);

        return back()->with('success', 'Alerte résolue.');
    }
}
