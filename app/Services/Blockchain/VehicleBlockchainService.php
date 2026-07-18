<?php

namespace App\Services\Blockchain;

use Web3\Contract;
use Web3\Web3;
use RuntimeException;

class VehicleBlockchainService
{
    protected Web3 $web3;
    protected Contract $contract;
    protected string $contractAddress;

    public function __construct(Web3 $web3)
    {
        $this->web3 = $web3;
        $this->contract = app('vehicleRegistry.contract');
        $this->contractAddress = config('blockchain.contract_address');
    }

    /**
     * Récupère les données d'un véhicule depuis la blockchain
     */
    public function getVehicle(string $vehicleIdentifier): array
    {
        $vehicleHash = $this->generateVehicleHash($vehicleIdentifier);
        
        return $this->callMethod('getVehicle', [$vehicleHash]);
    }

    /**
     * Récupère l'historique de maintenance certifié
     */
    public function getMaintenanceHistory(string $vehicleIdentifier): array
    {
        $vehicleHash = $this->generateVehicleHash($vehicleIdentifier);
        
        return $this->callMethod('getMaintenanceHistory', [$vehicleHash]);
    }

    public function generateVehicleHash(string $identifier): string
    {
        return '0x' . hash('sha256', $identifier);
    }

    /**
     * Appelle une méthode (lecture blockchain)
     */
    protected function callMethod(string $method, array $params): array
    {
        $result = null;
        $error = null;

        $callArgs = [$method];
        foreach ($params as $param) {
            $callArgs[] = $param;
        }
        $callArgs[] = function ($err, $res) use (&$result, &$error) {
            if ($err !== null) {
                $error = $err;
            } else {
                $result = $res;
            }
        };

        $this->contract->at($this->contractAddress)->call(...$callArgs);

        // Attendre le résultat
        $timeout = 10;
        $startTime = time();
        
        while ($result === null && $error === null) {
            if (time() - $startTime > $timeout) {
                throw new RuntimeException('Timeout de l\'appel blockchain');
            }
            usleep(100000);
        }

        if ($error) {
            throw new RuntimeException('Erreur blockchain: ' . $error->getMessage());
        }

        return $result;
    }
}