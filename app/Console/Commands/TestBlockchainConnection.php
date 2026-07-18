<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Blockchain\VehicleBlockchainService;

class TestBlockchainConnection extends Command
{
    protected $signature = 'blockchain:test';
    protected $description = 'Teste la connexion entre Laravel et la Blockchain';

    public function handle(VehicleBlockchainService $blockchainService): int
    {
        $this->info('🔗 Test de connexion Blockchain...');
        
        try {
            // Test 1: Enregistrement d'un véhicule test
            $this->info('📝 Test 1: Enregistrement véhicule test...');
            $vehicleId = 'VEH-TEST-' . uniqid();
            $txHash = $blockchainService->registerVehicle($vehicleId);
            $this->info("✅ Véhicule enregistré! Tx: {$txHash}");
            
            // Test 2: Mise à jour kilométrage
            $this->info('📝 Test 2: Mise à jour kilométrage...');
            $txHash = $blockchainService->updateMileage($vehicleId, 15000);
            $this->info("✅ Kilométrage mis à jour: 15000 km");
            
            // Test 3: Récupération données véhicule
            $this->info('📝 Test 3: Lecture données blockchain...');
            $vehicle = $blockchainService->getVehicle($vehicleId);
            $this->info("✅ Données véhicule récupérées");
            $this->table(
                ['Propriété', 'Valeur'],
                [
                    ['Hash', $vehicle[0] ?? 'N/A'],
                    ['Kilométrage', $vehicle[1] ?? 'N/A'],
                    ['Propriétaire', $vehicle[4] ?? 'N/A'],
                ]
            );
            
            // Test 4: Enregistrement maintenance
            $this->info('📝 Test 4: Enregistrement maintenance...');
            $txHash = $blockchainService->recordMaintenance(
                $vehicleId,
                'Vidange - Filtre huile - 15000km',
                15000
            );
            $this->info("✅ Maintenance enregistrée!");
            
            // Test 5: Historique maintenance
            $this->info('📝 Test 5: Récupération historique...');
            $history = $blockchainService->getMaintenanceHistory($vehicleId);
            $this->info("✅ Historique récupéré: " . count($history) . " entrée(s)");
            
            $this->newLine();
            $this->info('🎉 TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS!');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}