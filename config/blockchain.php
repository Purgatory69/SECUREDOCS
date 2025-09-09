<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Default Blockchain Storage Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default blockchain storage provider that will
    | be used for premium users. You may change this to any of the supported
    | providers: 'pinata', 'storj', 'arweave'
    |
    */

    'default' => env('BLOCKCHAIN_STORAGE_DEFAULT', 'pinata'),

    /*
    |--------------------------------------------------------------------------
    | Blockchain Storage Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the blockchain storage providers for your
    | application. These providers will be used to store files on
    | decentralized networks like IPFS.
    |
    */

    'providers' => [
        
        'pinata' => [
            'name' => 'Pinata (IPFS)',
            'api_key' => env('PINATA_API_KEY'),
            'api_secret' => env('PINATA_API_SECRET'),
            'gateway_url' => env('PINATA_GATEWAY_URL', 'https://gateway.pinata.cloud'),
            'api_url' => env('PINATA_API_URL', 'https://api.pinata.cloud'),
            'enabled' => env('PINATA_ENABLED', true),
            'max_file_size' => env('PINATA_MAX_FILE_SIZE', 104857600), // 100MB in bytes
            'pricing' => [
                'currency' => 'USD',
                'per_gb_monthly' => 20.00, // Pinata pricing
                'free_tier_gb' => 1.0,
            ]
        ],


        'storj' => [
            'name' => 'Storj DCS',
            'enabled' => env('STORJ_ENABLED', false),
            'access_key' => env('STORJ_ACCESS_KEY'),
            'secret_key' => env('STORJ_SECRET_KEY'),
            'endpoint' => env('STORJ_ENDPOINT', 'https://gateway.storjshare.io'),
            'pricing' => [
                'currency' => 'USD',
                'per_gb_monthly' => 4.00,
                'free_tier_gb' => 0,
            ]
        ],

        'arweave' => [
            'name' => 'Arweave (Permanent Storage)',
            'enabled' => env('ARWEAVE_ENABLED', false),
            'gateway_url' => env('ARWEAVE_GATEWAY_URL', 'https://arweave.net'),
            'pricing' => [
                'currency' => 'USD',
                'per_gb_onetime' => 2.13, // One-time payment for permanent storage
                'free_tier_gb' => 0,
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
