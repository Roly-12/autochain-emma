<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('wallet_address', 42)->nullable()->unique();
            $table->uuid('onchain_identifier')->nullable()->unique();
            $table->enum('role', ['super_admin', 'gestionnaire_parc', 'chauffeur', 'garagiste_agree', 'auditeur'])->default('auditeur');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified_onchain')->default(false);
            $table->timestamp('last_onchain_activity')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('company_name')->nullable();
            $table->text('bio')->nullable();
            $table->string('theme_preference')->default('system');
            $table->boolean('notification_email')->default(true);
            $table->boolean('mfa_enabled')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};