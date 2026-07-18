<?php
// database/migrations/xxxx_create_vehicles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            
            // Identifiant unique du véhicule sur la blockchain
            $table->uuid('blockchain_vehicle_id')->unique();
            
            // Informations administratives (stockées off-chain, hachées)
            $table->string('license_plate')->unique();
            $table->string('vin')->unique(); // Numéro de châssis
            $table->string('brand');
            $table->string('model');
            $table->integer('year');
            $table->string('fuel_type'); // essence, diesel, électrique, hybride
            
            // Statut synchronisé avec la blockchain
            $table->enum('status', [
                'available', 
                'in_mission', 
                'maintenance', 
                'broken', 
                'sold'
            ])->default('available');
            
            // Dernier kilométrage certifié (synchronisé on-chain)
            $table->integer('last_certified_mileage')->nullable();
            $table->timestamp('mileage_certified_at')->nullable();
            
            // Hash des documents (preuve d'intégrité)
            $table->string('registration_hash')->nullable(); // Carte grise
            $table->string('insurance_contract_hash')->nullable();
            
            // Assignation actuelle
            $table->foreignId('current_driver_id')->nullable()->constrained('users');
            $table->timestamp('assigned_at')->nullable();
            
            // Dates administratives pour alertes
            $table->date('technical_control_deadline')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->integer('next_maintenance_mileage')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Pour archivage sans suppression
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};