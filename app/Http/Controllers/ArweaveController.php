<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\ArweaveWallet;
use App\Models\ArweaveTransaction;
use App\Services\ArweaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ArweaveController extends Controller
{
    protected ArweaveService $arweaveService;

    public function __construct(ArweaveService $arweaveService)
    {
        $this->middleware(['auth', 'verified']);
        $this->arweaveService = $arweaveService;
    }

    /**
     * Upload file to Arweave for permanent storage
     */
    public function uploadToPermanentStorage(Request $request, $fileId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check if user is premium
            if (!$user->is_premium) {
                return response()->json([
                    'success' => false,
                    'message' => 'Arweave permanent storage is only available for premium users'
                ], 403);
            }

            $file = File::where('id', $fileId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Check if file is already on Arweave
            if ($file->storage_provider === 'arweave' && $file->is_permanent_arweave) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is already stored permanently on Arweave'
                ], 400);
            }

            // Get cost estimate
            $filePath = storage_path('app/' . $file->file_path);
            $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
            
            if ($fileSize === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found or empty'
                ], 404);
            }

            $costEstimate = $this->arweaveService->calculateUploadCost($fileSize);

            // Check if user has wallet
            $wallet = $this->arweaveService->getUserWallet($user->id);
            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Arweave wallet found. Please create a wallet first.',
                    'cost_estimate' => $costEstimate,
                    'action_required' => 'create_wallet'
                ], 400);
            }

            // Check wallet balance
            $balance = $this->arweaveService->getWalletBalance($wallet->wallet_address);
            if ($balance['ar'] < $costEstimate['ar']) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient AR balance. Required: {$costEstimate['ar']} AR, Available: {$balance['ar']} AR",
                    'cost_estimate' => $costEstimate,
                    'current_balance' => $balance,
                    'action_required' => 'fund_wallet'
                ], 400);
            }

            // Proceed with upload
            $result = $this->arweaveService->uploadFile($file, $user->id);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'File uploaded to Arweave successfully',
                    'tx_id' => $result['tx_id'],
                    'arweave_url' => $result['url'],
                    'cost' => $result['cost'],
                    'estimated_confirmation' => $result['estimated_confirmation']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error']
            ], 500);

        } catch (\Exception $e) {
            Log::error('Arweave upload failed', [
                'file_id' => $fileId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create Arweave wallet for user
     */
    public function createWallet(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user already has a wallet
            $existingWallet = $this->arweaveService->getUserWallet($user->id);
            if ($existingWallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has an Arweave wallet'
                ], 400);
            }

            $wallet = $this->arweaveService->createWallet($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Arweave wallet created successfully',
                'wallet_address' => $wallet->wallet_address,
                'balance' => [
                    'ar' => 0,
                    'usd' => 0
                ],
                'funding_instructions' => [
                    'message' => 'Send AR tokens to this address to fund your wallet',
                    'address' => $wallet->wallet_address,
                    'min_amount' => '0.001 AR (~$0.01)',
                    'exchanges' => ['Binance', 'KuCoin', 'Gate.io']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Arweave wallet creation failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Wallet creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's Arweave wallet info
     */
    public function getWalletInfo(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $wallet = $this->arweaveService->getUserWallet($user->id);

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Arweave wallet found',
                    'has_wallet' => false
                ]);
            }

            // Get current balance
            $balance = $this->arweaveService->getWalletBalance($wallet->wallet_address);

            // Update wallet balance in database
            $wallet->update([
                'balance_ar' => $balance['ar'],
                'balance_usd' => $balance['usd'],
                'last_balance_check' => now()
            ]);

            return response()->json([
                'success' => true,
                'has_wallet' => true,
                'wallet' => [
                    'address' => $wallet->wallet_address,
                    'balance' => $balance,
                    'created_at' => $wallet->created_at,
                    'last_balance_check' => $wallet->last_balance_check
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get Arweave wallet info', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve wallet information'
            ], 500);
        }
    }

    /**
     * Get cost estimate for file upload
     */
    public function getCostEstimate(Request $request, $fileId): JsonResponse
    {
        try {
            $user = Auth::user();
            $file = File::where('id', $fileId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $filePath = storage_path('app/' . $file->file_path);
            $fileSize = file_exists($filePath) ? filesize($filePath) : 0;

            if ($fileSize === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found or empty'
                ], 404);
            }

            $costEstimate = $this->arweaveService->calculateUploadCost($fileSize);

            return response()->json([
                'success' => true,
                'cost_estimate' => $costEstimate,
                'file_info' => [
                    'name' => $file->file_name,
                    'size_bytes' => $fileSize,
                    'size_formatted' => $this->formatBytes($fileSize)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate cost: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction status
     */
    public function getTransactionStatus(Request $request, $txId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Find transaction in database
            $transaction = ArweaveTransaction::where('tx_id', $txId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Get current status from Arweave network
            $networkStatus = $this->arweaveService->getTransactionStatus($txId);

            // Update transaction if confirmed
            if ($networkStatus['confirmed'] && $transaction->status !== 'confirmed') {
                $transaction->update([
                    'status' => 'confirmed',
                    'block_height' => $networkStatus['block_height'],
                    'block_hash' => $networkStatus['block_hash'],
                    'confirmations' => $networkStatus['confirmations'],
                    'confirmed_at' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'transaction' => [
                    'tx_id' => $transaction->tx_id,
                    'status' => $transaction->status,
                    'confirmed' => $transaction->isConfirmed(),
                    'block_height' => $transaction->block_height,
                    'confirmations' => $transaction->confirmations,
                    'explorer_url' => $transaction->explorer_url,
                    'gateway_url' => $transaction->gateway_url,
                    'submitted_at' => $transaction->submitted_at,
                    'confirmed_at' => $transaction->confirmed_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found or error occurred'
            ], 404);
        }
    }

    /**
     * Get user's Arweave transactions
     */
    public function getTransactions(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $transactions = ArweaveTransaction::where('user_id', $user->id)
                ->with('file')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'transactions' => $transactions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transactions'
            ], 500);
        }
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
