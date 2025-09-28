<?php

namespace App\Services;

use App\Models\File;
use App\Models\ArweaveTransaction;
use App\Models\User;
use App\Services\ModernArweaveClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Arweave L2 Bundling Service with Fiat Payment Integration
 * 
 * This service handles:
 * 1. User pays USD via credit card
 * 2. System calculates Arweave cost + profit margin
 * 3. Uses L2 bundling service (like Bundlr/Irys) for easier uploads
 * 4. No user wallet management required
 */
class ArweaveBundlerService
{
    protected array $config;
    protected string $bundlerUrl;
    protected string $bundlerApiKey;
    protected float $profitMarginPercent;
    protected ModernArweaveClient $arweaveClient;

    public function __construct(ModernArweaveClient $arweaveClient)
    {
        $this->arweaveClient = $arweaveClient;
        $this->config = config('blockchain.providers.arweave');
        $this->bundlerUrl = config('services.bundler.url', 'https://node1.bundlr.network');
        $this->bundlerApiKey = config('services.bundler.api_key');
        $this->profitMarginPercent = config('blockchain.profit_margin', 25.0); // 25% profit margin
    }

    /**
     * Calculate total cost for user (Arweave cost + our profit)
     */
    public function calculateUserCost(int $fileSizeBytes): array
    {
        try {
            // Get raw Arweave network cost
            $arweaveCost = $this->getArweaveNetworkCost($fileSizeBytes);
            
            // Add our profit margin
            $ourFee = $arweaveCost['usd'] * ($this->profitMarginPercent / 100);
            $totalUserCost = $arweaveCost['usd'] + $ourFee;

            // Minimum charge
            $minimumCharge = 1.00; // $1 minimum
            $totalUserCost = max($totalUserCost, $minimumCharge);

            return [
                'user_pays_usd' => round($totalUserCost, 2),
                'arweave_network_cost_usd' => round($arweaveCost['usd'], 4),
                'our_fee_usd' => round($ourFee, 2),
                'profit_margin_percent' => $this->profitMarginPercent,
                'file_size_mb' => round($fileSizeBytes / 1024 / 1024, 2),
                'cost_per_mb' => round($totalUserCost / ($fileSizeBytes / 1024 / 1024), 4),
                'storage_duration' => 'Permanent (200+ years)',
                'breakdown' => [
                    'network_fee' => $arweaveCost['usd'],
                    'service_fee' => $ourFee,
                    'processing_fee' => 0.50, // Fixed processing fee
                    'total' => $totalUserCost
                ]
            ];

        } catch (Exception $e) {
            Log::error('Failed to calculate user cost', ['error' => $e->getMessage()]);
            
            // Fallback pricing
            $fileSizeGB = $fileSizeBytes / (1024 * 1024 * 1024);
            $fallbackCost = max(1.00, $fileSizeGB * 3.00); // $3/GB fallback
            
            return [
                'user_pays_usd' => $fallbackCost,
                'arweave_network_cost_usd' => $fallbackCost * 0.7,
                'our_fee_usd' => $fallbackCost * 0.3,
                'profit_margin_percent' => 30,
                'file_size_mb' => round($fileSizeBytes / 1024 / 1024, 2),
                'estimated' => true,
                'error' => 'Using fallback pricing'
            ];
        }
    }

