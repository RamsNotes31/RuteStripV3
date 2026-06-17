<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend'   => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses'      => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack'    => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'python'   => [
        'path' => env('PYTHON_PATH', 'python'),
    ],

    'pinata'   => [
        'jwt'         => env('PINATA_JWT'),
        'api_url'     => env('PINATA_API_URL', 'https://api.pinata.cloud'),
        'gateway_url' => rtrim(env('IPFS_GATEWAY_URL', 'https://gateway.pinata.cloud/ipfs/'), '/') . '/',
    ],

    'blockchain' => [
        'rpc_url'          => env('BLOCKCHAIN_RPC_URL', 'http://127.0.0.1:8545'),
        'private_key'      => env('BLOCKCHAIN_PRIVATE_KEY'),
        'contract_address' => env('BLOCKCHAIN_CONTRACT_ADDRESS'),
        'network'          => env('BLOCKCHAIN_NETWORK', 'localhost'),
        'node_path'        => env('NODE_PATH', 'node'),
        'etherscan_api_key' => env('ETHERSCAN_API_KEY'),
        'etherscan_api_url' => env('ETHERSCAN_API_URL', 'https://api-sepolia.etherscan.io/api'),
    ],

];
