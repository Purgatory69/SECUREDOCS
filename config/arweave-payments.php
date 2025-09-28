<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Arweave Payment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for real Arweave payments via Bundlr Network
    |
    */

    'bundlr' => [
        'enabled' => env('BUNDLR_ENABLED', true),
        'network' => env('BUNDLR_NETWORK', 'https://node1.bundlr.network'), // Production
        'dev_network' => env('BUNDLR_DEV_NETWORK', 'https://devnet.bundlr.network'), // Development
        'currency' => env('BUNDLR_CURRENCY', 'ethereum'), // ethereum, matic, solana, etc.
        'private_key' => env('BUNDLR_PRIVATE_KEY'), // Server wallet private key
        'min_balance' => env('BUNDLR_MIN_BALANCE', 0.01), // Minimum balance to maintain
    ],

    'pricing' => [
        // Service fee configuration
        'service_fee_percentage' => env('ARWEAVE_SERVICE_FEE_PERCENTAGE', 15), // 15% service fee
        'minimum_service_fee_usd' => env('ARWEAVE_MIN_SERVICE_FEE_USD', 0.10), // $0.10 minimum
        'maximum_service_fee_usd' => env('ARWEAVE_MAX_SERVICE_FEE_USD', 50.00), // $50.00 maximum
        
        // Payment methods
        'accept_crypto' => env('ARWEAVE_ACCEPT_CRYPTO', true),
        'accept_fiat' => env('ARWEAVE_ACCEPT_FIAT', false), // Future: Stripe integration
        
        // Pricing tiers
        'bulk_discount_threshold' => 10, // Files
        'bulk_discount_percentage' => 10, // 10% discount for bulk uploads
        
        // Free tier (if applicable)
        'free_tier_mb_per_month' => env('ARWEAVE_FREE_TIER_MB', 0), // 0 = no free tier
    ],

    'wallet' => [
        // Supported payment currencies
        'supported_currencies' => [
            'ETH' => [
                'name' => 'Ethereum',
                'symbol' => 'ETH',
                'decimals' => 18,
                'network' => 'ethereum',
                'enabled' => true,
            ],
            'MATIC' => [
                'name' => 'Polygon',
                'symbol' => 'MATIC', 
                'decimals' => 18,
                'network' => 'polygon',
                'enabled' => true,
            ],
            'SOL' => [
                'name' => 'Solana',
                'symbol' => 'SOL',
                'decimals' => 9,
                'network' => 'solana',
                'enabled' => false, // Enable when needed
            ],
        ],
        
        // Default payment currency
        'default_currency' => 'MATIC', // Cheapest option
    ],

    'limits' => [
        'max_file_size_mb' => 100, // 100MB per file
        'max_daily_uploads_per_user' => 50,
        'max_monthly_storage_gb_per_user' => 10, // 10GB per user per month
    ],

    'notifications' => [
        'low_balance_threshold_usd' => 100, // Notify when server balance < $100
        'notify_admin_email' => env('ARWEAVE_ADMIN_EMAIL', 'admin@securedocs.com'),
    ],
];
