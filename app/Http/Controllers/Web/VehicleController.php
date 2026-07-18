<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Blockchain\BlockchainTransactionService;
use App\Services\Blockchain\VehicleBlockchainService;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class VehicleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Vehicle::class);

        $vehicles = Vehicle::with('currentDriver')
            ->when($request->user()->hasRole(UserRole::Chauffeur), function ($q) use ($request) {
                $q->where('current_driver_id', $request->user()->id);
            })
            ->orderByDesc('created_at')
            ->paginate(2)
            ->withQueryString();

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'canCreate' => $request->user()->can('create', Vehicle::class),
        ]);
    }

    public function show(Vehicle $vehicle, VehicleBlockchainService $blockchain): Response
    {
        $this->authorize('view', $vehicle);

        $vehicle->load([
            'maintenances.garageUser',
            'documents',
            'fuelLogs' => fn ($q) => $q->limit(10),
            'mileageLogs' => fn ($q) => $q->limit(20),
            'alerts' => fn ($q) => $q->open()->limit(10),
            'currentDriver',
            'sales' => fn ($q) => $q->latest()->limit(5),
            'blockchainEvents' => fn ($q) => $q->limit(50),
        ]);

        $onchain = null;
        try {
            $onchain = $blockchain->getVehicle($vehicle->blockchain_vehicle_id ?? (string) $vehicle->id);
        } catch (\Throwable) {
            $onchain = null;
        }

        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
            'timeline' => $this->buildTimeline($vehicle),
            'onchain' => $onchain,
            'avgConsumption' => $vehicle->averageConsumption(),
            'drivers' => User::where('role', UserRole::Chauffeur->value)
                ->where('is_active', true)
                ->whereNotNull('wallet_verified_at')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'canManage' => auth()->user()->can('update', $vehicle),
            'canAssign' => auth()->user()->can('assign', $vehicle),
            'canDelete' => auth()->user()->can('delete', $vehicle),
            'canReportMileage' => auth()->user()->can('reportMileage', $vehicle),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Vehicle::class);

        return Inertia::render('Vehicles/Create');
    }

    public function edit(Vehicle $vehicle): Response
    {
        $this->authorize('update', $vehicle);

        return Inertia::render('Vehicles/Edit', [
            'vehicle' => $vehicle,
        ]);
    }

    public function store(
        Request $request,
        ImageUploadService $images,
        BlockchainTransactionService $transactions
    ): RedirectResponse
    {
        $this->authorize('create', Vehicle::class);
        try {
            $transactions->assertReady($request->user(), 'register_vehicle');
        } catch (\RuntimeException $exception) {
            return redirect()->route('wallet.show')->with('error', $exception->getMessage());
        }

        $data = $request->validate([
            'license_plate' => 'required|string|max:50|unique:vehicles,license_plate',
            'vin' => 'required|string|max:64|unique:vehicles,vin',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:'.(date('Y') + 1),
            'fuel_type' => 'nullable|string|max:50',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'technical_control_deadline' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date',
            'next_maintenance_mileage' => 'nullable|integer|min:0',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            try {
                $photoPath = $images->storeAsJpeg($request->file('photo'), 'vehicles', 1600);
            } catch (InvalidArgumentException $e) {
                return back()->withErrors(['photo' => $e->getMessage()])->withInput();
            }
        }

        unset($data['photo']);

        $vehicle = Vehicle::create(array_merge($data, [
            'blockchain_vehicle_id' => Str::uuid()->toString(),
            'fuel_type' => $data['fuel_type'] ?? 'essence',
            'photo_path' => $photoPath,
            'status' => 'available',
            'last_certified_mileage' => 0,
            'blockchain_status' => 'pending',
        ]));

        $transaction = $transactions->prepare(
            $vehicle,
            $request->user(),
            'register_vehicle',
            'VehicleRegistered',
            'registerVehicle',
            ['0x'.hash('sha256', $vehicle->blockchain_vehicle_id)],
            ['vehicle_id' => $vehicle->id]
        );

        return redirect()->route('blockchain.transactions.show', $transaction);
    }

    public function update(Request $request, Vehicle $vehicle, ImageUploadService $images): RedirectResponse
    {
        $this->authorize('update', $vehicle);

        $data = $request->validate([
            'license_plate' => ['required', 'string', 'max:50', Rule::unique('vehicles', 'license_plate')->ignore($vehicle->id)],
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:'.(date('Y') + 1),
            'fuel_type' => 'nullable|string|max:50',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'technical_control_deadline' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date',
            'next_maintenance_mileage' => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('photo')) {
            try {
                if ($vehicle->photo_path) {
                    Storage::disk('public')->delete($vehicle->photo_path);
                }
                $data['photo_path'] = $images->storeAsJpeg($request->file('photo'), 'vehicles', 1600);
            } catch (InvalidArgumentException $e) {
                return back()->withErrors(['photo' => $e->getMessage()])->withInput();
            }
        }

        unset($data['photo']);

        // VIN et km certifié : non modifiables ici (intégrité / blockchain)
        $vehicle->update($data);

        return redirect()->route('vehicles.show', $vehicle)->with('success', 'Informations administratives mises à jour.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);

        // Soft delete = archivage (cahier des charges), pas d'effacement physique
        $vehicle->delete();

        return redirect()->route('vehicles.index')->with('success', 'Véhicule archivé (soft delete). Les preuves blockchain restent intactes.');
    }

    public function updatePhoto(Request $request, Vehicle $vehicle, ImageUploadService $images): RedirectResponse
    {
        $this->authorize('update', $vehicle);

        $request->validate([
            'photo' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        try {
            if ($vehicle->photo_path) {
                Storage::disk('public')->delete($vehicle->photo_path);
            }
            $path = $images->storeAsJpeg($request->file('photo'), 'vehicles', 1600);
            $vehicle->update(['photo_path' => $path]);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['photo' => $e->getMessage()]);
        }

        return back()->with('success', 'Photo du véhicule mise à jour.');
    }

    public function assign(
        Request $request,
        Vehicle $vehicle,
        BlockchainTransactionService $transactions
    ): RedirectResponse
    {
        $this->authorize('assign', $vehicle);
        abort_unless($vehicle->transaction_hash, 422, 'Le véhicule doit d’abord être enregistré on-chain.');
        try {
            $transactions->assertReady($request->user(), 'assign_driver');
        } catch (\RuntimeException $exception) {
            return redirect()->route('wallet.show')->with('error', $exception->getMessage());
        }

        $data = $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $driver = User::findOrFail($data['driver_id']);
        abort_unless($driver->hasRole(UserRole::Chauffeur), 422, 'L\'utilisateur doit être chauffeur.');
        abort_unless($driver->wallet_verified_at && $driver->wallet_address, 422, 'Le chauffeur doit lier son wallet MetaMask.');

        $transaction = $transactions->prepare(
            $vehicle,
            $request->user(),
            'assign_driver',
            'DriverAssigned',
            'assignDriver',
            ['0x'.hash('sha256', $vehicle->blockchain_vehicle_id), $driver->wallet_address],
            ['driver_id' => $driver->id]
        );

        return redirect()->route('blockchain.transactions.show', $transaction);
    }

    public function updateStatus(
        Request $request,
        Vehicle $vehicle,
        BlockchainTransactionService $transactions
    ): RedirectResponse {
        $this->authorize('updateStatus', $vehicle);
        abort_unless($vehicle->transaction_hash, 422, 'Le véhicule doit d’abord être enregistré on-chain.');
        try {
            $transactions->assertReady($request->user(), 'update_status');
        } catch (\RuntimeException $exception) {
            return redirect()->route('wallet.show')->with('error', $exception->getMessage());
        }

        $data = $request->validate([
            'status' => 'required|in:available,maintenance,broken',
        ]);

        $statusCode = [
            'available' => 0,
            'maintenance' => 1,
            'broken' => 2,
        ][$data['status']];

        $transaction = $transactions->prepare(
            $vehicle,
            $request->user(),
            'update_status',
            'StatusUpdated',
            'updateStatus',
            ['0x'.hash('sha256', $vehicle->blockchain_vehicle_id), $statusCode],
            ['status' => $data['status']]
        );

        return redirect()->route('blockchain.transactions.show', $transaction);
    }

    protected function buildTimeline(Vehicle $vehicle): array
    {
        $events = [];

        foreach ($vehicle->maintenances as $m) {
            $events[] = [
                'at' => optional($m->date)->toDateString() ?? optional($m->created_at)->toDateString(),
                'type' => 'maintenance',
                'source' => $m->transaction_hash ? 'blockchain' : 'backend',
                'title' => $m->type,
                'detail' => trim(($m->garage ? $m->garage.' — ' : '').($m->details ?? '')),
                'tx' => $m->transaction_hash,
                'certified' => (bool) $m->synced_onchain_at,
            ];
        }

        foreach ($vehicle->mileageLogs as $log) {
            $events[] = [
                'at' => optional($log->created_at)->toDateString(),
                'type' => 'mileage',
                'source' => $log->transaction_hash ? 'blockchain' : 'backend',
                'title' => 'Relevé '.$log->odometer.' km',
                'detail' => $log->context.($log->notes ? ' — '.$log->notes : ''),
                'tx' => $log->transaction_hash,
                'certified' => (bool) $log->synced_onchain_at,
            ];
        }

        foreach ($vehicle->documents as $doc) {
            $events[] = [
                'at' => optional($doc->created_at)->toDateString(),
                'type' => 'document',
                'source' => 'backend',
                'title' => $doc->title,
                'detail' => strtoupper($doc->type).' — hash '.substr($doc->content_hash, 0, 12).'…',
                'tx' => $doc->ipfs_cid,
                'certified' => (bool) $doc->ipfs_cid,
            ];
        }

        foreach ($vehicle->fuelLogs as $fuel) {
            $events[] = [
                'at' => optional($fuel->filled_at)->toDateString(),
                'type' => 'fuel',
                'source' => 'backend',
                'title' => 'Plein '.$fuel->liters.' L',
                'detail' => ($fuel->station ?: 'Station').' @ '.$fuel->odometer.' km',
                'tx' => null,
                'certified' => false,
            ];
        }

        foreach ($vehicle->blockchainEvents as $event) {
            if (in_array($event->event_name, ['MaintenanceRecorded', 'MileageUpdated'], true)) {
                continue;
            }

            $events[] = [
                'at' => optional($event->created_at)->toIso8601String(),
                'type' => 'blockchain',
                'source' => 'blockchain',
                'title' => $event->event_name,
                'detail' => 'Événement confirmé au bloc '.$event->block_number,
                'tx' => $event->transaction_hash,
                'certified' => true,
            ];
        }

        usort($events, fn ($a, $b) => strcmp((string) $b['at'], (string) $a['at']));

        return $events;
    }
}
