<?php

namespace App\Services;

use App\Models\File;
use App\Models\CryptoPayment;
use App\Models\User;
use App\Services\ArweaveBundlerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Crypto Payment Service for Pay-Per-Upload Permanent Storage
 * 
 * Supports:
 * - MetaMask (Ethereum/Polygon)
 * - Ronin Wallet
 * - Multiple tokens (USDC, USDT, ETH, etc.)
 * - Instant payment detection
 */
class CryptoPaymentService
{
    protected ArweaveBundlerService $bundlerService;
    protected array $supportedNetworks;
    protected array $supportedTokens;

    public function __construct(ArweaveBundlerService $bundlerService)
    {
        $this->bundlerService = $bundlerService;
        $this->supportedNetworks = config('crypto.supported_networks');
        $this->supportedTokens = config('crypto.supported_tokens');
    }

    /**
     * Create crypto payment request for file upload
     */
    public function createPaymentRequest(File $file, User $user, string $walletAddress): array
    {
        try {
            // Calculate costs
            $filePath = storage_path('app/' . $file->file_path);
            $fileSize = filesize($filePath);
            $costBreakdown = $this->bundlerService->calculateUserCost($fileSize);

            // Create payment record
            $payment = CryptoPayment::create([
                'file_id' => $file->id,
                'user_id' => $user->id,
                'wallet_address' => $walletAddress,
                'amount_usd' => $costBreakdown['user_pays_usd'],
                'amount_crypto' => $this->convertUSDToCrypto($costBreakdown['user_pays_usd'], 'USDC'),
                'token_symbol' => 'USDC',
                'network' => 'polygon', // Low fees
                'status' => 'pending',
                'expires_at' => now()->addMinutes(15), // 15 min expiry
                'cost_breakdown' => $costBreakdown,
            ]);

            // Generate payment request
            return [
                'success' => true,
                'payment_id' => $payment->id,
                'payment_request' => [
                    'to_address' => config('crypto.payment_wallet_address'),
                    'amount' => $payment->amount_crypto,
                    'token' => 'USDC',
                    'network' => 'polygon',
                    'chain_id' => 137, // Polygon mainnet
                ],
                'cost_breakdown' => $costBreakdown,
                'file_info' => [
                    'name' => $file->file_name,
                    'size' => $this->formatBytes($fileSize)
                ],
                'expires_in_minutes' => 15,
                'qr_code_data' => $this->generatePaymentQR($payment),
            ];

        } catch (Exception $e) {
            Log::error('Failed to create crypto payment request', [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if payment has been received
     */
    public function checkPaymentStatus(string $paymentId): array
    {
        try {
            $payment = CryptoPayment::findOrFail($paymentId);

            if ($payment->status === 'completed') {
                return [
                    'success' => true,
                    'status' => 'completed',
                    'tx_hash' => $payment->tx_hash,
                    'confirmed_at' => $payment->confirmed_at
                ];
            }

            if ($payment->expires_at < now()) {
                $payment->update(['status' => 'expired']);
                return [
                    'success' => false,
                    'status' => 'expired',
                    'message' => 'Payment request expired'
                ];
            }

            // Check blockchain for payment
            $blockchainStatus = $this->checkBlockchainPayment($payment);
            
            if ($blockchainStatus['found']) {
                // Payment found! Update status and process upload
                $payment->update([
                    'status' => 'completed',
                    'tx_hash' => $blockchainStatus['tx_hash'],
                    'confirmed_at' => now(),
                    'actual_amount_received' => $blockchainStatus['amount']
                ]);

                // Trigger Arweave upload
                $this->processArweaveUpload($payment);

                return [
                    'success' => true,
                    'status' => 'completed',
                    'tx_hash' => $blockchainStatus['tx_hash'],
                    'message' => 'Payment confirmed! Uploading to Arweave...'
                ];
            }

            return [
                'success' => true,
                'status' => 'pending',
                'message' => 'Waiting for payment confirmation...'
            ];

        } catch (Exception $e) {
            Log::error('Failed to check payment status', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check blockchain for payment transaction
     */
    protected function checkBlockchainPayment(CryptoPayment $payment): array
    {
        try {
            // Use blockchain API to check for transactions
            $apiKey = config('crypto.polygon_api_key');
            $walletAddress = config('crypto.payment_wallet_address');
            
            // Check recent transactions to our wallet
            $response = Http::timeout(10)->get("https://api.polygonscan.com/api", [
                'module' => 'account',
                'action' => 'tokentx',
                'contractaddress' => config('crypto.tokens.USDC.polygon.address'),
                'address' => $walletAddress,
                'startblock' => 0,
                'endblock' => 'latest',
                'sort' => 'desc',
                'apikey' => $apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                foreach ($data['result'] ?? [] as $tx) {
                    // Check if transaction matches our payment
                    $txAmount = $tx['value'] / pow(10, 6); // USDC has 6 decimals
                    $txTimestamp = (int) $tx['timeStamp'];
                    $paymentCreated = $payment->created_at->timestamp;
                    
                    // Match criteria: amount, time window, sender
                    if (
                        abs($txAmount - $payment->amount_crypto) < 0.01 && // Allow small variance
                        $txTimestamp >= $paymentCreated &&
                        $txTimestamp <= $payment->expires_at->timestamp &&
                        strtolower($tx['from']) === strtolower($payment->wallet_address)
                    ) {
                        return [
                            'found' => true,
                            'tx_hash' => $tx['hash'],
                            'amount' => $txAmount,
                            'timestamp' => $txTimestamp
                        ];
                    }
                }
            }

            return ['found' => false];

        } catch (Exception $e) {
            Log::error('Blockchain payment check failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return ['found' => false];
        }
    }

    /**
     * Process Arweave upload after payment confirmation
     */
    protected function processArweaveUpload(CryptoPayment $payment): void
    {
        try {
            $file = $payment->file;
            $user = $payment->user;

            // Create fake payment data for bundler service
            $paymentData = [
                'method' => 'crypto',
                'token' => $payment->tx_hash,
                'amount' => $payment->amount_usd,
                'currency' => 'USD',
                'description' => "Crypto payment for {$file->file_name}"
            ];

            // Process upload via bundler service
            $result = $this->bundlerService->processPaymentAndUpload($file, $user, $paymentData);

            if ($result['success']) {
                $payment->update([
                    'arweave_tx_id' => $result['tx_id'],
                    'arweave_url' => $result['arweave_url'],
                    'upload_status' => 'completed'
                ]);

                Log::info('Crypto payment and Arweave upload completed', [
                    'payment_id' => $payment->id,
                    'tx_hash' => $payment->tx_hash,
                    'arweave_tx_id' => $result['tx_id']
                ]);
            } else {
                $payment->update(['upload_status' => 'failed']);
                Log::error('Arweave upload failed after crypto payment', [
                    'payment_id' => $payment->id,
                    'error' => $result['error']
                ]);
            }

        } catch (Exception $e) {
            Log::error('Failed to process Arweave upload after payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Convert USD to crypto amount
     */
    protected function convertUSDToCrypto(float $usdAmount, string $tokenSymbol): float
    {
        try {
            // Get current token price
            $price = $this->getTokenPrice($tokenSymbol);
            return round($usdAmount / $price, 6);

        } catch (Exception $e) {
            Log::warning('Failed to get crypto price, using fallback', [
                'token' => $tokenSymbol,
                'error' => $e->getMessage()
            ]);

            // Fallback prices
            $fallbackPrices = [
                'USDC' => 1.00,
                'USDT' => 1.00,
                'ETH' => 2500.00,
                'BNB' => 300.00,
            ];

            return round($usdAmount / ($fallbackPrices[$tokenSymbol] ?? 1.00), 6);
        }
    }

    /**
     * Get current token price in USD
     */
    protected function getTokenPrice(string $tokenSymbol): float
    {
        $coinGeckoIds = [
            'USDC' => 'usd-coin',
            'USDT' => 'tether',
            'ETH' => 'ethereum',
            'BNB' => 'binancecoin',
        ];

        $coinId = $coinGeckoIds[$tokenSymbol] ?? 'usd-coin';

        $response = Http::timeout(10)->get('https://api.coingecko.com/api/v3/simple/price', [
            'ids' => $coinId,
            'vs_currencies' => 'usd'
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data[$coinId]['usd'] ?? 1.00;
        }

        return 1.00; // Fallback
    }

    /**
     * Generate QR code data for mobile wallet payments
     */
    protected function generatePaymentQR(CryptoPayment $payment): string
    {
        // EIP-681 format for wallet compatibility
        return sprintf(
            'ethereum:%s@%d/transfer?address=%s&uint256=%s',
            config('crypto.tokens.USDC.polygon.address'),
            137, // Polygon chain ID
            config('crypto.payment_wallet_address'),
            bcmul($payment->amount_crypto, '1000000') // Convert to wei (6 decimals for USDC)
        );
    }

    /**
     * Get supported payment options for user
     */
    public function getSupportedPaymentOptions(): array
    {
        return [
            'networks' => [
                [
                    'id' => 'polygon',
                    'name' => 'Polygon',
                    'chain_id' => 137,
                    'currency' => 'MATIC',
                    'rpc_url' => 'https://polygon-rpc.com',
                    'explorer' => 'https://polygonscan.com',
                    'fees' => 'Low (~$0.01)'
                ],
                [
                    'id' => 'ethereum',
                    'name' => 'Ethereum',
                    'chain_id' => 1,
                    'currency' => 'ETH',
                    'rpc_url' => 'https://mainnet.infura.io/v3/YOUR_KEY',
                    'explorer' => 'https://etherscan.io',
                    'fees' => 'High (~$5-50)'
                ],
                [
                    'id' => 'ronin',
                    'name' => 'Ronin',
                    'chain_id' => 2020,
                    'currency' => 'RON',
                    'rpc_url' => 'https://api.roninchain.com/rpc',
                    'explorer' => 'https://explorer.roninchain.com',
                    'fees' => 'Very Low (~$0.001)'
                ]
            ],
            'tokens' => [
                [
                    'symbol' => 'USDC',
                    'name' => 'USD Coin',
                    'decimals' => 6,
                    'stable' => true,
                    'recommended' => true
                ],
                [
                    'symbol' => 'USDT',
                    'name' => 'Tether',
                    'decimals' => 6,
                    'stable' => true,
                    'recommended' => false
                ]
            ],
            'wallets' => [
                [
                    'id' => 'metamask',
                    'name' => 'MetaMask',
                    'icon' => '/images/wallets/metamask.svg',
                    'supported_networks' => ['ethereum', 'polygon']
                ],
                [
                    'id' => 'ronin',
                    'name' => 'Ronin Wallet',
                    'icon' => '/images/wallets/ronin.svg',
                    'supported_networks' => ['ronin']
                ]
            ]
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
