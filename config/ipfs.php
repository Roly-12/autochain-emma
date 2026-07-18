<?php

return [
    'enabled' => env('IPFS_ENABLED', false),
    'api_url' => env('IPFS_API_URL', 'http://127.0.0.1:5001'),
    'gateway_url' => env('IPFS_GATEWAY_URL', 'http://127.0.0.1:8080/ipfs'),
];
