<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('type');
            $table->string('garage')->nullable();
            $table->date('date')->nullable();
            $table->text('details')->nullable();
            $table->string('status')->default('planned');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
