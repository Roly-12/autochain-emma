<?php

namespace Tests\Unit;

use App\Providers\BlockchainServiceProvider;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class BlockchainConfigurationTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->app['env'] = 'testing';

        parent::tearDown();
    }

    public function test_deployment_file_is_rejected_outside_local_environment(): void
    {
        $this->app['env'] = 'production';
        config(['blockchain.load_deployment_file' => true]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('interdit hors environnement local');

        $this->invokeProviderMethod('loadDeploymentConfig');
    }

    public function test_valid_sepolia_configuration_is_accepted_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->configureSepolia();

        $this->invokeProviderMethod('validateDeploymentConfig');

        $this->addToAssertionCount(1);
    }

    public function test_network_and_chain_id_mismatch_is_rejected(): void
    {
        $this->app['env'] = 'production';
        $this->configureSepolia();
        config(['blockchain.chain_id' => 31337]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('incompatible avec le chain ID');

        $this->invokeProviderMethod('validateDeploymentConfig');
    }

    private function configureSepolia(): void
    {
        config([
            'blockchain.load_deployment_file' => false,
            'blockchain.network' => 'sepolia',
            'blockchain.chain_id' => 11155111,
            'blockchain.node_url' => 'https://ethereum-sepolia-rpc.publicnode.com',
            'blockchain.contract_address' => '0x1111111111111111111111111111111111111111',
            'blockchain.admin_address' => '0x2222222222222222222222222222222222222222',
            'blockchain.contract_abi' => json_encode([
                ['type' => 'constructor', 'inputs' => []],
            ], JSON_THROW_ON_ERROR),
        ]);
    }

    private function invokeProviderMethod(string $method): void
    {
        $provider = new BlockchainServiceProvider($this->app);
        $reflection = new ReflectionMethod($provider, $method);
        $reflection->invoke($provider);
    }
}
