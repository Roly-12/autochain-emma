<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Services\Blockchain\BlockchainTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Maintenance::class);

        $records = Maintenance::with('vehicle')
            ->when(
                $request->user()->hasRole(UserRole::Auditeur),
                fn ($query) => $query->where('blockchain_status', 'confirmed')
            )
            ->when(
                $request->user()->hasRole(UserRole::Chauffeur),
                fn ($query) => $query->whereHas(
                    'vehicle',
                    fn ($vehicleQuery) => $vehicleQuery->where('current_driver_id', $request->user()->id)
                )
            )
            ->orderByDesc('date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Maintenance/Index', [
            'records' => $records,
            'canCreate' => $request->user()->can('create', Maintenance::class),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Maintenance::class);

        return Inertia::render('Maintenance/Create', [
            'vehicles' => Vehicle::orderBy('license_plate')->get(['id', 'license_plate', 'brand', 'model', 'last_certified_mileage']),
        ]);
    }

    public function store(
        Request $request,
        BlockchainTransactionService $transactions
    ): RedirectResponse
    {
        $this->authorize('create', Maintenance::class);
        try {
            $transactions->assertReady($request->user(), 'record_maintenance');
        } catch (\RuntimeException $exception) {
            return redirect()->route('wallet.show')->with('error', $exception->getMessage());
        }

        $data = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'type' => 'required|string|max:255',
            'garage' => 'nullable|string|max:255',
            'date' => 'required|date',
            'details' => 'nullable|string',
            'mileage' => 'required|integer|min:0',
            'parts_changed' => 'nullable|string',
            'sync_blockchain' => 'boolean',
        ]);

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        abort_unless($vehicle->transaction_hash && $vehicle->mileage_certified_at, 422, 'Le véhicule doit d’abord être enregistré on-chain.');
        if ((int) $data['mileage'] !== (int) ($vehicle->last_certified_mileage ?? 0)) {
            return back()->withErrors([
                'mileage' => 'La maintenance doit utiliser le dernier kilométrage déjà certifié.',
            ])->withInput();
        }

        $maintenance = Maintenance::create([
            ...collect($data)->except('sync_blockchain')->all(),
            'status' => 'pending',
            'blockchain_status' => 'pending',
            'garage_user_id' => $request->user()->id,
        ]);

        $canonical = json_encode([
            'id' => $maintenance->id,
            'vehicle_id' => $maintenance->vehicle_id,
            'type' => $maintenance->type,
            'date' => $maintenance->date->toDateString(),
            'mileage' => (int) $maintenance->mileage,
            'details' => $maintenance->details,
            'parts_changed' => $maintenance->parts_changed,
            'garage_user_id' => $maintenance->garage_user_id,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $maintenanceHash = '0x'.hash('sha256', $canonical);
        $maintenance->update(['maintenance_hash' => $maintenanceHash]);

        $transaction = $transactions->prepare(
            $maintenance,
            $request->user(),
            'record_maintenance',
            'MaintenanceRecorded',
            'recordMaintenance',
            [
                '0x'.hash('sha256', $vehicle->blockchain_vehicle_id),
                $maintenanceHash,
                (int) $maintenance->mileage,
            ],
            ['maintenance_hash' => $maintenanceHash]
        );

        return redirect()->route('blockchain.transactions.show', $transaction);
    }

    public function edit(Maintenance $maintenance): Response
    {
        $this->authorize('update', $maintenance);

        return Inertia::render('Maintenance/Edit', [
            'maintenance' => $maintenance,
            'vehicles' => Vehicle::orderBy('license_plate')->get(['id', 'license_plate']),
        ]);
    }

    public function update(Request $request, Maintenance $maintenance): RedirectResponse
    {
        $this->authorize('update', $maintenance);

        $data = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'type' => 'required|string|max:255',
            'garage' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'details' => 'nullable|string',
            'status' => 'nullable|string|max:50',
            'mileage' => 'nullable|integer|min:0',
            'parts_changed' => 'nullable|string',
        ]);

        $maintenance->update($data);

        return redirect()->route('maintenance.index');
    }

    public function destroy(Maintenance $maintenance): RedirectResponse
    {
        $this->authorize('delete', $maintenance);

        $maintenance->delete();

        return redirect()->route('maintenance.index');
    }
}
