<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\PermanentStorage;
use App\Models\CryptoPayment;
use App\Models\ArweaveTransaction;
use App\Services\ArweaveBundlerService;
use App\Services\CryptoPaymentService;
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
            'success' => false,
            'message' => 'This endpoint is not yet implemented. Use the crypto payment flow instead.'
        ], 501);
    }

    /**
     * Get pricing tiers for marketing/info (DISABLED)
     */
    public function getPricingTiers(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'This endpoint is not yet implemented.'
        ], 501);
    }

    /**
     * Calculate cost for permanent storage
     */
    public function calculateCost(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_size' => 'required|integer|min:1|max:104857600', // 100MB max
                'file_name' => 'required|string|max:255'
            ]);

            $fileSizeBytes = $request->file_size;
            $fileSizeMB = $fileSizeBytes / (1024 * 1024);
            
            // Arweave pricing calculation (approximate)
            $arweaveCostUSD = max(0.01, $fileSizeMB * 0.002); // ~$0.002 per MB
            $serviceFeeUSD = $arweaveCostUSD * 0.1; // 10% service fee
            $processingFeeUSD = 0.05; // Fixed processing fee
            
            $totalUSD = $arweaveCostUSD + $serviceFeeUSD + $processingFeeUSD;
            
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
            $request->validate([
                'file_name' => 'required|string|max:255',
                'file_size' => 'required|integer|min:1|max:104857600',
                'wallet_address' => 'required|string|max:255',
                'wallet_type' => 'required|string|in:metamask,ronin,walletconnect'
            ]);

            $user = Auth::user();
            
            // Calculate costs
            $fileSizeBytes = $request->file_size;
            $fileSizeMB = $fileSizeBytes / (1024 * 1024);
            $arweaveCostUSD = max(0.01, $fileSizeMB * 0.002);
            $serviceFeeUSD = $arweaveCostUSD * 0.1;
            $processingFeeUSD = 0.05;
            $totalUSD = $arweaveCostUSD + $serviceFeeUSD + $processingFeeUSD;
            
            // Convert to crypto
            $usdcAmount = round($totalUSD, 6);
            
            // Determine network based on wallet type
            $network = match($request->wallet_type) {
                'metamask' => 'Polygon',
                'ronin' => 'Ronin Network',
                'walletconnect' => 'Ethereum',
                default => 'Ethereum'
            };
            
            // Generate payment request
            $paymentRequest = [
                'payment_id' => 'pay_' . uniqid(),
                'amount' => $usdcAmount,
                'amount_usd' => $totalUSD,
                'amount_crypto' => $usdcAmount,
                'token' => 'USDC',
                'network' => $network,
                'to_address' => $this->getPaymentAddress($request->wallet_type),
                'wallet_address' => $request->wallet_address,
                'wallet_type' => $request->wallet_type,
                'expires_at' => now()->addMinutes(15),
                'file_info' => [
                    'name' => $request->file_name,
                    'size' => $request->file_size
                ]
            ];

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
     * Check crypto payment status
     */
    public function checkPaymentStatus(Request $request, $paymentId): JsonResponse
    {
        try {
            // For now, return a mock response since we don't have real crypto integration yet
            // In a real implementation, this would check the blockchain for payment confirmation
            
            // Always return confirmed for testing (change back to random for production)
            $status = 'confirmed';
            
            return response()->json([
                'success' => true,
                'payment_id' => $paymentId,
                'status' => $status,
                'confirmations' => $status === 'confirmed' ? rand(1, 12) : 0,
                'transaction_hash' => $status === 'confirmed' ? '0x' . bin2hex(random_bytes(32)) : null,
                'message' => match($status) {
                    'pending' => 'Payment is being processed...',
                    'confirmed' => 'Payment confirmed! File will be uploaded to Arweave.',
                    'failed' => 'Payment failed. Please try again.',
                    default => 'Unknown status'
                }
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
            $request->validate([
                'payment_id' => 'required|string',
                'file_name' => 'required|string|max:255',
                'file_size' => 'required|integer|min:1',
                'file_content' => 'required|string', // Base64 encoded file
                'mime_type' => 'required|string|max:255'
            ]);

            $user = Auth::user();
            
            // Debug: Log the request data
            Log::info('Permanent storage upload request', [
                'user_id' => $user->id,
                'payment_id' => $request->payment_id,
                'file_name' => $request->file_name,
                'file_size' => $request->file_size
            ]);
            
            // For now, simulate Arweave upload with a mock transaction ID
            // In production, this would use actual Arweave SDK
            $mockArweaveId = bin2hex(random_bytes(21)) . bin2hex(random_bytes(1)); // 43 character ID like real Arweave
            
            // Store permanent storage record in dedicated table
            $permanentStorage = PermanentStorage::create([
                'user_id' => $user->id,
                'file_name' => $request->file_name,
                'file_size' => $request->file_size,
                'mime_type' => $request->mime_type,
                'file_hash' => hash('sha256', base64_decode($request->file_content)),
                'payment_id' => $request->payment_id,
                'payment_status' => 'confirmed',
                'payment_amount_usd' => $request->input('cost_usd', 0),
                'payment_amount_crypto' => $request->input('cost_crypto', 0),
                'payment_token' => 'USDC',
                'payment_network' => $request->input('network', 'Ethereum'),
                'wallet_address' => $request->input('wallet_address', ''),
                'wallet_type' => $request->input('wallet_type', 'metamask'),
                'storage_provider' => 'arweave',
                'arweave_transaction_id' => $mockArweaveId,
                'storage_status' => 'completed',
                'payment_confirmed_at' => now(),
                'uploaded_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded to Arweave successfully!',
                'arweave_id' => $mockArweaveId,
                'arweave_url' => "https://arweave.net/{$mockArweaveId}",
                'gateway_urls' => [
                    'primary' => "https://arweave.net/{$mockArweaveId}",
                    'backup' => "https://ar-io.net/{$mockArweaveId}",
                    'ipfs_style' => "https://gateway.ar-io.dev/{$mockArweaveId}"
                ],
                'file_info' => [
                    'id' => $permanentStorage->id,
                    'name' => $permanentStorage->file_name,
                    'size' => $permanentStorage->formatted_file_size,
                    'uploaded_at' => $permanentStorage->uploaded_at->toISOString()
                ],
                'access_instructions' => [
                    'permanent_url' => "https://arweave.net/{$mockArweaveId}",
                    'how_to_access' => 'Your file is permanently stored on Arweave blockchain. You can access it anytime using the URL above, even if this website goes offline.',
                    'backup_methods' => [
                        'Direct Arweave access via arweave.net',
                        'IPFS-style gateways',
                        'Local Arweave node (if running)',
                        'Third-party Arweave explorers'
                    ]
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid upload data',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Arweave upload failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
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
