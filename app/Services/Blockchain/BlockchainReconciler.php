<?php

namespace App\Services\Blockchain;

use App\Models\BlockchainTransaction;
use App\Models\BlockchainEvent;
use App\Models\Maintenance;
use App\Models\MileageLog;
use App\Models\Vehicle;
use App\Models\VehicleSale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use kornrunner\Keccak;
use RuntimeException;

class BlockchainReconciler
{
    public function __construct(private readonly EthereumRpcService $rpc)
    {
    }

    public function reconcile(BlockchainTransaction $record): bool
    {
        if ($record->status === 'confirmed') {
            return true;
        }

        if (! $record->transaction_hash) {
            return false;
        }

        try {
            $this->validateNetworkAndTransaction($record);
            $receipt = $this->rpc->receipt($record->transaction_hash);

            if (! $receipt) {
                return false;
            }

            if (($receipt['status'] ?? null) !== '0x1') {
                throw new RuntimeException('La transaction a été rejetée par la blockchain.');
            }

            if (! $this->containsExpectedEvent($receipt, $record->expected_event)) {
                throw new RuntimeException("L’événement {$record->expected_event} est absent du receipt.");
            }

            DB::transaction(function () use ($record, $receipt): void {
                $locked = BlockchainTransaction::query()->lockForUpdate()->findOrFail($record->id);
                if ($locked->status === 'confirmed') {
                    return;
                }

                $this->applyCertifiedState($locked);
                $this->indexReceiptEvents($locked, $receipt);
                $locked->update([
                    'status' => 'confirmed',
                    'receipt' => $receipt,
                    'confirmed_at' => now(),
                    'error_message' => null,
                ]);
            });

            return true;
        } catch (\Throwable $exception) {
            if ($exception instanceof ConnectionException || $exception instanceof RequestException) {
                $record->update(['error_message' => 'RPC temporairement indisponible : '.$exception->getMessage()]);
                report($exception);

                return false;
            }

            $record->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'failed_at' => now(),
            ]);
            if (in_array($record->action, [
                'register_vehicle',
                'assign_driver',
                'update_status',
                'update_mileage',
                'record_maintenance',
            ], true)) {
                $record->transactionable?->update(['blockchain_status' => 'failed']);
            }

            report($exception);

