<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleSale;
use App\Services\Blockchain\BlockchainTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleSaleController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', VehicleSale::class);

        $sales = VehicleSale::with(['vehicle', 'buyer', 'initiator'])
            ->when(
                ! auth()->user()->hasRole(UserRole::SuperAdmin),
                fn ($query) => $query->where('buyer_id', auth()->id())
            )
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', VehicleSale::class);

        return Inertia::render('Sales/Create', [
            'vehicles' => Vehicle::where('status', '!=', 'sold')
                ->whereNotNull('transaction_hash')
                ->orderBy('license_plate')
                ->get(['id', 'license_plate', 'brand', 'model']),
            'buyers' => User::where('role', UserRole::Auditeur->value)
                ->where('is_active', true)
                ->whereNotNull('wallet_verified_at')
                ->whereNotNull('wallet_address')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'wallet_address']),
        ]);
    }

    public function store(
        Request $request,
        BlockchainTransactionService $transactions
    ): RedirectResponse
    {
        $this->authorize('create', VehicleSale::class);
        try {
            $transactions->assertReady($request->user(), 'propose_sale');
        } catch (\RuntimeException $exception) {
            return redirect()->route('wallet.show')->with('error', $exception->getMessage());
        }

        $data = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'buyer_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        $buyer = User::findOrFail($data['buyer_id']);
        abort_unless($buyer->wallet_verified_at && $buyer->wallet_address, 422, 'L’acheteur doit lier et vérifier son wallet MetaMask.');
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        abort_unless($vehicle->transaction_hash, 422, 'Le véhicule doit d’abord être enregistré on-chain.');
        abort_if(
            $vehicle->sales()->whereIn('status', ['pending', 'admin_signed', 'buyer_signed'])->exists(),
            422,
            'Une vente est déjà en cours pour ce véhicule.'
        );

        $sale = VehicleSale::create([
            'vehicle_id' => $data['vehicle_id'],
            'initiated_by' => $request->user()->id,
            'buyer_id' => $buyer->id,
            'buyer_wallet' => strtolower($buyer->wallet_address),
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

        $transaction = $transactions->prepare(
            $sale,
            $request->user(),
            'propose_sale',
            'TransferProposed',
            'proposeTransfer',
            ['0x'.hash('sha256', $vehicle->blockchain_vehicle_id), $buyer->wallet_address],
            ['buyer_id' => $buyer->id]
        );

        return redirect()->route('blockchain.transactions.show', $transaction);
    }

    public function signBuyer(
        Request $request,
        VehicleSale $sale,
        BlockchainTransactionService $transactions
    ): RedirectResponse
    {
        $this->authorize('accept', $sale);

        if (in_array($sale->status, ['cancelled', 'completed'], true)) {
            return back()->withErrors(['sale' => 'Cette vente ne peut plus être signée.']);
        }

        abort_unless($sale->status === 'admin_signed', 422, 'La proposition admin doit être confirmée on-chain.');
        abort_unless(
            $request->user()->wallet_verified_at
            && hash_equals(strtolower($sale->buyer_wallet), strtolower((string) $request->user()->wallet_address)),
            422,
            'Le wallet vérifié ne correspond pas au wallet acheteur désigné.'
        );
        try {
            $transactions->assertReady($request->user(), 'accept_sale');
        } catch (\RuntimeException $exception) {
            return redirect()->route('wallet.show')->with('error', $exception->getMessage());
        }

        $transaction = $transactions->prepare(
            $sale,
            $request->user(),
            'accept_sale',
            'TransferAccepted',
            'acceptTransfer',
            ['0x'.hash('sha256', $sale->vehicle->blockchain_vehicle_id)],
            ['buyer_id' => $sale->buyer_id]
        );

        return redirect()->route('blockchain.transactions.show', $transaction);
    }

    public function cancel(
        Request $request,
        VehicleSale $sale,
        BlockchainTransactionService $transactions
    ): RedirectResponse
    {
        $this->authorize('cancel', $sale);

        if ($sale->status === 'pending') {
            $sale->update(['status' => 'cancelled']);

            return back()->with('success', 'Vente annulée.');
        }

        try {
            $transactions->assertReady($request->user(), 'cancel_sale');
        } catch (\RuntimeException $exception) {
            return redirect()->route('wallet.show')->with('error', $exception->getMessage());
        }

        $transaction = $transactions->prepare(
            $sale,
            $request->user(),
            'cancel_sale',
            'TransferCancelled',
            'cancelTransfer',
            ['0x'.hash('sha256', $sale->vehicle->blockchain_vehicle_id)]
        );

        return redirect()->route('blockchain.transactions.show', $transaction);
    }
}
