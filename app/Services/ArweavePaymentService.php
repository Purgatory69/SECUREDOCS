<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use App\Models\ArweaveTransaction;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class ArweavePaymentService
{
    protected array $config;
    protected string $bundlrNetwork;
    protected string $currency;
    protected ?string $privateKey;

    public function __construct()
    {
        $this->config = config('arweave-payments');
        $this->bundlrNetwork = app()->environment('production') 
            ? $this->config['bundlr']['network']
            : $this->config['bundlr']['dev_network'];
        $this->currency = $this->config['bundlr']['currency'];
        $this->privateKey = $this->config['bundlr']['private_key'];
    }

    /**
     * Calculate total cost including service fees
     */
    public function calculateTotalCost(int $fileSizeBytes): array
    {
        try {
            // Get base Arweave storage cost
            $baseCost = $this->getArweaveStorageCost($fileSizeBytes);
            
            // Calculate service fee
            $serviceFeeUSD = $this->calculateServiceFee($baseCost['usd']);
            
            // Get current crypto prices
            $cryptoPrices = $this->getCryptoPrices();
            
            $totalUSD = $baseCost['usd'] + $serviceFeeUSD;
            
            return [
                'base_cost' => $baseCost,
                'service_fee_usd' => $serviceFeeUSD,
                'service_fee_percentage' => $this->config['pricing']['service_fee_percentage'],
                'total_usd' => $totalUSD,
                'payment_options' => $this->calculateCryptoAmounts($totalUSD, $cryptoPrices),
                'file_size_mb' => round($fileSizeBytes / 1024 / 1024, 2),
                'estimated_confirmation_time' => '5-15 minutes',
            ];

        } catch (Exception $e) {
            Log::error('Failed to calculate Arweave cost', [
                'error' => $e->getMessage(),
                'file_size' => $fileSizeBytes
            ]);

            // Fallback pricing
            return $this->getFallbackPricing($fileSizeBytes);
        }
    }

    /**
     * Process payment and upload file
     */
    public function processPaymentAndUpload(File $file, User $user, array $paymentData): array
    {
        try {
            Log::info('Processing Arweave payment and upload', [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'payment_method' => $paymentData['method'] ?? 'unknown'
            ]);

            // Validate payment
            $paymentValidation = $this->validatePayment($paymentData);
            if (!$paymentValidation['valid']) {
                throw new Exception('Payment validation failed: ' . $paymentValidation['error']);
            }

            // Create payment transaction record
            $paymentTransaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'file_id' => $file->id,
                'payment_method' => $paymentData['method'],
                'currency' => $paymentData['currency'],
                'amount' => $paymentData['amount'],
                'amount_usd' => $paymentData['amount_usd'],
                'service_fee_usd' => $paymentData['service_fee_usd'],
                'transaction_hash' => $paymentData['tx_hash'] ?? null,
                'wallet_address' => $paymentData['wallet_address'] ?? null,
                'status' => 'pending',
                'payment_data' => $paymentData,
            ]);

            // Process the actual upload to Arweave
            $uploadResult = $this->uploadToArweave($file, $user, $paymentTransaction);

            if ($uploadResult['success']) {
                // Update payment status
                $paymentTransaction->update([
                    'status' => 'completed',
                    'arweave_tx_id' => $uploadResult['tx_id'],
                    'completed_at' => now(),
                ]);

                return [
                    'success' => true,
                    'payment_id' => $paymentTransaction->id,
                    'arweave_tx_id' => $uploadResult['tx_id'],
                    'arweave_url' => $uploadResult['url'],
                    'total_cost_usd' => $paymentData['amount_usd'],
                    'service_fee_usd' => $paymentData['service_fee_usd'],
                ];
            } else {
                // Update payment status to failed
                $paymentTransaction->update([
                    'status' => 'failed',
                    'error_message' => $uploadResult['error'] ?? 'Upload failed',
                ]);

                throw new Exception('Upload failed: ' . ($uploadResult['error'] ?? 'Unknown error'));
            }

        } catch (Exception $e) {
            Log::error('Arweave payment and upload failed', [
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
     * Get Arweave storage cost from network
     */
    protected function getArweaveStorageCost(int $fileSizeBytes): array
    {
        $response = Http::timeout(10)->get('https://arweave.net/price/' . $fileSizeBytes);
        
        if ($response->successful()) {
            $priceWinston = (int) $response->body();
            $priceAR = $priceWinston / 1000000000000; // Convert Winston to AR
            
            // Get AR to USD rate
            $arToUsd = $this->getARtoUSDRate();
            $priceUSD = $priceAR * $arToUsd;

            return [
                'ar' => round($priceAR, 12),
                'usd' => round($priceUSD, 4),
                'winston' => $priceWinston,
                'ar_usd_rate' => $arToUsd
            ];
        }

        // Fallback pricing
        $fileSizeGB = $fileSizeBytes / (1024 * 1024 * 1024);
        $estimatedUSD = max(0.01, $fileSizeGB * 2.13);
        
        return [
            'ar' => 0.001,
            'usd' => $estimatedUSD,
            'winston' => 1000000000,
            'ar_usd_rate' => 10.0,
            'estimated' => true
        ];
    }

    /**
     * Calculate service fee
     */
    protected function calculateServiceFee(float $baseCostUSD): float
    {
        $feePercentage = $this->config['pricing']['service_fee_percentage'];
        $minFee = $this->config['pricing']['minimum_service_fee_usd'];
        $maxFee = $this->config['pricing']['maximum_service_fee_usd'];

        $calculatedFee = $baseCostUSD * ($feePercentage / 100);
        
        // Apply min/max limits
        $serviceFee = max($minFee, min($maxFee, $calculatedFee));
        
        return round($serviceFee, 4);
    }

    /**
     * Get current cryptocurrency prices
     */
    protected function getCryptoPrices(): array
    {
        $cacheKey = 'crypto_prices';
        
        return Cache::remember($cacheKey, 300, function () { // Cache for 5 minutes
            try {
                $response = Http::timeout(10)->get('https://api.coingecko.com/api/v3/simple/price', [
                    'ids' => 'ethereum,matic-network,solana',
                    'vs_currencies' => 'usd'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'ETH' => $data['ethereum']['usd'] ?? 2000,
                        'MATIC' => $data['matic-network']['usd'] ?? 0.8,
                        'SOL' => $data['solana']['usd'] ?? 100,
                    ];
                }
            } catch (Exception $e) {
                Log::warning('Failed to fetch crypto prices', ['error' => $e->getMessage()]);
            }

            // Fallback prices
            return [
                'ETH' => 2000,
                'MATIC' => 0.8,
                'SOL' => 100,
            ];
        });
    }

    /**
     * Calculate crypto amounts for payment options
     */
    protected function calculateCryptoAmounts(float $totalUSD, array $cryptoPrices): array
    {
        $paymentOptions = [];
        
        foreach ($this->config['wallet']['supported_currencies'] as $symbol => $currency) {
            if (!$currency['enabled']) continue;
            
            $price = $cryptoPrices[$symbol] ?? 1;
            $amount = $totalUSD / $price;
            
            $paymentOptions[$symbol] = [
                'currency' => $symbol,
                'name' => $currency['name'],
                'amount' => round($amount, 8),
                'amount_formatted' => number_format($amount, 8) . ' ' . $symbol,
                'usd_price' => $price,
                'network' => $currency['network'],
                'decimals' => $currency['decimals'],
            ];
        }
        
        return $paymentOptions;
    }

    /**
     * Validate payment data
     */
    protected function validatePayment(array $paymentData): array
    {
        // Basic validation
        if (empty($paymentData['method']) || empty($paymentData['currency']) || empty($paymentData['amount'])) {
            return ['valid' => false, 'error' => 'Missing required payment data'];
        }

        // For crypto payments, validate transaction hash
        if ($paymentData['method'] === 'crypto' && empty($paymentData['tx_hash'])) {
            return ['valid' => false, 'error' => 'Transaction hash required for crypto payments'];
        }

        // Validate currency is supported
        $supportedCurrencies = array_keys($this->config['wallet']['supported_currencies']);
        if (!in_array($paymentData['currency'], $supportedCurrencies)) {
            return ['valid' => false, 'error' => 'Unsupported currency'];
        }

        // TODO: Add blockchain transaction verification
        // This would involve checking the transaction hash on the blockchain
        
        return ['valid' => true];
    }

    /**
     * Upload file to Arweave via Bundlr
     */
    protected function uploadToArweave(File $file, User $user, PaymentTransaction $payment): array
    {
        try {
            // Use the existing ArweaveService for actual upload
            $arweaveService = new ArweaveService();
            $result = $arweaveService->uploadFile($file, $user->id);
            
            if ($result['success']) {
                // Update the ArweaveTransaction with payment info
                $arweaveTransaction = ArweaveTransaction::where('tx_id', $result['tx_id'])->first();
                if ($arweaveTransaction) {
                    $arweaveTransaction->update([
                        'payment_transaction_id' => $payment->id,
                        'paid_amount_usd' => $payment->amount_usd,
                        'service_fee_usd' => $payment->service_fee_usd,
                    ]);
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Arweave upload failed', [
                'file_id' => $file->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get AR to USD conversion rate
     */
    protected function getARtoUSDRate(): float
    {
        try {
            $response = Http::timeout(10)->get('https://api.coingecko.com/api/v3/simple/price', [
                'ids' => 'arweave',
                'vs_currencies' => 'usd'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['arweave']['usd'] ?? 10.0;
            }
        } catch (Exception $e) {
            Log::warning('Failed to fetch AR price', ['error' => $e->getMessage()]);
        }

        return 10.0; // Fallback rate
    }

    /**
     * Get fallback pricing when API fails
     */
    protected function getFallbackPricing(int $fileSizeBytes): array
    {
        $fileSizeGB = $fileSizeBytes / (1024 * 1024 * 1024);
        $baseCostUSD = max(0.01, $fileSizeGB * 2.13);
        $serviceFeeUSD = $this->calculateServiceFee($baseCostUSD);
        $totalUSD = $baseCostUSD + $serviceFeeUSD;
        
        return [
            'base_cost' => [
                'ar' => 0.001,
                'usd' => $baseCostUSD,
                'estimated' => true
            ],
            'service_fee_usd' => $serviceFeeUSD,
            'service_fee_percentage' => $this->config['pricing']['service_fee_percentage'],
            'total_usd' => $totalUSD,
            'payment_options' => $this->calculateCryptoAmounts($totalUSD, $this->getCryptoPrices()),
            'file_size_mb' => round($fileSizeBytes / 1024 / 1024, 2),
            'estimated' => true,
        ];
    }
}
