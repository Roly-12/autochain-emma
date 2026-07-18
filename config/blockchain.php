<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration Blockchain AutoChain Emma+
    |--------------------------------------------------------------------------
    */

    // Réseau blockchain (localhost pour développement)
    'network' => env('BLOCKCHAIN_NETWORK', 'localhost'),
    'chain_id' => (int) env('BLOCKCHAIN_CHAIN_ID', 31337),

    // Le fichier de déploiement Hardhat est réservé au développement local.
    // En production, l'adresse, l'ABI et le réseau doivent venir de l'environnement.
    'load_deployment_file' => (bool) env(
        'BLOCKCHAIN_LOAD_DEPLOYMENT_FILE',
        env('APP_ENV', 'production') === 'local'
    ),
    'deployment_file' => env(
        'BLOCKCHAIN_DEPLOYMENT_FILE',
        base_path('blockchain/deployment.json')
    ),

    // URL du nœud Ethereum
    'node_url' => env('BLOCKCHAIN_NODE_URL', 'http://127.0.0.1:8545'),

    // Adresse du Smart Contract VehicleRegistry
    'contract_address' => env('BLOCKCHAIN_CONTRACT_ADDRESS', ''),
    'admin_address' => env('BLOCKCHAIN_ADMIN_ADDRESS', ''),

    // ABI JSON du contrat (ou deployment.json en développement local)
    'contract_abi' => env('VEHICLE_REGISTRY_ABI', ''),
    'contract_abi_file' => env(
        'BLOCKCHAIN_ABI_FILE',
        base_path('blockchain/deployment.json')
    ),

    // Timeout des transactions (en secondes)
    'timeout' => env('BLOCKCHAIN_TIMEOUT', 30),
];