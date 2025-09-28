<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Default Blockchain Storage Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default blockchain storage provider that will
    | be used for premium users. You may change this to any of the supported
    | providers: 'arweave'
    |
    */

    'default' => env('BLOCKCHAIN_STORAGE_DEFAULT', 'arweave'),

    /*
    |--------------------------------------------------------------------------
    | Blockchain Storage Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the blockchain storage providers for your
    | decentralized networks like IPFS.
    |
    */

    'providers' => [
        'arweave' => [
            'name' => 'Arweave (Permanent Storage)',
            'enabled' => env('ARWEAVE_ENABLED', true), // Enabled by default
            'gateway_url' => env('ARWEAVE_GATEWAY_URL', 'https://arweave.net'),
            'node_url' => env('ARWEAVE_NODE_URL', 'https://arweave.net'),
            'max_file_size' => env('ARWEAVE_MAX_FILE_SIZE', 104857600), // 100MB in bytes
            'pricing' => [
                'currency' => 'USD',
                'per_gb_onetime' => 2.13, // One-time payment for permanent storage
                'free_tier_gb' => 0,
                'min_cost_usd' => 0.01, // Minimum cost
            ],
            'features' => [
                'permanent_storage' => true,
                'immutable' => true,
                'decentralized' => true,
                'censorship_resistant' => true,
            ]
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Premium Features
    |--------------------------------------------------------------------------
    |
    | Configuration for premium blockchain storage features
    |
    */

    'premium' => [
        'enabled' => env('BLOCKCHAIN_PREMIUM_ENABLED', true),
        'require_premium' => env('BLOCKCHAIN_REQUIRE_PREMIUM', true),
        'auto_backup_to_blockchain' => env('BLOCKCHAIN_AUTO_BACKUP', false),
        'max_monthly_uploads' => env('BLOCKCHAIN_MAX_MONTHLY_UPLOADS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Model Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for profit margins and pricing
    |
    */

    'profit_margin' => env('BLOCKCHAIN_PROFIT_MARGIN', 25.0), // 25% profit margin
    'minimum_charge_usd' => env('BLOCKCHAIN_MIN_CHARGE', 1.00), // $1 minimum charge
    'processing_fee_usd' => env('BLOCKCHAIN_PROCESSING_FEE', 0.50), // $0.50 processing fee

    /*
    |--------------------------------------------------------------------------
    | IPFS Settings
    |--------------------------------------------------------------------------
    |
    | General IPFS configuration settings
    |
    */

    'ipfs' => [
        'public_gateways' => [
            'https://ipfs.io/ipfs/',
            'https://gateway.ipfs.io/ipfs/',
            'https://cloudflare-ipfs.com/ipfs/',
            'https://dweb.link/ipfs/',
        ],
        'timeout' => env('IPFS_TIMEOUT', 30), // seconds
        'retry_attempts' => env('IPFS_RETRY_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Type Support
    |--------------------------------------------------------------------------
    |
    | Define which file types are supported for blockchain storage
    |
    */

    'supported_file_types' => [
        // Documents
        'pdf', 'doc', 'docx', 'txt', 'rtf', 'odt',
        
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp',
        
        // Videos
        'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm',
        
        // Audio
        'mp3', 'wav', 'flac', 'aac', 'ogg',
        
        // Archives
        'zip', 'rar', '7z', 'tar', 'gz',
        
        // Spreadsheets
        'xls', 'xlsx', 'csv', 'ods',
        
        // Presentations  
        'ppt', 'pptx', 'odp',
        
        // Code files
        'js', 'html', 'css', 'php', 'py', 'json', 'xml',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security configurations for blockchain storage
    |
    */

    'security' => [
        'encrypt_api_keys' => true,
        'hash_verification' => true,
        'secure_headers' => true,
        'rate_limiting' => [
            'uploads_per_minute' => env('BLOCKCHAIN_RATE_LIMIT', 10),
            'max_concurrent_uploads' => env('BLOCKCHAIN_MAX_CONCURRENT', 3),
        ]
    ]

];
