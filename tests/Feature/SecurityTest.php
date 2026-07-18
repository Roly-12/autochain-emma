<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehicleSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_is_blocked_globally(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
        $this->assertGuest();
    }

    public function test_private_document_is_hidden_and_forbidden_to_auditor(): void
    {
        Storage::fake('local');
        $auditor = User::factory()->create(['role' => UserRole::Auditeur]);
        $vehicle = $this->vehicle();
        Storage::disk('local')->put('documents/private.pdf', 'secret');
        $document = VehicleDocument::create([
            'vehicle_id' => $vehicle->id,
            'type' => 'assurance',
            'title' => 'Contrat privé',
            'file_path' => 'documents/private.pdf',
            'content_hash' => hash('sha256', 'secret'),
            'is_public' => false,
        ]);

        $this->actingAs($auditor)
            ->get(route('documents.download', $document))
            ->assertForbidden();
    }

    public function test_only_designated_buyer_can_accept_sale(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $buyer = User::factory()->create(['role' => UserRole::Auditeur]);
        $other = User::factory()->create(['role' => UserRole::Auditeur]);
        $sale = VehicleSale::create([
            'vehicle_id' => $this->vehicle()->id,
            'initiated_by' => $admin->id,
            'buyer_id' => $buyer->id,
            'buyer_wallet' => '0x0000000000000000000000000000000000000001',
            'status' => 'admin_signed',
        ]);

        $this->actingAs($other)
            ->post(route('sales.sign-buyer', $sale))
            ->assertForbidden();
    }

    public function test_mfa_code_expires(): void
    {
        $user = User::factory()->create();

        $this->withSession([
            'mfa_code' => '123456',
            'mfa_user_id' => $user->id,
            'mfa_expires_at' => now()->subMinute()->timestamp,
        ])->post(route('mfa.verify.store'), ['code' => '123456'])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::create([
            'blockchain_vehicle_id' => (string) Str::uuid(),
            'license_plate' => 'TEST-'.Str::upper(Str::random(6)),
            'vin' => 'VIN'.Str::upper(Str::random(14)),
            'brand' => 'Test',
            'model' => 'Model',
            'year' => 2025,
            'fuel_type' => 'electric',
            'status' => 'available',
            'blockchain_status' => 'confirmed',
        ]);
    }
}
