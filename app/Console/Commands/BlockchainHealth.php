<?php

namespace App\Console\Commands;

use App\Services\Blockchain\VehicleBlockchainService;
use Illuminate\Console\Command;

class BlockchainHealth extends Command
{
    protected $signature = 'blockchain:health';
    protected $description = 'Vérifie la connectivité au nœud blockchain et l\'état du contrat VehicleRegistry';

    public function handle(): int
    {
        $nodeUrl = config('blockchain.node_url');
        $contractAddress = config('blockchain.contract_address');

        $this->info("Node URL: {$nodeUrl}");

        // Simple JSON-RPC call to web3_clientVersion
        try {
            $payload = json_encode(['jsonrpc' => '2.0', 'method' => 'web3_clientVersion', 'params' => [], 'id' => 1]);
            $opts = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $payload,
                    'timeout' => (int) config('blockchain.timeout', 10),
                ],
            ]);

            $res = @file_get_contents($nodeUrl, false, $opts);
            if ($res === false) {
                $this->error('Impossible de joindre le nœud blockchain. Vérifiez BLOCKCHAIN_NODE_URL dans .env');
                return 1;
            }

            $data = json_decode($res, true);
            if (isset($data['result'])) {
                $this->info('Noeud répond: ' . $data['result']);
            } else {
                $this->warn('Réponse inattendue du nœud: ' . substr($res, 0, 200));
            }
        } catch (\Throwable $e) {
            $this->error('Erreur en contactant le nœud: ' . $e->getMessage());
            return 1;
        }

        if (empty($contractAddress)) {
            $this->error('Adresse du contrat introuvable (VEHICLE_REGISTRY_ADDRESS ou blockchain/deployment.json).');
            return 2;
        }

        $this->info('Adresse contrat: ' . $contractAddress);

        // Try a contract call via the service
        try {
            /** @var VehicleBlockchainService $service */
            $service = app(VehicleBlockchainService::class);
            $this->info('Tentative d\'appel de lecture du contrat (getVehicle) ...');
            $result = $service->getVehicle('health-check');
            $this->info('Appel contrat réussi. Result: ' . (is_array($result) ? json_encode($result) : (string) $result));
        } catch (\Throwable $e) {
            $this->error('Appel contrat échoué: ' . $e->getMessage());
            return 3;
        }

        $this->info('Vérification blockchain terminée avec succès.');
        return 0;
    }
}
