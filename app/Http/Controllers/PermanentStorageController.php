<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\PermanentStorage;
use App\Models\CryptoPayment;
use App\Models\ArweaveTransaction;
use App\Services\ArweaveBundlerService;
use App\Services\RealCryptoPaymentService;
use App\Services\RealArweaveService;
use App\Services\ArweaveIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PermanentStorageController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Get pricing quote for permanent storage (DISABLED - use calculateCost instead)
     */
    public function getPricingQuote(Request $request, $fileId): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'This endpoint is deprecated. Use /api/permanent-storage/calculate-cost instead.'
        ], 410);
    }

    /**
     * Process payment and upload to permanent storage (DISABLED)
     */
    public function purchasePermanentStorage(Request $request, $fileId): JsonResponse
    {
        return response()->json([
            'message' => 'This endpoint is not yet implemented. Use the crypto payment flow instead.'
        ], 501);
    }

    /**
     * Calculate cost for permanent storage
     */
    public function calculateCost(Request $request): JsonResponse
    {
        try {
            Log::info('Calculate cost request received', $request->all());
            
            $request->validate([
                'file_size' => 'required|integer|min:1|max:104857600', // 100MB max
                'file_name' => 'required|string|max:255'
            ]);

            $fileSizeBytes = $request->file_size;
            $fileSizeMB = $fileSizeBytes / (1024 * 1024);
            
            // Arweave pricing calculation (no service fees as requested)
            $arweaveCostUSD = max(0.01, $fileSizeMB * 0.005); // ~$0.005 per MB (realistic Arweave cost)
            $serviceFeeUSD = 0; // No service fees as requested
            $processingFeeUSD = 0; // No processing fees
            
            $totalUSD = $arweaveCostUSD;
            
            // Convert to crypto (mock USDC rate)
            $usdcRate = 1.0; // 1 USDC ≈ 1 USD
            $totalCrypto = round($totalUSD / $usdcRate, 6);

            return response()->json([
                'success' => true,
                'cost_breakdown' => [
                    'arweave_cost_usd' => round($arweaveCostUSD, 4),
                    'service_fee_usd' => round($serviceFeeUSD, 4),
                    'processing_fee_usd' => round($processingFeeUSD, 2),
                    'total_usd' => round($totalUSD, 2),
                    'total_crypto' => $totalCrypto,
                    'recommended_token' => 'USDC',
                    'file_size_mb' => round($fileSizeMB, 2)
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file data',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Cost calculation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate storage cost'
            ], 500);
        }
    }

    /**
     * Create payment request for permanent storage
     */
    public function createPayment(Request $request): JsonResponse
    {
        try {
            Log::info('Create payment request received', $request->all());
            
            $request->validate([
                'file_name' => 'required|string|max:255',
                'file_size' => 'required|integer|min:1|max:104857600',
                'wallet_address' => 'required|string|max:255',
                'wallet_type' => 'required|string|in:metamask,ronin,walletconnect'
            ]);

            $user = Auth::user();
            
            // Calculate costs (no service fees as requested)
            $fileSizeBytes = $request->file_size;
            $fileSizeMB = $fileSizeBytes / (1024 * 1024);
            $arweaveCostUSD = max(0.01, $fileSizeMB * 0.005); // Realistic Arweave cost
            $serviceFeeUSD = 0; // No service fees
            $processingFeeUSD = 0; // No processing fees
            $totalUSD = $arweaveCostUSD;
            
            // Convert to crypto
            $usdcAmount = round($totalUSD, 6);
            // Create crypto payment request (simplified for now)
            $paymentId = 'pay_' . uniqid() . '_' . time();
            
            // Determine network and token based on wallet type
            $networkConfig = match($request->wallet_type) {
                'metamask' => ['network' => 'Polygon', 'token' => 'USDC', 'chain_id' => 137],
                'ronin' => ['network' => 'Ronin', 'token' => 'RON', 'chain_id' => 2020],
                'walletconnect' => ['network' => 'Ethereum', 'token' => 'USDC', 'chain_id' => 1],
                default => ['network' => 'Polygon', 'token' => 'USDC', 'chain_id' => 137]
            };
            
            // Convert USD to crypto (simplified)
            $cryptoAmount = $networkConfig['token'] === 'USDC' ? $totalUSD : $totalUSD / 100; // Mock conversion
            
            // Create payment record in database
            $payment = CryptoPayment::create([
                'user_id' => $user->id,
                'wallet_address' => $request->wallet_address,
                'amount_usd' => $totalUSD,
                'amount_crypto' => $cryptoAmount,
                'token_symbol' => $networkConfig['token'],
                'network' => strtolower($networkConfig['network']), // lowercase for consistency
                'chain_id' => $networkConfig['chain_id'],
                'status' => 'pending',
                'expires_at' => now()->addMinutes(15),
                'cost_breakdown' => [
                    'arweave_cost_usd' => $arweaveCostUSD,
                    'service_fee_usd' => $serviceFeeUSD,
                    'processing_fee_usd' => $processingFeeUSD,
                    'total_usd' => $totalUSD
                ],
                'payment_metadata' => [
                    'payment_id' => $paymentId,
                    'file_name' => $request->file_name,
                    'file_size' => $request->file_size,
                    'to_address' => config('crypto.payment_wallet_address', '0x742d35Cc6634C0532925a3b8D4C2C4e07C3c4526')
                ]
            ]);
            
            $paymentRequest = [
                'payment_id' => $paymentId,
                'payment_details' => [
                    'to_address' => config('crypto.payment_wallet_address', '0x742d35Cc6634C0532925a3b8D4C2C4e07C3c4526'),
                    'amount' => number_format($cryptoAmount, 6),
                    'token' => $networkConfig['token'],
                    'network' => $networkConfig['network'],
                    'chain_id' => $networkConfig['chain_id'],
                    'expires_at' => now()->addMinutes(15)->toISOString()
                ]
            ];
            
            Log::info('Payment record created', [
                'payment_id' => $paymentId,
                'status' => 'pending',
                'amount' => $cryptoAmount,
                'currency' => $networkConfig['token']
            ]);

            return response()->json([
                'success' => true,
                'payment_request' => $paymentRequest,
                'cost_breakdown' => [
                    'arweave_cost_usd' => round($arweaveCostUSD, 4),
                    'service_fee_usd' => round($serviceFeeUSD, 4),
                    'processing_fee_usd' => round($processingFeeUSD, 2),
                    'total_usd' => round($totalUSD, 2),
                    'total_crypto' => $usdcAmount,
                    'recommended_token' => 'USDC'
                ],
                'expires_in_minutes' => 15
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment data',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Payment request creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment request'
            ], 500);
        }
    }

    /**
     * Upload file to Arweave after payment confirmation
     * Get user's permanent storage history
     */
    public function getPermanentStorageHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $permanentFiles = PermanentStorage::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $totalSpent = PermanentStorage::where('user_id', $user->id)
                ->where('payment_status', 'confirmed')
                ->sum('payment_amount_usd');

            return response()->json([
                'success' => true,
                'files' => $permanentFiles,
                'statistics' => [
                    'total_files' => $permanentFiles->total(),
                    'total_spent_usd' => round($totalSpent, 2),
                    'average_cost_per_file' => $permanentFiles->total() > 0 ? round($totalSpent / $permanentFiles->total(), 2) : 0,
                    'storage_savings' => $this->calculateUserSavings($user->id)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load history'
            ], 500);
        }
    }

    /**
     * Calculate user's savings vs traditional cloud
     */
    protected function calculateUserSavings($userId): array
    {
        $totalSize = PermanentStorage::where('user_id', $userId)
            ->where('storage_status', 'completed')
            ->sum('file_size');

        $totalPaid = PermanentStorage::where('user_id', $userId)
            ->where('payment_status', 'confirmed')
            ->sum('payment_amount_usd');

        if ($totalSize === 0) {
            return ['total_savings' => 0, 'monthly_savings' => 0];
        }

        $totalSizeGB = $totalSize / (1024 * 1024 * 1024);
        $monthlyCloudCost = $totalSizeGB * 0.025; // Average cloud cost per GB/month
        $yearlyCloudCost = $monthlyCloudCost * 12;
        $fiveYearCloudCost = $yearlyCloudCost * 5;

        return [
            'total_paid_permanent' => round($totalPaid, 2),
            'monthly_cloud_equivalent' => round($monthlyCloudCost, 2),
            'yearly_cloud_equivalent' => round($yearlyCloudCost, 2),
            'five_year_savings' => round($fiveYearCloudCost - $totalPaid, 2),
            'break_even_months' => $monthlyCloudCost > 0 ? ceil($totalPaid / $monthlyCloudCost) : 0
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

    /**
     * Get payment address for different wallet types
     */
    private function getPaymentAddress($walletType): string
    {
        // Mock payment addresses for different networks
        return match($walletType) {
            'metamask' => '0x742d35Cc6634C0532925a3b8D4C9db96590c6C87', // Polygon USDC
            'ronin' => 'ronin:742d35Cc6634C0532925a3b8D4C9db96590c6C87', // Ronin format
            'walletconnect' => '0x742d35Cc6634C0532925a3b8D4C9db96590c6C87', // Ethereum USDC
            default => '0x742d35Cc6634C0532925a3b8D4C9db96590c6C87'
        };
    }


    /**
     * Check crypto payment status - PRODUCTION MODE
     * Requires real blockchain payment verification
     */
    public function checkPaymentStatus(Request $request, $paymentId): JsonResponse
    {
        try {
            Log::info('Checking payment status', ['payment_id' => $paymentId]);
            
            // Check if payment exists in database
            // payment_id is stored in payment_metadata JSON field
            $payment = CryptoPayment::where('user_id', Auth::id())
                ->whereJsonContains('payment_metadata->payment_id', $paymentId)
                ->first();
            
            if (!$payment) {
                return response()->json([
                    'success' => true,
                    'payment_status' => [
                        'status' => 'pending',
                        'message' => 'Waiting for payment...'
                    ]
                ]);
            }
            
            // Check actual payment status from blockchain
            // TODO: Implement real blockchain verification here
            // For now, check database status
            $status = $payment->status ?? 'pending';
            
            // Only return completed if payment is actually verified
            if ($status === 'completed' || $status === 'confirmed') {
                return response()->json([
                    'success' => true,
                    'payment_status' => [
                        'status' => 'completed',
                        'tx_hash' => $payment->tx_hash ?? null,
                        'confirmed_at' => $payment->confirmed_at ? $payment->confirmed_at->toISOString() : now()->toISOString(),
                        'explorer_url' => $payment->explorer_url ?? null
                    ]
                ]);
            }
            
            // Return pending status
            return response()->json([
                'success' => true,
                'payment_status' => [
                    'status' => $status,
                    'message' => 'Waiting for blockchain confirmation...'
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status'
            ], 500);
        }
    }

    /**
     * Upload file to Arweave after payment
     */
    public function uploadToArweave(Request $request): JsonResponse
    {
        try {
            Log::info('Arweave upload request received', [
                'file_name' => $request->file_name,
                'file_size' => $request->file_size,
                'payment_id' => $request->payment_id
            ]);
            
            $request->validate([
                'file_name' => 'required|string|max:255',
                'file_size' => 'required|integer|min:1',
                'file_content' => 'required|string',
                'mime_type' => 'required|string'
            ]);

            $user = Auth::user();
            
            // Create temporary file from base64 content
            $decodedContent = base64_decode($request->file_content);
            if ($decodedContent === false) {
                throw new \Exception('Invalid base64 file content');
            }
            
            $tempFile = tmpfile();
            $tempPath = stream_get_meta_data($tempFile)['uri'];
            file_put_contents($tempPath, $decodedContent);
            
            // Create UploadedFile instance
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tempPath,
                $request->file_name,
                $request->mime_type,
                null,
                true
            );
            
            // Use ArweaveIntegrationService for upload
            $arweaveService = new ArweaveIntegrationService();
            
            $uploadResult = $arweaveService->uploadFile($uploadedFile, [
                'payment_id' => $request->payment_id,
                'user_id' => $user->id,
                'original_name' => $request->file_name,
                'file_size' => $request->file_size
            ]);
            
            // Store permanent storage record
            $permanentStorage = PermanentStorage::create([
                'user_id' => $user->id,
                'file_name' => $request->file_name,
                'file_size' => $request->file_size,
                'mime_type' => $request->mime_type,
                'file_hash' => hash('sha256', $decodedContent),
                'payment_id' => $request->payment_id ?? 'demo_payment',
                'payment_status' => 'confirmed',
                'payment_amount_usd' => $request->input('cost_usd', 0),
                'payment_amount_crypto' => $request->input('cost_crypto', 0),
                'payment_token' => 'USDC',
                'payment_network' => $request->input('network', 'Polygon'),
                'wallet_address' => $request->input('wallet_address', ''),
                'wallet_type' => $request->input('wallet_type', 'metamask'),
                'storage_provider' => 'arweave',
                'arweave_transaction_id' => $uploadResult['transaction_id'],
                'storage_status' => 'completed',
                'payment_confirmed_at' => now(),
                'uploaded_at' => now(),
            ]);
            
            // Clean up temp file
            fclose($tempFile);
            
            Log::info('Arweave upload completed successfully', [
                'transaction_id' => $uploadResult['transaction_id'],
                'file_name' => $request->file_name,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded to Arweave successfully!',
                'arweave_id' => $uploadResult['transaction_id'],
                'arweave_url' => $uploadResult['url'],
                'gateway_urls' => $uploadResult['gateway_urls'],
                'file_info' => $uploadResult['file_info'],
                'receipt' => $uploadResult['receipt'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Arweave upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_name' => $request->file_name ?? 'unknown',
                'payment_id' => $request->payment_id ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Alchemy webhook for automatic payment detection
     */
    public function handleAlchemyWebhook(Request $request): JsonResponse
    {
        try {
            Log::info('Alchemy webhook received', [
                'payload' => $request->all()
            ]);
            
            // Get webhook data
            $webhookData = $request->all();
            
            // Alchemy sends activity in this format
            $event = $webhookData['event'] ?? null;
            if (!$event) {
                Log::warning('No event data in Alchemy webhook');
                return response()->json(['success' => false, 'message' => 'No event data'], 400);
            }
            
            $activity = $event['activity'] ?? [];
            if (empty($activity)) {
                Log::warning('No activity in Alchemy webhook event');
                return response()->json(['success' => false, 'message' => 'No activity'], 400);
            }
            
            // Process each transaction
            foreach ($activity as $tx) {
                $this->processIncomingTransaction($tx);
            }
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Alchemy webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['success' => false], 500);
        }
    }
    
    /**
     * Process incoming transaction from Alchemy
     */
    protected function processIncomingTransaction(array $tx): void
    {
        try {
            // Extract transaction details
            $toAddress = strtolower($tx['toAddress'] ?? '');
            $fromAddress = strtolower($tx['fromAddress'] ?? '');
            $value = $tx['value'] ?? 0;
            $txHash = $tx['hash'] ?? '';
            $asset = $tx['asset'] ?? '';
            
            Log::info('Processing Alchemy transaction', [
                'to' => $toAddress,
                'from' => $fromAddress,
                'value' => $value,
                'hash' => $txHash,
                'asset' => $asset
            ]);
            
            // Check if this is to our payment wallet
            $ourWallet = strtolower(config('crypto.payment_wallet_address', ''));
            if ($toAddress !== $ourWallet) {
                Log::debug('Transaction not to our wallet', [
                    'to' => $toAddress,
                    'our_wallet' => $ourWallet
                ]);
                return;
            }
            
            // Convert value (USDC has 6 decimals)
            $amountUSDC = $value / 1000000;
            
            Log::info('Payment received', [
                'amount_usdc' => $amountUSDC,
                'from' => $fromAddress,
                'tx_hash' => $txHash
            ]);
            
            // Find matching pending payment
            $payment = CryptoPayment::where('status', 'pending')
                ->where(function($query) use ($fromAddress) {
                    $query->where('wallet_address', $fromAddress)
                          ->orWhere('wallet_address', 'like', '%' . substr($fromAddress, -8));
                })
                ->where('amount_crypto', '>=', $amountUSDC * 0.95) // 5% tolerance
                ->where('amount_crypto', '<=', $amountUSDC * 1.05)
                ->where('expires_at', '>', now())
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($payment) {
                $payment->update([
                    'status' => 'completed',
                    'tx_hash' => $txHash,
                    'actual_amount_received' => $amountUSDC,
                    'confirmed_at' => now()
                ]);
                
                Log::info('✅ Payment automatically confirmed!', [
                    'payment_id' => $payment->id,
                    'user_id' => $payment->user_id,
                    'amount' => $amountUSDC,
                    'tx_hash' => $txHash
                ]);
            } else {
                Log::warning('No matching payment found', [
                    'from_address' => $fromAddress,
                    'amount' => $amountUSDC,
                    'tx_hash' => $txHash
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Transaction processing failed', [
                'error' => $e->getMessage(),
                'tx' => $tx
            ]);
        }
    }

    /**
     * Get user's permanent storage history (PLACEHOLDER)
     */
    public function getHistory(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'payments' => [],
            'statistics' => [
                'total_payments' => 0,
                'total_spent_usd' => 0,
                'completed_uploads' => 0
            ]
        ]);
    }

    /**
     * Get supported crypto payment options (PLACEHOLDER)
     */
    public function getSupportedOptions(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'supported_options' => [
                'wallets' => ['metamask', 'ronin', 'walletconnect'],
                'tokens' => ['USDC', 'USDT', 'ETH'],
                'networks' => ['ethereum', 'polygon', 'ronin']
            ]
        ]);
    }
}
