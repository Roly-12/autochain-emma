<?php

namespace App\Console\Commands;

use App\Models\BlockchainTransaction;
use App\Services\Blockchain\BlockchainReconciler;
use Illuminate\Console\Command;

class ReconcileBlockchain extends Command
{
    protected $signature = 'blockchain:reconcile {--limit=100}';

    protected $description = 'Réconcilie les transactions soumises avec leurs receipts Ethereum';

    public function handle(BlockchainReconciler $reconciler): int
    {
        $transactions = BlockchainTransaction::query()
            ->where('status', 'submitted')
            ->oldest('submitted_at')
            ->limit((int) $this->option('limit'))
            ->get();

        $confirmed = 0;
        foreach ($transactions as $transaction) {
            $confirmed += $reconciler->reconcile($transaction) ? 1 : 0;
        }

        $this->info("{$confirmed}/{$transactions->count()} transaction(s) confirmée(s).");

        return self::SUCCESS;
    }
}
