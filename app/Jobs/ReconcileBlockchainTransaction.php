<?php

namespace App\Jobs;

use App\Models\BlockchainTransaction;
use App\Services\Blockchain\BlockchainReconciler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReconcileBlockchainTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 8;

    public array $backoff = [10, 20, 40, 80, 160, 300, 600];

    public function __construct(public readonly int $transactionId)
    {
    }

    public function handle(BlockchainReconciler $reconciler): void
    {
        $transaction = BlockchainTransaction::find($this->transactionId);

        if (! $transaction || in_array($transaction->status, ['confirmed', 'failed'], true)) {
            return;
        }

        if (! $reconciler->reconcile($transaction) && $transaction->fresh()->status === 'submitted') {
            $this->release($this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)]);
        }
    }
}
