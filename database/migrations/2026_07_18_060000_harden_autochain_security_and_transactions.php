<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('wallet_nonce', 64)->nullable();
            $table->timestamp('wallet_nonce_expires_at')->nullable();
            $table->timestamp('wallet_verified_at')->nullable();
        });

        Schema::create('blockchain_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('transactionable');
            $table->string('action', 80);
            $table->string('expected_event', 100);
            $table->string('wallet_address', 42);
            $table->string('transaction_hash', 66)->nullable()->unique();
            $table->unsignedBigInteger('chain_id');
            $table->enum('status', ['pending', 'submitted', 'confirmed', 'failed'])->default('pending');
            $table->json('payload')->nullable();
            $table->json('receipt')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('document_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('integrity_valid')->nullable();
            $table->timestamps();
        });

        Schema::create('blockchain_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('blockchain_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_hash', 66);
            $table->unsignedBigInteger('block_number');
            $table->unsignedInteger('log_index');
            $table->string('event_name', 100);
            $table->json('topics');
            $table->text('data')->nullable();
            $table->timestamps();

            $table->unique(['transaction_hash', 'log_index']);
            $table->index(['vehicle_id', 'block_number']);
        });

        Schema::table('vehicle_sales', function (Blueprint $table) {
            $table->string('proposal_transaction_hash', 66)->nullable();
            $table->string('acceptance_transaction_hash', 66)->nullable();
            $table->timestamp('proposal_confirmed_at')->nullable();
            $table->timestamp('acceptance_confirmed_at')->nullable();
        });

        Schema::table('fleet_alerts', function (Blueprint $table) {
            $table->string('fingerprint', 64)->nullable()->unique();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->enum('blockchain_status', ['pending', 'submitted', 'confirmed', 'failed'])
                ->default('pending');
        });

        Schema::table('maintenances', function (Blueprint $table) {
            $table->enum('blockchain_status', ['pending', 'submitted', 'confirmed', 'failed'])
                ->default('pending');
        });

        Schema::table('mileage_logs', function (Blueprint $table) {
            $table->enum('blockchain_status', ['pending', 'submitted', 'confirmed', 'failed'])
                ->default('pending');
        });
    }

    public function down(): void
    {
        Schema::table('mileage_logs', fn (Blueprint $table) => $table->dropColumn('blockchain_status'));
        Schema::table('maintenances', fn (Blueprint $table) => $table->dropColumn('blockchain_status'));
        Schema::table('vehicles', fn (Blueprint $table) => $table->dropColumn('blockchain_status'));

        Schema::table('vehicle_sales', function (Blueprint $table) {
            $table->dropColumn([
                'proposal_transaction_hash',
                'acceptance_transaction_hash',
                'proposal_confirmed_at',
                'acceptance_confirmed_at',
            ]);
        });

        Schema::table('fleet_alerts', fn (Blueprint $table) => $table->dropColumn('fingerprint'));

        Schema::dropIfExists('blockchain_events');
        Schema::dropIfExists('document_access_logs');
        Schema::dropIfExists('blockchain_transactions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wallet_nonce', 'wallet_nonce_expires_at', 'wallet_verified_at']);
        });
    }
};
