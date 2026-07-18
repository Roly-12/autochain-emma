<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'wallet_address')) {
                $table->string('wallet_address', 42)->nullable()->unique();
            }
            if (! Schema::hasColumn('users', 'onchain_identifier')) {
                $table->uuid('onchain_identifier')->nullable()->unique();
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['super_admin', 'gestionnaire_parc', 'chauffeur', 'garagiste_agree', 'auditeur'])->default('auditeur');
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (! Schema::hasColumn('users', 'is_verified_onchain')) {
                $table->boolean('is_verified_onchain')->default(false);
            }
            if (! Schema::hasColumn('users', 'last_onchain_activity')) {
                $table->timestamp('last_onchain_activity')->nullable();
            }
            if (! Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number')->nullable();
            }
            if (! Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name')->nullable();
            }
            if (! Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable();
            }
            if (! Schema::hasColumn('users', 'theme_preference')) {
                $table->string('theme_preference')->default('system');
            }
            if (! Schema::hasColumn('users', 'notification_email')) {
                $table->boolean('notification_email')->default(true);
            }
            if (! Schema::hasColumn('users', 'mfa_enabled')) {
                $table->boolean('mfa_enabled')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'wallet_address',
                'onchain_identifier',
                'role',
                'is_active',
                'is_verified_onchain',
                'last_onchain_activity',
                'phone_number',
                'company_name',
                'bio',
                'theme_preference',
                'notification_email',
                'mfa_enabled',
            ]);
        });
    }
};
