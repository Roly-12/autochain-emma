<?php

namespace App\Services\Blockchain;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class EthereumRpcService
{
    public function transaction(string $hash): ?array
    {
        return $this->call('eth_getTransactionByHash', [$hash]);
    }

    public function receipt(string $hash): ?array
    {
        return $this->call('eth_getTransactionReceipt', [$hash]);
    }

    public function chainId(): int
    {
        return hexdec($this->call('eth_chainId'));
    }

    private function call(string $method, array $parameters = []): mixed
    {
        $response = Http::timeout((int) config('blockchain.timeout', 30))
            ->post(config('blockchain.node_url'), [
                'jsonrpc' => '2.0',
                'id' => random_int(1, PHP_INT_MAX),
                'method' => $method,
                'params' => $parameters,
            ])
            ->throw()
            ->json();

        if (isset($response['error'])) {
            throw new RuntimeException($response['error']['message'] ?? 'Erreur JSON-RPC Ethereum.');
        }

        return $response['result'] ?? null;
    }
}
