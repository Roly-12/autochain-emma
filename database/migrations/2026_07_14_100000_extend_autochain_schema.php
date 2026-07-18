<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'transaction_hash')) {
                $table->string('transaction_hash')->nullable()->after('next_maintenance_mileage');
            }
        });

        Schema::table('maintenances', function (Blueprint $table) {
            if (! Schema::hasColumn('maintenances', 'mileage')) {
                $table->unsignedInteger('mileage')->nullable()->after('details');
            }
            if (! Schema::hasColumn('maintenances', 'parts_changed')) {
                $table->text('parts_changed')->nullable()->after('mileage');
            }
            if (! Schema::hasColumn('maintenances', 'garage_user_id')) {
                $table->foreignId('garage_user_id')->nullable()->after('parts_changed')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('maintenances', 'maintenance_hash')) {
                $table->string('maintenance_hash')->nullable()->after('garage_user_id');
            }
            if (! Schema::hasColumn('maintenances', 'transaction_hash')) {
                $table->string('transaction_hash')->nullable()->after('maintenance_hash');
            }
            if (! Schema::hasColumn('maintenances', 'synced_onchain_at')) {
                $table->timestamp('synced_onchain_at')->nullable()->after('transaction_hash');
            }
        });

        Schema::create('vehicle_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['carte_grise', 'assurance', 'facture', 'controle_technique', 'certificat_inspection', 'autre']);
            $table->string('title');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('content_hash', 64);
            $table->string('ipfs_cid')->nullable();
            $table->boolean('is_public')->default(false);
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('filled_at');
            $table->decimal('liters', 8, 2);
            $table->decimal('amount', 10, 2)->nullable();
            $table->unsignedInteger('odometer');
            $table->string('station')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fleet_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['technical_control', 'insurance_renewal', 'maintenance_due', 'oil_change', 'custom']);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->string('title');
            $table->text('message')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['resolved_at', 'due_date']);
        });

        Schema::create('vehicle_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->string('buyer_wallet', 42)->nullable();
            $table->enum('status', ['pending', 'admin_signed', 'buyer_signed', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('admin_signed_at')->nullable();
            $table->timestamp('buyer_signed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('transaction_hash')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('mileage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('odometer');
            $table->enum('context', ['trip_end', 'maintenance', 'manual', 'assignment'])->default('manual');
            $table->text('notes')->nullable();
            $table->string('transaction_hash')->nullable();
            $table->timestamp('synced_onchain_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mileage_logs');
        Schema::dropIfExists('vehicle_sales');
        Schema::dropIfExists('fleet_alerts');
        Schema::dropIfExists('fuel_logs');
        Schema::dropIfExists('vehicle_documents');

        Schema::table('maintenances', function (Blueprint $table) {
            $columns = ['mileage', 'parts_changed', 'garage_user_id', 'maintenance_hash', 'transaction_hash', 'synced_onchain_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('maintenances', $column)) {
                    if ($column === 'garage_user_id') {
                        $table->dropConstrainedForeignId('garage_user_id');
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'transaction_hash')) {
                $table->dropColumn('transaction_hash');
            }
        });
    }
};
