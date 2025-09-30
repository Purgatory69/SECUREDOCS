<?php

namespace App\Services;

use App\Models\CryptoPayment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Real Crypto Payment Service for Permanent Storage
 * 
 * Handles:
 * - Real crypto payment requests (USDC, ETH, etc.)
 * - Payment monitoring via blockchain APIs
 * - Multi-network support (Polygon, Ethereum, Ronin)
 * - Real-time payment confirmation
 */
class RealCryptoPaymentService
{
    protected array $supportedNetworks;
    protected array $supportedTokens;
    protected string $businessWallet;
    protected int $paymentTimeoutMinutes;

    public function __construct()
    {
        $this->supportedNetworks = config('crypto.supported_networks');
        $this->supportedTokens = config('crypto.supported_tokens');
        $this->businessWallet = config('crypto.payment_wallet_address');
        $this->paymentTimeoutMinutes = config('crypto.payment.payment_timeout_minutes', 15);
    }

    /**
     * Create a real crypto payment request
     */
    public function createPaymentRequest(array $data): array
    {
        try {
            $userId = $data['user_id'];
            $amountUSD = $data['amount_usd'];
            $walletAddress = $data['wallet_address'];
            $walletType = $data['wallet_type'];
            $fileName = $data['file_name'];
            $fileSize = $data['file_size'];

            // Determine best network and token based on wallet type
            $paymentConfig = $this->getBestPaymentConfig($walletType, $amountUSD);
            
            // Get current crypto prices
            $cryptoAmount = $this->convertUSDToCrypto($amountUSD, $paymentConfig['token']);
            
            // Create payment record
            $payment = CryptoPayment::create([
                'user_id' => $userId,
                'wallet_address' => $walletAddress,
                'amount_usd' => $amountUSD,
                'amount_crypto' => $cryptoAmount,
                'token_symbol' => $paymentConfig['token'],
                'network' => $paymentConfig['network'],
                'chain_id' => $this->supportedNetworks[$paymentConfig['network']]['chain_id'],
                'status' => 'pending',
                'expires_at' => now()->addMinutes($this->paymentTimeoutMinutes),
                'cost_breakdown' => [
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'base_price_usd' => $data['amount_usd'] * 0.87, // Assuming 15% service fee
                    'service_fee_usd' => $data['amount_usd'] * 0.13,
                    'total_usd' => $amountUSD
                ],
                'payment_metadata' => [
                    'wallet_type' => $walletType,
                    'service_type' => $data['service_type'] ?? 'permanent_storage'
                ]
            ]);

            return [
                'payment_id' => $payment->id,
                'payment_details' => [
                    'to_address' => $this->businessWallet,
                    'amount' => number_format($cryptoAmount, 8),
                    'token' => $paymentConfig['token'],
                    'network' => $this->supportedNetworks[$paymentConfig['network']]['name'],
                    'chain_id' => $this->supportedNetworks[$paymentConfig['network']]['chain_id'],
                    'contract_address' => $this->getTokenContractAddress($paymentConfig['token'], $paymentConfig['network']),
                    'decimals' => $this->supportedTokens[$paymentConfig['token']]['decimals'],
                    'explorer_url' => $this->supportedNetworks[$paymentConfig['network']]['explorer'],
                    'expires_at' => $payment->expires_at->toISOString()
                ]
            ];

        } catch (Exception $e) {
            Log::error('Real crypto payment creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Check payment status by monitoring blockchain
     */
    public function checkPaymentStatus(string $paymentId, int $userId): array
    {
        try {
            $payment = CryptoPayment::where('id', $paymentId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // If already completed, return status
            if ($payment->status === 'completed') {
                return [
                    'status' => 'completed',
                    'tx_hash' => $payment->tx_hash,
                    'confirmed_at' => $payment->confirmed_at,
                    'explorer_url' => $payment->explorer_url
                ];
            }

            // If expired, mark as expired
            if ($payment->isExpired()) {
                $payment->update(['status' => 'expired']);
                return [
                    'status' => 'expired',
                    'message' => 'Payment window has expired'
                ];
            }

            // Check blockchain for payment
            $blockchainStatus = $this->checkBlockchainPayment($payment);
            
            if ($blockchainStatus['found']) {
                // Payment found! Update record
                $payment->update([
                    'status' => 'completed',
                    'tx_hash' => $blockchainStatus['tx_hash'],
                    'actual_amount_received' => $blockchainStatus['amount'],
                    'confirmed_at' => now()
                ]);

                return [
                    'status' => 'completed',
                    'tx_hash' => $blockchainStatus['tx_hash'],
                    'confirmed_at' => now(),
                    'explorer_url' => $payment->explorer_url
                ];
            }

            return [
                'status' => 'pending',
                'expires_in_minutes' => $payment->expires_at->diffInMinutes(now()),
                'amount_expected' => $payment->amount_crypto,
                'token' => $payment->token_symbol,
                'network' => $payment->network_display_name
            ];

        } catch (Exception $e) {
            Log::error('Payment status check failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get the best payment configuration based on wallet type
     */
    protected function getBestPaymentConfig(string $walletType, float $amountUSD): array
    {
        // Recommend networks based on amount and wallet
        if ($walletType === 'ronin') {
            return [
                'network' => 'ronin',
                'token' => 'RON' // Use native RON for Ronin wallet
            ];
        }

        // For small amounts, use Polygon for low fees
        if ($amountUSD < 10) {
            return [
                'network' => 'polygon',
                'token' => 'USDC' // Stable coin
            ];
        }

        // For larger amounts, use Ethereum mainnet
        return [
            'network' => 'ethereum',
            'token' => 'USDC'
        ];
    }

    /**
     * Convert USD to crypto amount using real-time prices
     */
    protected function convertUSDToCrypto(float $usdAmount, string $token): float
    {
        try {
            // Use CoinGecko API for real-time prices
            $tokenIds = [
                'USDC' => 'usd-coin',
                'USDT' => 'tether',
                'ETH' => 'ethereum',
                'BNB' => 'binancecoin',
                'RON' => 'ronin',
                'AXS' => 'axie-infinity'
            ];

            $tokenId = $tokenIds[$token] ?? 'usd-coin';
            
            $response = Http::timeout(10)->get('https://api.coingecko.com/api/v3/simple/price', [
                'ids' => $tokenId,
                'vs_currencies' => 'usd'
            ]);

            if ($response->successful()) {
                $priceData = $response->json();
                $tokenPriceUSD = $priceData[$tokenId]['usd'] ?? 1.0;
                
                return $usdAmount / $tokenPriceUSD;
            }

            // Fallback prices if API fails
            $fallbackPrices = [
                'USDC' => 1.0,
                'USDT' => 1.0,
                'ETH' => 2500.0,
                'BNB' => 300.0,
                'RON' => 1.5,
                'AXS' => 8.0
            ];

            $fallbackPrice = $fallbackPrices[$token] ?? 1.0;
            return $usdAmount / $fallbackPrice;

        } catch (Exception $e) {
            Log::warning('Price conversion failed, using fallback', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);
            
            // Safe fallback - assume 1:1 for stablecoins
            return $token === 'USDC' || $token === 'USDT' ? $usdAmount : $usdAmount / 100;
        }
    }

    /**
     * Get token contract address for the network
     */
    protected function getTokenContractAddress(string $token, string $network): ?string
    {
        $tokenConfig = $this->supportedTokens[$token] ?? null;
        
        if (!$tokenConfig) {
            return null;
        }

        // Native tokens don't have contract addresses
        if ($tokenConfig['native'] ?? false) {
            return null;
        }

        return $tokenConfig['addresses'][$network] ?? null;
    }

    /**
     * Check blockchain for payment (simplified - in production use proper blockchain APIs)
     */
    protected function checkBlockchainPayment(CryptoPayment $payment): array
    {
        try {
            // This is a simplified implementation
            // In production, you'd use proper blockchain APIs like:
            // - Etherscan API for Ethereum
            // - Polygonscan API for Polygon  
            // - Ronin Explorer API for Ronin
            
            $apiKey = config('crypto.api_keys.polygonscan'); // Example for Polygon
            
            if (!$apiKey) {
                Log::warning('No API key configured for blockchain monitoring');
                return ['found' => false];
            }

            // Example API call (you'd implement this for each network)
            $response = Http::timeout(10)->get('https://api.polygonscan.com/api', [
                'module' => 'account',
                'action' => 'tokentx',
                'address' => $this->businessWallet,
                'startblock' => 0,
                'endblock' => 'latest',
                'sort' => 'desc',
                'apikey' => $apiKey
            ]);

            if ($response->successful()) {
                $transactions = $response->json()['result'] ?? [];
                
                // Look for matching transaction
                foreach ($transactions as $tx) {
                    $txAmount = $tx['value'] / pow(10, $tx['tokenDecimal']);
                    $expectedAmount = $payment->amount_crypto;
                    
                    // Check if amounts match (with small tolerance)
                    if (abs($txAmount - $expectedAmount) < 0.01 && 
                        strtolower($tx['from']) === strtolower($payment->wallet_address) &&
                        strtolower($tx['to']) === strtolower($this->businessWallet)) {
                        
                        return [
                            'found' => true,
                            'tx_hash' => $tx['hash'],
                            'amount' => $txAmount
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
     * Get supported payment options for frontend
     */
    public function getSupportedOptions(): array
    {
        return [
            'networks' => $this->supportedNetworks,
            'tokens' => $this->supportedTokens,
            'wallets' => config('crypto.wallets'),
            'business_wallet' => $this->businessWallet,
            'payment_timeout_minutes' => $this->paymentTimeoutMinutes
        ];
    }
}
