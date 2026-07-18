<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Web3\Contract;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Web3;

class BlockchainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Web3::class, function ($app) {
            $nodeUrl = config('blockchain.node_url');
            $timeout = config('blockchain.timeout', 30);

            return new Web3(
                new HttpProvider(
                    new HttpRequestManager($nodeUrl, $timeout)
                )
            );
        });

        $this->app->singleton('vehicleRegistry.contract', function ($app) {
            $web3 = $app->make(Web3::class);
            $contractAddress = config('blockchain.contract_address');
            $abi = config('blockchain.contract_abi');

            if (empty($contractAddress) || empty($abi)) {
                throw new \RuntimeException('Configuration blockchain manquante. Vérifiez votre .env');
            }

            return new Contract($web3->provider, $abi);
        });
    }

    public function boot(): void
    {
        $this->loadDeploymentConfig();
        $this->loadContractAbi();
        $this->validateDeploymentConfig();
    }

    protected function loadDeploymentConfig(): void
    {
        if ($this->app->environment('testing')) {
            return;
        }

        if (! config('blockchain.load_deployment_file')) {
            return;
        }

        if (! $this->app->environment('local')) {
            throw new \RuntimeException(
                'BLOCKCHAIN_LOAD_DEPLOYMENT_FILE est interdit hors environnement local.'
            );
        }

        $deploymentPath = (string) config('blockchain.deployment_file');

        if (! is_file($deploymentPath)) {
            return;
        }

        $deployment = json_decode((string) file_get_contents($deploymentPath), true);
        $requiredKeys = ['address', 'abi', 'chainId', 'network', 'deployer'];

        if (! is_array($deployment) || array_diff($requiredKeys, array_keys($deployment))) {
            throw new \RuntimeException(
                'Le fichier de déploiement blockchain est invalide ou incomplet.'
            );
        }

        config([
            'blockchain.contract_address' => $deployment['address'],
            'blockchain.contract_abi' => json_encode($deployment['abi'], JSON_THROW_ON_ERROR),
            'blockchain.chain_id' => (int) $deployment['chainId'],
            'blockchain.network' => $deployment['network'],
            'blockchain.admin_address' => $deployment['deployer'],
        ]);
    }

    protected function loadContractAbi(): void
    {
        if (config('blockchain.contract_abi')) {
            return;
        }

        $abiPath = (string) config('blockchain.contract_abi_file');
        if (! is_file($abiPath)) {
            return;
        }

        $contents = json_decode((string) file_get_contents($abiPath), true);
        $abi = is_array($contents) && isset($contents['abi'])
            ? $contents['abi']
            : $contents;

        if (! is_array($abi) || $abi === []) {
            throw new \RuntimeException('Le fichier ABI blockchain est invalide.');
        }

        config(['blockchain.contract_abi' => json_encode($abi, JSON_THROW_ON_ERROR)]);
    }

    protected function validateDeploymentConfig(): void
    {
        if ($this->app->environment(['local', 'testing'])) {
            return;
        }

        $network = strtolower((string) config('blockchain.network'));
        $chainId = (int) config('blockchain.chain_id');
        $nodeUrl = (string) config('blockchain.node_url');
        $contractAddress = (string) config('blockchain.contract_address');
        $adminAddress = (string) config('blockchain.admin_address');
        $abi = json_decode((string) config('blockchain.contract_abi'), true);

        $required = [
            'BLOCKCHAIN_NETWORK' => $network,
            'BLOCKCHAIN_CHAIN_ID' => $chainId,
            'BLOCKCHAIN_NODE_URL' => $nodeUrl,
            'BLOCKCHAIN_CONTRACT_ADDRESS' => $contractAddress,
            'BLOCKCHAIN_ADMIN_ADDRESS' => $adminAddress,
            'VEHICLE_REGISTRY_ABI ou BLOCKCHAIN_ABI_FILE' => $abi,
        ];

        foreach ($required as $name => $value) {
            if ($value === '' || $value === 0 || $value === null || $value === []) {
                throw new \RuntimeException("Configuration blockchain manquante : {$name}.");
            }
        }

        $nodeScheme = strtolower((string) parse_url($nodeUrl, PHP_URL_SCHEME));
        if (! filter_var($nodeUrl, FILTER_VALIDATE_URL) || ! in_array($nodeScheme, ['http', 'https'], true)) {
            throw new \RuntimeException('BLOCKCHAIN_NODE_URL doit être une URL HTTP(S) valide.');
        }

        $nodeHost = strtolower((string) parse_url($nodeUrl, PHP_URL_HOST));
        if (in_array($nodeHost, ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new \RuntimeException('Un nœud blockchain local ne peut pas être utilisé hors environnement local.');
        }

        if (! preg_match('/^0x[a-fA-F0-9]{40}$/', $contractAddress)) {
            throw new \RuntimeException('BLOCKCHAIN_CONTRACT_ADDRESS est invalide.');
        }

        if (! preg_match('/^0x[a-fA-F0-9]{40}$/', $adminAddress)) {
            throw new \RuntimeException('BLOCKCHAIN_ADMIN_ADDRESS est invalide.');
        }

        if (! is_array($abi) || $abi === []) {
            throw new \RuntimeException('VEHICLE_REGISTRY_ABI doit contenir un ABI JSON valide.');
        }

        $knownNetworks = [
            'localhost' => 31337,
            'hardhat' => 31337,
            'sepolia' => 11155111,
            'mainnet' => 1,
        ];

        if (isset($knownNetworks[$network]) && $knownNetworks[$network] !== $chainId) {
            throw new \RuntimeException(
                "Le réseau {$network} est incompatible avec le chain ID {$chainId}."
            );
        }

        if ($chainId === 31337 || in_array($network, ['localhost', 'hardhat'], true)) {
            throw new \RuntimeException('La configuration Hardhat locale est interdite hors environnement local.');
        }
    }
}