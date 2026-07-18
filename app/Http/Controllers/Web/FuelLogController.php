<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use App\Models\FuelLog;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FuelLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FuelLog::class);

        $logs = FuelLog::with(['vehicle', 'recorder'])
            ->when(
                $request->user()->hasRole(UserRole::Chauffeur),
                fn ($query) => $query->where('recorded_by', $request->user()->id)
            )
            ->when($request->vehicle_id, fn ($q) => $q->where('vehicle_id', $request->vehicle_id))
            ->orderByDesc('filled_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fuel/Index', [
            'logs' => $logs,
            'vehicles' => Vehicle::orderBy('license_plate')->get(['id', 'license_plate']),
            'filters' => $request->only('vehicle_id'),
        ]);
    }

    public function create(): Response
    {
        $user = auth()->user();
        $this->authorize('viewAny', FuelLog::class);

        return Inertia::render('Fuel/Create', [
            'vehicles' => Vehicle::query()
                ->when(
                    $user->hasRole(UserRole::Chauffeur),
                    fn ($query) => $query->where('current_driver_id', $user->id)
                )
                ->orderBy('license_plate')
                ->get(['id', 'license_plate', 'last_certified_mileage']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'filled_at' => 'required|date',
            'liters' => 'required|numeric|min:0.1',
            'amount' => 'nullable|numeric|min:0',
            'odometer' => 'required|integer|min:0',
            'station' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        Gate::authorize('createForVehicle', [FuelLog::class, $vehicle]);

        $minimumOdometer = max(
            (int) ($vehicle->last_certified_mileage ?? 0),
            (int) ($vehicle->fuelLogs()->max('odometer') ?? 0)
        );

        if ((int) $data['odometer'] < $minimumOdometer) {
            return back()->withErrors([
                'odometer' => "Le compteur doit être supérieur ou égal à {$minimumOdometer} km.",
            ])->withInput();
        }

        FuelLog::create([
            ...$data,
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()->route('fuel.index')->with('success', 'Plein enregistré.');
    }
}
