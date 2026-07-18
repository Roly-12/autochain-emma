<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MileageLog;
use App\Models\Vehicle;
use App\Services\Blockchain\BlockchainTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MileageController extends Controller
{
    public function store(
        Request $request,
        Vehicle $vehicle,
        BlockchainTransactionService $transactions
    ): RedirectResponse
    {
        $this->authorize('reportMileage', $vehicle);
        abort_unless($vehicle->transaction_hash && $vehicle->mileage_certified_at, 422, 'Le véhicule doit d’abord être enregistré on-chain.');
        try {
            $transactions->assertReady($request->user(), 'update_mileage');
        } catch (\RuntimeException $exception) {
            return redirect()->route('wallet.show')->with('error', $exception->getMessage());
        }

        $data = $request->validate([
            'odometer' => 'required|integer|min:0',
            'context' => 'nullable|in:trip_end,maintenance,manual,assignment',
            'notes' => 'nullable|string|max:1000',
            'sync_blockchain' => 'boolean',
        ]);

        $current = (int) ($vehicle->last_certified_mileage ?? 0);
        if ((int) $data['odometer'] <= $current) {
            return back()->withErrors(['odometer' => "Le kilométrage doit être strictement supérieur à {$current} km."]);
        }

        $log = MileageLog::create([
            'vehicle_id' => $vehicle->id,
            'recorded_by' => $request->user()->id,
            'odometer' => $data['odometer'],
            'context' => $data['context'] ?? 'manual',
            'notes' => $data['notes'] ?? null,
            'blockchain_status' => 'pending',
        ]);

        $transaction = $transactions->prepare(
            $log,
            $request->user(),
            'update_mileage',
            'MileageUpdated',
            'updateMileage',
            ['0x'.hash('sha256', $vehicle->blockchain_vehicle_id), (int) $data['odometer']],
            ['odometer' => (int) $data['odometer']]
        );

        return redirect()->route('blockchain.transactions.show', $transaction);
    }
}
