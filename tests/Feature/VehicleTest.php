<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BlockchainTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_creates_pending_vehicle_transaction_for_metamask(): void
    {
        config([
            'blockchain.contract_address' => '0x0000000000000000000000000000000000000001',
            'blockchain.admin_address' => '0x0000000000000000000000000000000000000002',
            'blockchain.chain_id' => 31337,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'wallet_address' => '0x0000000000000000000000000000000000000002',
            'wallet_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/vehicles', [
            'license_plate' => 'ZZ-111-YY',
            'vin' => 'VIN1234567890',
            'brand' => 'TestBrand',
            'model' => 'T1',
            'year' => 2022,
            'fuel_type' => 'essence',
        ]);

        $this->assertDatabaseHas('vehicles', ['license_plate' => 'ZZ-111-YY']);
        $this->assertDatabaseHas('blockchain_transactions', [
            'action' => 'register_vehicle',
            'status' => 'pending',
            'user_id' => $user->id,
        ]);
        $response->assertRedirect(route(
            'blockchain.transactions.show',
            BlockchainTransaction::first()
        ));
    }
}
