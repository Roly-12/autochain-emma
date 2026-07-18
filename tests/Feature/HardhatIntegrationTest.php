<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('hardhat')]
class HardhatIntegrationTest extends TestCase
{
    public function test_laravel_reads_the_deployed_contract_and_admin(): void
    {
        if (! filter_var(env('RUN_HARDHAT_TESTS', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('RUN_HARDHAT_TESTS n’est pas activé.');
        }

        $deployment = json_decode(file_get_contents(base_path('blockchain/deployment.json')), true);
        $rpc = config('blockchain.node_url');

        $chain = $this->rpc($rpc, 'eth_chainId');
        $code = $this->rpc($rpc, 'eth_getCode', [$deployment['address'], 'latest']);
        $adminResult = $this->rpc($rpc, 'eth_call', [[
            'to' => $deployment['address'],
            'data' => '0xf851a440',
        ], 'latest']);

        $this->assertSame((int) $deployment['chainId'], hexdec($chain));
        $this->assertNotSame('0x', $code);
        $this->assertSame(
            strtolower($deployment['deployer']),
            '0x'.substr(strtolower($adminResult), -40)
        );
    }

    private function rpc(string $url, string $method, array $parameters = []): mixed
    {
        return Http::post($url, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $parameters,
        ])->throw()->json('result');
    }
}