            return false;
        }
    }

    private function validateNetworkAndTransaction(BlockchainTransaction $record): void
    {
        if ($this->rpc->chainId() !== (int) $record->chain_id) {
            throw new RuntimeException('Le node RPC ne correspond pas au réseau attendu.');
        }

        $transaction = $this->rpc->transaction($record->transaction_hash);
        if (! $transaction) {
            throw new RuntimeException('Transaction introuvable sur le réseau configuré.');
        }

        if (! hash_equals(strtolower($record->wallet_address), strtolower($transaction['from'] ?? ''))) {
            throw new RuntimeException('La transaction n’a pas été signée par le wallet vérifié.');
        }

        if (! hash_equals(
            strtolower((string) config('blockchain.contract_address')),
            strtolower($transaction['to'] ?? '')
        )) {
            throw new RuntimeException('La transaction ne cible pas le contrat AutoChain configuré.');
        }

        $expectedInput = $this->expectedCalldata($record);
        if (! hash_equals(strtolower($expectedInput), strtolower($transaction['input'] ?? ''))) {
            throw new RuntimeException('Les paramètres signés ne correspondent pas à la demande Laravel.');
        }
    }

    private function expectedCalldata(BlockchainTransaction $record): string
    {
        $methodName = $record->payload['method'] ?? null;
        $arguments = $record->payload['arguments'] ?? [];
        $abi = json_decode((string) config('blockchain.contract_abi'), true) ?: [];
        $function = collect($abi)->first(
            fn (array $entry) => ($entry['type'] ?? null) === 'function' && ($entry['name'] ?? null) === $methodName
        );

        if (! $function) {
            throw new RuntimeException("La fonction {$methodName} est absente de l’ABI.");
        }

        $types = array_column($function['inputs'] ?? [], 'type');
        if (count($types) !== count($arguments)) {
            throw new RuntimeException('Nombre de paramètres blockchain incohérent.');
        }

        $signature = $methodName.'('.implode(',', $types).')';
        $selector = substr(Keccak::hash($signature, 256), 0, 8);
        $encoded = '';

        foreach ($types as $index => $type) {
            $value = $arguments[$index];
            $encoded .= match (true) {
                $type === 'address' => str_pad(preg_replace('/^0x/', '', strtolower((string) $value)), 64, '0', STR_PAD_LEFT),
                $type === 'bytes32' => str_pad(preg_replace('/^0x/', '', strtolower((string) $value)), 64, '0', STR_PAD_RIGHT),
                $type === 'bool' => str_pad($value ? '1' : '0', 64, '0', STR_PAD_LEFT),
                str_starts_with($type, 'uint') => str_pad(dechex((int) $value), 64, '0', STR_PAD_LEFT),
                default => throw new RuntimeException("Type ABI {$type} non pris en charge."),
            };
        }

        return '0x'.$selector.$encoded;
    }

    private function containsExpectedEvent(array $receipt, string $eventName): bool
    {
        $abi = json_decode((string) config('blockchain.contract_abi'), true) ?: [];
        $event = collect($abi)->first(
            fn (array $entry) => ($entry['type'] ?? null) === 'event' && ($entry['name'] ?? null) === $eventName
        );

        if (! $event) {
            throw new RuntimeException("L’événement {$eventName} est absent de l’ABI configurée.");
        }

        $signature = $eventName.'('.implode(',', array_column($event['inputs'] ?? [], 'type')).')';
        $topic = '0x'.Keccak::hash($signature, 256);
        $contract = strtolower((string) config('blockchain.contract_address'));

        return collect($receipt['logs'] ?? [])->contains(
            fn (array $log) => strtolower($log['address'] ?? '') === $contract
                && strtolower($log['topics'][0] ?? '') === strtolower($topic)
        );
    }

    private function applyCertifiedState(BlockchainTransaction $record): void
    {
        $target = $record->transactionable;
        $certified = $record->payload['certified'] ?? [];
        $txHash = $record->transaction_hash;

        match ($record->action) {
            'register_vehicle' => $target instanceof Vehicle && $target->update([
                'transaction_hash' => $txHash,
                'blockchain_status' => 'confirmed',
                'last_certified_mileage' => 0,
                'mileage_certified_at' => now(),
            ]),
            'assign_driver' => $target instanceof Vehicle && $target->update([
                'current_driver_id' => $certified['driver_id'],
                'assigned_at' => now(),
                'status' => 'in_mission',
                'blockchain_status' => 'confirmed',
            ]),
            'update_status' => $target instanceof Vehicle && $target->update([
                'status' => $certified['status'],
                'blockchain_status' => 'confirmed',
            ]),
            'update_mileage' => $this->confirmMileage($target, $txHash),
            'record_maintenance' => $this->confirmMaintenance($target, $txHash),
            'propose_sale' => $target instanceof VehicleSale && $target->update([
                'status' => 'admin_signed',
                'admin_signed_at' => now(),
                'proposal_confirmed_at' => now(),
                'proposal_transaction_hash' => $txHash,
            ]),
            'accept_sale' => $this->confirmSale($target, $txHash),
            'cancel_sale' => $target instanceof VehicleSale && $target->update([
                'status' => 'cancelled',
                'transaction_hash' => $txHash,
            ]),
            'certify_garage' => $target instanceof User && $target->update([
                'is_verified_onchain' => (bool) $certified['certified'],
                'last_onchain_activity' => now(),
            ]),
            default => throw new RuntimeException('Action blockchain non prise en charge.'),
        };
    }

    private function indexReceiptEvents(BlockchainTransaction $record, array $receipt): void
    {
        $abi = json_decode((string) config('blockchain.contract_abi'), true) ?: [];
        $eventTopics = collect($abi)
            ->where('type', 'event')
            ->mapWithKeys(function (array $event): array {
                $signature = $event['name'].'('.implode(',', array_column($event['inputs'] ?? [], 'type')).')';

                return ['0x'.strtolower(Keccak::hash($signature, 256)) => $event['name']];
            });

        $target = $record->transactionable;
        $vehicleId = match (true) {
            $target instanceof Vehicle => $target->id,
            $target instanceof MileageLog, $target instanceof Maintenance, $target instanceof VehicleSale => $target->vehicle_id,
            default => null,
        };
        $contract = strtolower((string) config('blockchain.contract_address'));

        foreach ($receipt['logs'] ?? [] as $log) {
            if (strtolower($log['address'] ?? '') !== $contract) {
                continue;
            }

            $topic = strtolower($log['topics'][0] ?? '');
            $eventName = $eventTopics->get($topic);
            if (! $eventName) {
                continue;
            }

            BlockchainEvent::updateOrCreate(
                [
                    'transaction_hash' => strtolower($record->transaction_hash),
                    'log_index' => hexdec($log['logIndex'] ?? '0x0'),
                ],
                [
                    'vehicle_id' => $vehicleId,
                    'blockchain_transaction_id' => $record->id,
                    'block_number' => hexdec($log['blockNumber'] ?? $receipt['blockNumber'] ?? '0x0'),
                    'event_name' => $eventName,
                    'topics' => $log['topics'] ?? [],
                    'data' => $log['data'] ?? null,
                ]
            );
        }
    }

    private function confirmMileage(mixed $target, string $txHash): bool
    {
        if (! $target instanceof MileageLog) {
            throw new RuntimeException('Cible kilométrique invalide.');
        }

        $target->update([
            'transaction_hash' => $txHash,
            'synced_onchain_at' => now(),
            'blockchain_status' => 'confirmed',
        ]);
        $target->vehicle->update([
            'last_certified_mileage' => $target->odometer,
            'mileage_certified_at' => now(),
        ]);

        return true;
    }

    private function confirmMaintenance(mixed $target, string $txHash): bool
    {
        if (! $target instanceof Maintenance) {
            throw new RuntimeException('Cible maintenance invalide.');
        }

        $target->update([
            'transaction_hash' => $txHash,
            'synced_onchain_at' => now(),
            'blockchain_status' => 'confirmed',
            'status' => 'certified',
        ]);

        if ($target->mileage > $target->vehicle->last_certified_mileage) {
            $target->vehicle->update([
                'last_certified_mileage' => $target->mileage,
                'mileage_certified_at' => now(),
            ]);
        }

        return true;
    }

    private function confirmSale(mixed $target, string $txHash): bool
    {
        if (! $target instanceof VehicleSale) {
            throw new RuntimeException('Cible vente invalide.');
        }

        $target->update([
            'status' => 'completed',
            'buyer_signed_at' => now(),
            'acceptance_confirmed_at' => now(),
            'completed_at' => now(),
            'acceptance_transaction_hash' => $txHash,
            'transaction_hash' => $txHash,
        ]);
        $target->vehicle->update([
            'status' => 'sold',
            'current_driver_id' => null,
            'assigned_at' => null,
        ]);

        return true;
    }
}
