<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'avatar_path')) {
                $table->string('avatar_path')->nullable()->after('bio');
            }
            if (! Schema::hasColumn('users', 'company_logo_path')) {
                $table->string('company_logo_path')->nullable()->after('avatar_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter(['avatar_path', 'company_logo_path'], fn ($c) => Schema::hasColumn('users', $c));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
