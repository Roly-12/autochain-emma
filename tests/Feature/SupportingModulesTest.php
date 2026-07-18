<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\FuelLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Services\AlertEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupportingModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_chauffeur_can_add_fuel_only_to_assigned_vehicle(): void
    {
        $chauffeur = User::factory()->create(['role' => UserRole::Chauffeur]);
        $assigned = $this->vehicle(['current_driver_id' => $chauffeur->id]);
        $other = $this->vehicle();

        $payload = [
            'filled_at' => now()->toDateString(),
            'liters' => 20,
            'odometer' => 100,
        ];

        $this->actingAs($chauffeur)
            ->post('/fuel', [...$payload, 'vehicle_id' => $assigned->id])
            ->assertRedirect();
        $this->actingAs($chauffeur)
            ->post('/fuel', [...$payload, 'vehicle_id' => $other->id])
            ->assertForbidden();
    }

    public function test_consumption_uses_distance_between_ordered_fillups(): void
    {
        $vehicle = $this->vehicle();
        FuelLog::create([
            'vehicle_id' => $vehicle->id,
            'filled_at' => '2026-01-01',
            'liters' => 10,
            'odometer' => 100,
        ]);
        FuelLog::create([
            'vehicle_id' => $vehicle->id,
            'filled_at' => '2026-01-02',
            'liters' => 8,
            'odometer' => 200,
        ]);

        $this->assertSame(8.0, $vehicle->averageConsumption());
    }

    public function test_alert_generation_is_idempotent(): void
    {
        Notification::fake();
        $vehicle = $this->vehicle(['next_maintenance_date' => now()->toDateString()]);
        $service = app(AlertEngineService::class);

        $this->assertSame(1, $service->syncVehicle($vehicle));
        $this->assertSame(0, $service->syncVehicle($vehicle));
        $this->assertDatabaseCount('fleet_alerts', 1);
    }

    public function test_tampered_document_is_not_downloaded(): void
    {
        Storage::fake('local');
        $manager = User::factory()->create(['role' => UserRole::GestionnaireParc]);
        $vehicle = $this->vehicle();
        Storage::disk('local')->put('documents/test.pdf', 'modified');
        $document = VehicleDocument::create([
            'vehicle_id' => $vehicle->id,
            'type' => 'assurance',
            'title' => 'Document',
            'file_path' => 'documents/test.pdf',
            'content_hash' => hash('sha256', 'original'),
            'is_public' => false,
        ]);

        $this->actingAs($manager)
            ->get(route('documents.download', $document))
            ->assertStatus(409);
        $this->assertDatabaseHas('document_access_logs', [
            'vehicle_document_id' => $document->id,
            'integrity_valid' => false,
        ]);
    }

    private function vehicle(array $attributes = []): Vehicle
    {
        return Vehicle::create([
            'blockchain_vehicle_id' => (string) Str::uuid(),
            'license_plate' => 'TEST-'.Str::upper(Str::random(7)),
            'vin' => 'VIN'.Str::upper(Str::random(14)),
            'brand' => 'Test',
            'model' => 'Model',
            'year' => 2025,
            'fuel_type' => 'electric',
            'status' => 'available',
            ...$attributes,
        ]);
    }
}
