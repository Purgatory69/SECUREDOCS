<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Crypto Payment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for cryptocurrency payments and wallet integration
    |
    */

    'enabled' => env('CRYPTO_PAYMENTS_ENABLED', true),
    
    // Your business wallet address (where payments are received)
    'payment_wallet_address' => env('CRYPTO_PAYMENT_WALLET', '0x742d35Cc6634C0532925a3b8D4C2C4e07C3c4526'),
    
    /*
    |--------------------------------------------------------------------------
    | Supported Networks
    |--------------------------------------------------------------------------
    */
    
    'supported_networks' => [
        'polygon' => [
            'name' => 'Polygon',
            'chain_id' => 137,
            'rpc_url' => env('POLYGON_RPC_URL', 'https://polygon-rpc.com'),
            'explorer' => 'https://polygonscan.com',
            'currency' => 'MATIC',
            'fees' => 'low', // Low transaction fees
        ],
        
        'ethereum' => [
            'name' => 'Ethereum',
            'chain_id' => 1,
            'rpc_url' => env('ETHEREUM_RPC_URL', 'https://mainnet.infura.io/v3/YOUR_KEY'),
            'explorer' => 'https://etherscan.io',
            'currency' => 'ETH',
            'fees' => 'high', // High transaction fees
        ],
        
        'ronin' => [
            'name' => 'Ronin',
            'chain_id' => 2020,
            'rpc_url' => env('RONIN_RPC_URL', 'https://api.roninchain.com/rpc'),
            'explorer' => 'https://explorer.roninchain.com',
            'currency' => 'RON',
            'fees' => 'very_low', // Very low transaction fees
        ],
        
        'bsc' => [
            'name' => 'Binance Smart Chain',
            'chain_id' => 56,
            'rpc_url' => env('BSC_RPC_URL', 'https://bsc-dataseed1.binance.org'),
            'explorer' => 'https://bscscan.com',
            'currency' => 'BNB',
            'fees' => 'low',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Tokens
    |--------------------------------------------------------------------------
    */
    
    'supported_tokens' => [
        'USDC' => [
            'name' => 'USD Coin',
            'symbol' => 'USDC',
            'decimals' => 6,
            'stable' => true,
            'recommended' => true,
            'addresses' => [
                'ethereum' => '0xA0b86a33E6441b8C0b7f6C4c8b6b6b6b6b6b6b6b',
                'polygon' => '0x2791Bca1f2de4661ED88A30C99A7a9449Aa84174',
                'bsc' => '0x8AC76a51cc950d9822D68b83fE1Ad97B32Cd580d',
            ]
        ],
        
        'USDT' => [
            'name' => 'Tether',
            'symbol' => 'USDT',
            'decimals' => 6,
            'stable' => true,
            'recommended' => false,
            'addresses' => [
                'ethereum' => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
                'polygon' => '0xc2132D05D31c914a87C6611C10748AEb04B58e8F',
                'bsc' => '0x55d398326f99059fF775485246999027B3197955',
            ]
        ],
        
        'ETH' => [
            'name' => 'Ethereum',
            'symbol' => 'ETH',
            'decimals' => 18,
            'stable' => false,
            'recommended' => false,
            'native' => true, // Native currency, no contract address
        ],
        
        'BNB' => [
            'name' => 'Binance Coin',
            'symbol' => 'BNB',
            'decimals' => 18,
            'stable' => false,
            'recommended' => false,
            'native' => true,
        ],
        
        'RON' => [
            'name' => 'Ronin',
            'symbol' => 'RON',
            'decimals' => 18,
            'stable' => false,
            'recommended' => false,
            'native' => true,
        ],
        
        'AXS' => [
            'name' => 'Axie Infinity Shard',
            'symbol' => 'AXS',
            'decimals' => 18,
            'stable' => false,
            'recommended' => false,
            'addresses' => [
                'ronin' => '0x97a9107C1793BC407d6F527b77e7fff4D812bece',
                'ethereum' => '0xBB0E17EF65F82Ab018d8EDd776e8DD940327B28b',
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Keys for Blockchain Monitoring
    |--------------------------------------------------------------------------
    */
    
    'api_keys' => [
        'polygonscan' => env('POLYGONSCAN_API_KEY'),
        'etherscan' => env('ETHERSCAN_API_KEY'),
        'bscscan' => env('BSCSCAN_API_KEY'),
        'ronin_explorer' => env('RONIN_EXPLORER_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */
    
    'payment' => [
        'default_network' => env('CRYPTO_DEFAULT_NETWORK', 'polygon'), // Recommended for low fees
        'default_token' => env('CRYPTO_DEFAULT_TOKEN', 'USDC'), // Stable coin
        'payment_timeout_minutes' => env('CRYPTO_PAYMENT_TIMEOUT', 15), // 15 minutes to pay
        'confirmation_blocks' => env('CRYPTO_CONFIRMATION_BLOCKS', 1), // Blocks to wait for confirmation
        'price_tolerance_percent' => env('CRYPTO_PRICE_TOLERANCE', 2), // 2% price tolerance
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet Integration
    |--------------------------------------------------------------------------
    */
    
    'wallets' => [
        'metamask' => [
            'name' => 'MetaMask',
            'supported_networks' => ['ethereum', 'polygon', 'bsc'],
            'icon' => '/images/wallets/metamask.svg',
            'deep_link' => 'https://metamask.app.link/dapp/',
        ],
        
        'ronin' => [
            'name' => 'Ronin Wallet',
            'supported_networks' => ['ronin'],
            'icon' => '/images/wallets/ronin.svg',
            'deep_link' => 'https://wallet.roninchain.com/dapp/',
        ],
        
        'trust' => [
            'name' => 'Trust Wallet',
            'supported_networks' => ['ethereum', 'polygon', 'bsc'],
            'icon' => '/images/wallets/trust.svg',
            'deep_link' => 'https://link.trustwallet.com/open_url?coin_id=60&url=',
        ],
        
        'walletconnect' => [
            'name' => 'WalletConnect',
            'supported_networks' => ['ethereum', 'polygon', 'bsc'],
            'icon' => '/images/wallets/walletconnect.svg',
            'universal' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    
    'security' => [
        'min_payment_usd' => env('CRYPTO_MIN_PAYMENT', 0.10), // Minimum $0.10
        'max_payment_usd' => env('CRYPTO_MAX_PAYMENT', 1000.00), // Maximum $1000
        'rate_limit_per_hour' => env('CRYPTO_RATE_LIMIT', 10), // 10 payments per hour per user
        'webhook_secret' => env('CRYPTO_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Configuration
    |--------------------------------------------------------------------------
    */
    
    'testing' => [
        'enabled' => env('CRYPTO_TESTING_MODE', false),
        'testnet_networks' => [
            'polygon_mumbai' => [
                'name' => 'Polygon Mumbai',
                'chain_id' => 80001,
                'rpc_url' => 'https://rpc-mumbai.maticvigil.com',
                'faucet' => 'https://faucet.polygon.technology',
            ],
            'ethereum_goerli' => [
                'name' => 'Ethereum Goerli',
                'chain_id' => 5,
                'rpc_url' => 'https://goerli.infura.io/v3/YOUR_KEY',
                'faucet' => 'https://goerlifaucet.com',
            ],
        ],
        'test_tokens' => [
            'USDC' => [
                'polygon_mumbai' => '0x742d35Cc6634C0532925a3b8D4C2C4e07C3c4526',
            ]
        ]
    ],

];
