<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('vehicles')
            ->where('blockchain_status', 'pending')
            ->update([
                'last_certified_mileage' => 0,
                'mileage_certified_at' => null,
            ]);

        DB::table('maintenances')
            ->where('blockchain_status', 'pending')
            ->update([
                'status' => 'pending',
                'synced_onchain_at' => null,
            ]);

        DB::table('mileage_logs')
            ->where('blockchain_status', 'pending')
            ->update(['synced_onchain_at' => null]);

        DB::table('vehicle_sales')
            ->where('status', 'completed')
            ->whereNull('proposal_transaction_hash')
            ->orWhere(function ($query) {
                $query->where('status', 'completed')
                    ->whereNull('acceptance_transaction_hash');
            })
            ->update([
                'status' => 'pending',
                'admin_signed_at' => null,
                'buyer_signed_at' => null,
                'completed_at' => null,
            ]);
    }

    public function down(): void
    {
        // Les anciennes valeurs présentées à tort comme certifiées ne sont pas restaurées.
    }
};
