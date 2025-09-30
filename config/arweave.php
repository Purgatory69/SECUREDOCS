<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Arweave Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Arweave permanent storage integration
    |
    */

    // Production mode - set to true for real Arweave uploads
    // Default to false for local development to avoid SSL issues
    'production_mode' => env('ARWEAVE_PRODUCTION_MODE', false),
    
    // Upload method: 'bundlr' or 'direct'
    'upload_method' => env('ARWEAVE_UPLOAD_METHOD', 'bundlr'),
    
    // Bundlr/Irys configuration
    'bundler_url' => env('BUNDLR_NETWORK', 'https://node1.bundlr.network'),
    'private_key' => env('BUNDLR_PRIVATE_KEY', ''),
    'currency' => env('BUNDLR_CURRENCY', 'matic'),
    
    // Direct Arweave configuration
    'arweave_private_key' => env('ARWEAVE_PRIVATE_KEY', ''),
    'arweave_wallet_address' => env('ARWEAVE_WALLET_ADDRESS', ''),
    
    // Arweave network settings
    'arweave_host' => env('ARWEAVE_HOST', 'arweave.net'),
    'arweave_port' => env('ARWEAVE_PORT', 443),
    'arweave_protocol' => env('ARWEAVE_PROTOCOL', 'https'),
    
    // Gateway URLs for file access
    'gateways' => [
        'primary' => 'https://arweave.net',
        'ar_io' => 'https://ar-io.net',
        'gateway_dev' => 'https://gateway.ar-io.dev',
        'viewblock' => 'https://viewblock.io/arweave/tx',
        'arweave_app' => 'https://arweave.app/tx'
    ],
    
    // Storage pricing (for cost calculation)
    'pricing' => [
        'ar_per_mb' => 0.005, // Approximate AR cost per MB
        'ar_to_usd_rate' => 25.50, // Mock rate - should fetch from API in production
        'service_fee_percentage' => 0, // No service fee as requested
    ],
    
    // File upload limits
    'limits' => [
        'max_file_size' => 100 * 1024 * 1024, // 100MB
        'allowed_mime_types' => [
            // Documents
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
            
            // Images
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            
            // Videos
            'video/mp4',
            'video/webm',
            'video/quicktime',
            
            // Audio
            'audio/mpeg',
            'audio/wav',
            'audio/ogg',
            
            // Archives
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            
            // Code
            'application/json',
            'application/xml',
            'text/html',
            'text/css',
            'text/javascript',
        ]
    ],
    
    // Metadata tags to include with uploads
    'default_tags' => [
        'App-Name' => 'SecureDocs',
        'App-Version' => '1.0.0',
        'Protocol' => 'ArFS-0.11', // ArDrive File System compatibility
    ],
    
    // Payment verification settings
    'payment' => [
        'verify_before_upload' => true,
        'supported_networks' => [
            'ethereum' => [
                'chain_id' => 1,
                'rpc_url' => 'https://mainnet.infura.io/v3/YOUR_KEY',
                'tokens' => ['USDC', 'ETH']
            ],
            'polygon' => [
                'chain_id' => 137,
                'rpc_url' => 'https://polygon-rpc.com',
                'tokens' => ['USDC', 'MATIC']
            ],
            'ronin' => [
                'chain_id' => 2020,
                'rpc_url' => 'https://api.roninchain.com/rpc',
                'tokens' => ['RON', 'AXS', 'SLP']
            ]
        ]
    ]
];
