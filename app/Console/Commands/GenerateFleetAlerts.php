<?php

namespace App\Console\Commands;

use App\Services\AlertEngineService;
use Illuminate\Console\Command;

class GenerateFleetAlerts extends Command
{
    protected $signature = 'fleet:generate-alerts {--days=30 : Horizon d\'alerte en jours}';

    protected $description = 'Génère les alertes CT / assurance / entretien pour le parc';

    public function handle(AlertEngineService $engine): int
    {
        $created = $engine->syncAll((int) $this->option('days'));
        $this->info("{$created} alerte(s) créée(s).");

        return self::SUCCESS;
    }
}
