<?php

namespace Tests\Feature;

use App\Models\BlockchainTransaction;
use App\Models\MileageLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Blockchain\BlockchainReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use kornrunner\Keccak;
use Tests\TestCase;

class BlockchainReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_certified_mileage_changes_only_after_valid_receipt_and_event(): void
    {
        $contract = '0x0000000000000000000000000000000000000010';
        $wallet = '0x0000000000000000000000000000000000000020';
        $txHash = '0x'.str_repeat('a', 64);
        $vehicleUuid = '11111111-1111-4111-8111-111111111111';
        $vehicleHash = '0x'.hash('sha256', $vehicleUuid);
        $input = '0x'.substr(Keccak::hash('updateMileage(bytes32,uint256)', 256), 0, 8)
            .substr($vehicleHash, 2)
            .str_pad(dechex(150), 64, '0', STR_PAD_LEFT);
        $eventTopic = '0x'.Keccak::hash('MileageUpdated(bytes32,uint256,address)', 256);
        config([
            'blockchain.node_url' => 'http://rpc.test',
            'blockchain.chain_id' => 31337,
            'blockchain.contract_address' => $contract,
            'blockchain.contract_abi' => json_encode([
                [
                    'type' => 'event',
                    'name' => 'MileageUpdated',
                    'inputs' => [
                        ['type' => 'bytes32', 'indexed' => true],
                        ['type' => 'uint256', 'indexed' => false],
                        ['type' => 'address', 'indexed' => true],
                    ],
                ],
                [
                    'type' => 'function',
                    'name' => 'updateMileage',
                    'inputs' => [
                        ['type' => 'bytes32'],
                        ['type' => 'uint256'],
                    ],
                ],
            ]),
        ]);

        Http::fake(function (Request $request) use ($contract, $wallet, $eventTopic, $input) {
            return Http::response([
                'jsonrpc' => '2.0',
                'id' => $request['id'],
                'result' => match ($request['method']) {
                    'eth_chainId' => '0x7a69',
                    'eth_getTransactionByHash' => ['from' => $wallet, 'to' => $contract, 'input' => $input],
                    'eth_getTransactionReceipt' => [
                        'status' => '0x1',
                        'blockNumber' => '0x10',
                        'logs' => [[
                            'address' => $contract,
                            'topics' => [$eventTopic],
                            'data' => '0x',
                            'logIndex' => '0x0',
                            'blockNumber' => '0x10',
                        ]],
                    ],
                },
            ]);
        });

        $user = User::factory()->create();
        $vehicle = Vehicle::create([
            'blockchain_vehicle_id' => $vehicleUuid,
            'license_plate' => 'BC-TEST',
            'vin' => 'VIN-BLOCKCHAIN-TEST',
            'brand' => 'Test',
            'model' => 'Chain',
            'year' => 2025,
            'fuel_type' => 'electric',
            'last_certified_mileage' => 100,
        ]);
        $log = MileageLog::create([
            'vehicle_id' => $vehicle->id,
            'recorded_by' => $user->id,
            'odometer' => 150,
            'context' => 'manual',
            'blockchain_status' => 'submitted',
        ]);
        $transaction = BlockchainTransaction::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'transactionable_type' => $log->getMorphClass(),
            'transactionable_id' => $log->id,
            'action' => 'update_mileage',
            'expected_event' => 'MileageUpdated',
            'wallet_address' => $wallet,
            'transaction_hash' => $txHash,
            'chain_id' => 31337,
            'status' => 'submitted',
            'payload' => [
                'method' => 'updateMileage',
                'arguments' => [$vehicleHash, 150],
                'certified' => ['odometer' => 150],
            ],
        ]);

        $this->assertTrue(app(BlockchainReconciler::class)->reconcile($transaction));
        $this->assertSame('confirmed', $log->fresh()->blockchain_status);
        $this->assertSame(150, $vehicle->fresh()->last_certified_mileage);
        $this->assertDatabaseHas('blockchain_events', ['event_name' => 'MileageUpdated']);
    }
}
