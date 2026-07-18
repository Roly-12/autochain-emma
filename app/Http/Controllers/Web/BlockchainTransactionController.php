<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ReconcileBlockchainTransaction;
use App\Models\BlockchainTransaction;
use App\Models\MileageLog;
use App\Models\Vehicle;
use App\Services\Blockchain\BlockchainReconciler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlockchainTransactionController extends Controller
{
    public function show(BlockchainTransaction $blockchainTransaction): Response
    {
        $this->assertOwner($blockchainTransaction);

        return Inertia::render('Blockchain/SignTransaction', [
            'transaction' => $blockchainTransaction,
            'contract' => [
                'address' => config('blockchain.contract_address'),
                'abi' => json_decode((string) config('blockchain.contract_abi'), true) ?: [],
                'chain_id' => (int) config('blockchain.chain_id'),
                'network' => config('blockchain.network'),
            ],
            'return_url' => $this->returnUrl($blockchainTransaction),
        ]);
    }

    public function submit(
        Request $request,
        BlockchainTransaction $blockchainTransaction,
        BlockchainReconciler $reconciler
    ): JsonResponse {
        $this->assertOwner($blockchainTransaction);

        $data = $request->validate([
            'transaction_hash' => [
                'required',
                'regex:/^0x[0-9a-fA-F]{64}$/',
                Rule::unique('blockchain_transactions', 'transaction_hash')->ignore($blockchainTransaction->id),
            ],
        ]);

        if (! in_array($blockchainTransaction->status, ['pending', 'submitted'], true)) {
            return response()->json([
                'message' => 'Cette demande a déjà été traitée.',
                'status' => $blockchainTransaction->status,
            ], 409);
        }

        $blockchainTransaction->update([
            'transaction_hash' => strtolower($data['transaction_hash']),
            'status' => 'submitted',
            'submitted_at' => now(),
            'failed_at' => null,
            'error_message' => null,
        ]);
        if (in_array($blockchainTransaction->action, [
            'register_vehicle',
            'assign_driver',
            'update_status',
            'update_mileage',
            'record_maintenance',
        ], true)) {
            $blockchainTransaction->transactionable?->update(['blockchain_status' => 'submitted']);
        }

        $confirmed = $reconciler->reconcile($blockchainTransaction->fresh());
        $blockchainTransaction->refresh();
        if (! $confirmed && $blockchainTransaction->status === 'submitted') {
            ReconcileBlockchainTransaction::dispatch($blockchainTransaction->id)->delay(now()->addSeconds(10));
        }

        return response()->json([
            'message' => $confirmed
                ? 'Transaction confirmée et état certifié.'
                : ($blockchainTransaction->error_message ?: 'Transaction soumise, confirmation en attente.'),
            'status' => $blockchainTransaction->status,
            'return_url' => $this->returnUrl($blockchainTransaction),
        ], $blockchainTransaction->status === 'failed' ? 422 : 200);
    }

    public function retry(BlockchainTransaction $blockchainTransaction): JsonResponse
    {
        $this->assertOwner($blockchainTransaction);
        abort_unless($blockchainTransaction->status === 'failed', 409, 'Seule une transaction échouée peut être relancée.');

        $retry = $blockchainTransaction->replicate([
            'transaction_hash',
            'receipt',
            'error_message',
            'submitted_at',
            'confirmed_at',
            'failed_at',
        ]);
        $retry->uuid = (string) Str::uuid();
        $retry->status = 'pending';
        $retry->save();
        if (in_array($retry->action, [
            'register_vehicle',
            'assign_driver',
            'update_status',
            'update_mileage',
            'record_maintenance',
        ], true)) {
            $retry->transactionable?->update(['blockchain_status' => 'pending']);
        }

        return response()->json([
            'url' => route('blockchain.transactions.show', $retry),
        ]);
    }

    public function status(
        BlockchainTransaction $blockchainTransaction,
        BlockchainReconciler $reconciler
    ): JsonResponse {
        $this->assertOwner($blockchainTransaction);

        if ($blockchainTransaction->status === 'submitted') {
            $reconciler->reconcile($blockchainTransaction);
            $blockchainTransaction->refresh();
        }

        return response()->json([
            'status' => $blockchainTransaction->status,
            'message' => $blockchainTransaction->error_message,
            'return_url' => $this->returnUrl($blockchainTransaction),
        ]);
    }

    private function assertOwner(BlockchainTransaction $record): void
    {
        abort_unless((int) $record->user_id === (int) auth()->id(), 403);
    }

    private function returnUrl(BlockchainTransaction $record): string
    {
        $target = $record->transactionable;
        $vehicle = $target instanceof MileageLog ? $target->vehicle : ($target instanceof Vehicle ? $target : null);

        return match (true) {
            $record->action === 'register_vehicle',
            $record->action === 'assign_driver',
            $record->action === 'update_status',
            $record->action === 'update_mileage' => $vehicle
                ? route('vehicles.show', $vehicle)
                : route('vehicles.index'),
            $record->action === 'record_maintenance' => route('maintenance.index'),
            in_array($record->action, ['propose_sale', 'accept_sale', 'cancel_sale'], true) => route('sales.index'),
            $record->action === 'certify_garage' => route('users.index'),
            default => route('dashboard'),
        };
    }
}
