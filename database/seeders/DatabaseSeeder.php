<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@autochain.test',
                'role' => UserRole::SuperAdmin,
            ],
            [
                'name' => 'Gestionnaire Parc',
                'email' => 'parc@autochain.test',
                'role' => UserRole::GestionnaireParc,
            ],
            [
                'name' => 'Chauffeur Emma',
                'email' => 'chauffeur@autochain.test',
                'role' => UserRole::Chauffeur,
            ],
            [
                'name' => 'Garagiste Agréé',
                'email' => 'garage@autochain.test',
                'role' => UserRole::GaragisteAgree,
            ],
            [
                'name' => 'Auditeur Acheteur',
                'email' => 'auditeur@autochain.test',
                'role' => UserRole::Auditeur,
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'password',
                    'role' => $data['role'],
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'onchain_identifier' => Str::uuid()->toString(),
                ]
            );
        }

        if (Vehicle::count() === 0) {
            Vehicle::create([
                'blockchain_vehicle_id' => Str::uuid()->toString(),
                'license_plate' => 'AA-001-EM',
                'vin' => 'VF1AUTOCHAIN00001',
                'brand' => 'Renault',
                'model' => 'Megane',
                'year' => 2022,
                'fuel_type' => 'essence',
                'status' => 'available',
                'last_certified_mileage' => 0,
                'mileage_certified_at' => null,
                'current_driver_id' => null,
                'assigned_at' => null,
                'blockchain_status' => 'pending',
                'technical_control_deadline' => now()->addDays(20)->toDateString(),
                'insurance_expiry' => now()->addDays(40)->toDateString(),
                'next_maintenance_date' => now()->addDays(10)->toDateString(),
                'next_maintenance_mileage' => 50000,
            ]);
        }

        $this->command?->info('Comptes démo (mot de passe: password):');
        $this->command?->info('admin@autochain.test | parc@autochain.test | chauffeur@autochain.test | garage@autochain.test | auditeur@autochain.test');
    }
}
