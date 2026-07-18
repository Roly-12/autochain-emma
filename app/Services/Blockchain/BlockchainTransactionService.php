<?php

namespace App\Services\Blockchain;

use App\Models\BlockchainTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;

class BlockchainTransactionService
{
    public function prepare(
        Model $target,
        User $user,
        string $action,
        string $expectedEvent,
        string $method,
        array $arguments,
        array $certifiedPayload = []
    ): BlockchainTransaction {
        $this->assertReady($user, $action);

        return BlockchainTransaction::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'transactionable_type' => $target->getMorphClass(),
            'transactionable_id' => $target->getKey(),
            'action' => $action,
            'expected_event' => $expectedEvent,
            'wallet_address' => strtolower($user->wallet_address),
            'chain_id' => (int) config('blockchain.chain_id'),
            'status' => 'pending',
            'payload' => [
                'method' => $method,
                'arguments' => $arguments,
                'certified' => $certifiedPayload,
            ],
        ]);
    }

    public function assertReady(User $user, string $action): void
    {
        if (! $user->wallet_verified_at || ! $user->wallet_address) {
            throw new RuntimeException('Liez et vérifiez votre wallet MetaMask avant de signer.');
        }

        if (! config('blockchain.contract_address')) {
            throw new RuntimeException('Le contrat blockchain n’est pas déployé sur ce réseau.');
        }

        $adminActions = [
            'register_vehicle',
            'assign_driver',
            'update_status',
            'propose_sale',
            'cancel_sale',
            'certify_garage',
        ];
        if (in_array($action, $adminActions, true)) {
            $adminAddress = strtolower((string) config('blockchain.admin_address'));
            if (! $adminAddress || ! hash_equals($adminAddress, strtolower($user->wallet_address))) {
                throw new RuntimeException('Le wallet connecté doit être l’administrateur du contrat.');
            }
        }
    }
}