    /**
     * Process fiat payment and upload to Arweave
     */
    public function processPaymentAndUpload(File $file, User $user, array $paymentData): array
    {
        try {
            // 1. Validate payment
            $paymentResult = $this->processPayment($paymentData);
            if (!$paymentResult['success']) {
                throw new Exception('Payment failed: ' . $paymentResult['error']);
            }

            // 2. Calculate costs
            $filePath = storage_path('app/' . $file->file_path);
            $fileSize = filesize($filePath);
            $costBreakdown = $this->calculateUserCost($fileSize);

            // 3. Create transaction record
            $transaction = ArweaveTransaction::create([
                'file_id' => $file->id,
                'user_id' => $user->id,
                'tx_id' => null, // Will be set after upload
                'wallet_address' => 'bundler_service', // Using bundler service
                'tx_type' => 'data',
                'status' => 'processing',
                'data_size' => $fileSize,
                'fee_ar' => 0, // We handle AR internally
                'fee_usd' => $costBreakdown['user_pays_usd'],
                'tx_metadata' => [
                    'payment_id' => $paymentResult['payment_id'],
                    'cost_breakdown' => $costBreakdown,
                    'bundler_service' => true,
                    'user_payment_method' => $paymentData['method'] ?? 'credit_card'
                ],
                'submitted_at' => now(),
            ]);

            // 4. Upload to Arweave via bundler
            $uploadResult = $this->uploadViaBundler($file, $transaction);

            if ($uploadResult['success']) {
                // 5. Update transaction with success
                $transaction->update([
                    'tx_id' => $uploadResult['tx_id'],
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'tx_metadata' => array_merge($transaction->tx_metadata, [
                        'bundler_response' => $uploadResult['bundler_data']
                    ])
                ]);

                // 6. Update file record
                $file->update([
                    'arweave_tx_id' => $uploadResult['tx_id'],
                    'arweave_url' => "https://arweave.net/{$uploadResult['tx_id']}",
                    'storage_provider' => 'arweave',
                    'is_permanent_arweave' => true,
                    'arweave_cost_ar' => 0, // Bundler handles AR
                    'arweave_cost_usd' => $costBreakdown['user_pays_usd'],
                    'is_blockchain_stored' => true,
                    'blockchain_provider' => 'arweave',
                ]);

                Log::info('Arweave bundler upload successful', [
                    'file_id' => $file->id,
                    'tx_id' => $uploadResult['tx_id'],
                    'user_paid_usd' => $costBreakdown['user_pays_usd'],
                    'payment_id' => $paymentResult['payment_id']
                ]);

                return [
                    'success' => true,
                    'tx_id' => $uploadResult['tx_id'],
                    'arweave_url' => "https://arweave.net/{$uploadResult['tx_id']}",
                    'cost_breakdown' => $costBreakdown,
                    'payment_id' => $paymentResult['payment_id'],
                    'message' => 'File uploaded to permanent storage successfully!'
                ];
            }

            throw new Exception('Upload to bundler failed: ' . $uploadResult['error']);

        } catch (Exception $e) {
            Log::error('Arweave bundler upload failed', [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            // Update transaction status if it exists
            if (isset($transaction)) {
                $transaction->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload file via L2 bundler service (Bundlr/Irys)
     */
    protected function uploadViaBundler(File $file, ArweaveTransaction $transaction): array
    {
        try {
            $filePath = storage_path('app/' . $file->file_path);
            $fileContent = file_get_contents($filePath);

            // Prepare metadata tags
            $tags = [
                [
                    'name' => 'Content-Type',
                    'value' => $file->mime_type ?? 'application/octet-stream'
                ],
                [
                    'name' => 'App-Name',
                    'value' => 'SecureDocs'
                ],
                [
                    'name' => 'App-Version',
                    'value' => '2.0'
                ],
                [
                    'name' => 'File-Name',
                    'value' => $file->file_name
                ],
                [
                    'name' => 'File-Size',
                    'value' => (string)strlen($fileContent)
                ],
                [
                    'name' => 'Upload-Timestamp',
                    'value' => now()->toISOString()
                ],
                [
                    'name' => 'User-ID',
                    'value' => (string)$file->user_id
                ],
                [
                    'name' => 'Payment-ID',
                    'value' => $transaction->tx_metadata['payment_id'] ?? ''
                ]
            ];

            // Upload to bundler service
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bundlerApiKey,
                'Content-Type' => 'application/octet-stream'
            ])->attach('file', $fileContent, $file->file_name)
              ->post($this->bundlerUrl . '/tx', [
                  'tags' => $tags
              ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'tx_id' => $data['id'],
                    'bundler_data' => $data
                ];
            }

            throw new Exception('Bundler API error: ' . $response->body());

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process fiat payment (integrate with Stripe/PayPal)
     */
    protected function processPayment(array $paymentData): array
    {
        try {
            // This would integrate with your payment processor (Stripe, PayPal, etc.)
            // For now, we'll simulate a successful payment
            
            $paymentId = 'pay_' . uniqid();
            
            // In real implementation:
            // $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            // $payment = $stripe->paymentIntents->create([...]);
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'amount' => $paymentData['amount'],
                'currency' => 'USD',
                'method' => $paymentData['method'] ?? 'credit_card'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get raw Arweave network cost
     */
    protected function getArweaveNetworkCost(int $fileSizeBytes): array
    {
        try {
            // Try bundler pricing API first
            $response = Http::timeout(10)->get($this->bundlerUrl . '/price/' . $fileSizeBytes);
            
            if ($response->successful()) {
                $priceWinston = (int) $response->body();
                $priceAR = $this->arweaveClient->winstonToAR($priceWinston);
                $arToUsd = $this->arweaveClient->getARtoUSDRate();
                $priceUSD = $priceAR * $arToUsd;

                return [
                    'ar' => round($priceAR, 12),
                    'usd' => round($priceUSD, 4),
                    'winston' => $priceWinston
                ];
            }

            // Fallback to direct Arweave network pricing
            $priceWinston = $this->arweaveClient->getPrice($fileSizeBytes);
            if ($priceWinston !== null) {
                $priceAR = $this->arweaveClient->winstonToAR($priceWinston);
                $arToUsd = $this->arweaveClient->getARtoUSDRate();
                $priceUSD = $priceAR * $arToUsd;

                return [
                    'ar' => round($priceAR, 12),
                    'usd' => round($priceUSD, 4),
                    'winston' => $priceWinston
                ];
            }

            throw new Exception('Failed to get pricing from both bundler and Arweave network');

        } catch (Exception $e) {
            Log::warning('Using fallback Arweave pricing', ['error' => $e->getMessage()]);
            
            // Fallback to estimated pricing
            $fileSizeGB = $fileSizeBytes / (1024 * 1024 * 1024);
            $estimatedUSD = max(0.10, $fileSizeGB * 2.13); // $2.13 per GB estimate, min $0.10
            $arToUsd = $this->arweaveClient->getARtoUSDRate();
            $estimatedAR = $estimatedUSD / $arToUsd;
            
            return [
                'ar' => round($estimatedAR, 12),
                'usd' => round($estimatedUSD, 4),
                'winston' => $this->arweaveClient->arToWinston($estimatedAR),
                'estimated' => true
            ];
        }
    }

    /**
     * Get current AR to USD exchange rate (delegated to ArweaveClient)
     */
    protected function getARtoUSDRate(): float
    {
        return $this->arweaveClient->getARtoUSDRate();
    }

    /**
     * Get pricing tiers for different file sizes
     */
    public function getPricingTiers(): array
    {
        $tiers = [
            ['size' => '1MB', 'bytes' => 1024 * 1024],
            ['size' => '10MB', 'bytes' => 10 * 1024 * 1024],
            ['size' => '100MB', 'bytes' => 100 * 1024 * 1024],
            ['size' => '1GB', 'bytes' => 1024 * 1024 * 1024],
        ];

        $pricing = [];
        foreach ($tiers as $tier) {
            $cost = $this->calculateUserCost($tier['bytes']);
            $pricing[] = [
                'size' => $tier['size'],
                'price_usd' => $cost['user_pays_usd'],
                'cost_per_mb' => $cost['cost_per_mb'],
                'savings_vs_cloud' => $this->calculateCloudSavings($tier['bytes'], $cost['user_pays_usd'])
            ];
        }

        return $pricing;
    }

    /**
     * Calculate savings vs traditional cloud storage
     */
    protected function calculateCloudSavings(int $bytes, float $ourPrice): array
    {
        $fileSizeGB = $bytes / (1024 * 1024 * 1024);
        
        // Traditional cloud costs per month
        $awsS3Monthly = $fileSizeGB * 0.023; // $0.023/GB/month
        $googleCloudMonthly = $fileSizeGB * 0.026; // $0.026/GB/month
        
        // Calculate 5-year costs
        $aws5Year = $awsS3Monthly * 12 * 5;
        $google5Year = $googleCloudMonthly * 12 * 5;
        
        return [
            'aws_s3_5_years' => round($aws5Year, 2),
            'google_cloud_5_years' => round($google5Year, 2),
            'our_permanent_cost' => $ourPrice,
            'savings_vs_aws' => round($aws5Year - $ourPrice, 2),
            'savings_vs_google' => round($google5Year - $ourPrice, 2),
            'savings_percentage' => round((($aws5Year - $ourPrice) / $aws5Year) * 100, 1)
        ];
    }
}
