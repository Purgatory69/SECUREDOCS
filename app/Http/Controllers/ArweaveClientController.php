<?php

namespace App\Http\Controllers;

use App\Models\ArweaveTransaction;
use App\Models\ArweaveWallet;
use App\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Client-Side Arweave Upload Controller
 * Handles tracking of user uploads done via client-side Bundlr
 */
class ArweaveClientController extends Controller
{
    /**
     * Get or create user's Arweave wallet record
     */
    public function getWalletInfo(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $walletAddress = $request->input('wallet_address');

            if (!$walletAddress) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet address is required'
                ], 400);
            }

            // Validate wallet address format (basic check)
            if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $walletAddress)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid wallet address format'
                ], 400);
            }

            Log::info('Getting wallet info', [
                'user_id' => $user->id,
                'wallet_address' => $walletAddress
            ]);

            // Find existing wallet first
            $wallet = ArweaveWallet::where('user_id', $user->id)
                ->where('wallet_address', strtolower($walletAddress))
                ->first();

            if (!$wallet) {
                // Create new wallet using DB::raw for boolean to fix PostgreSQL casting
                $walletId = \DB::table('arweave_wallets')->insertGetId([
                    'user_id' => $user->id,
                    'wallet_address' => strtolower($walletAddress),
                    'encrypted_jwk' => 'client_side_wallet', // Placeholder for client-side connections
                    'balance_ar' => 0,
                    'balance_usd' => 0,
                    'is_active' => \DB::raw('true'), // Use DB::raw for PostgreSQL boolean
                    'last_balance_check' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Fetch the created wallet
                $wallet = ArweaveWallet::find($walletId);
            }

            return response()->json([
                'success' => true,
                'wallet' => [
                    'id' => $wallet->id,
                    'address' => $wallet->wallet_address,
                    'balance_ar' => (float) $wallet->balance_ar,
                    'balance_usd' => (float) $wallet->balance_usd,
                    'is_active' => $wallet->is_active,
                    'created_at' => $wallet->created_at?->toISOString(),
                    'last_check' => $wallet->last_balance_check?->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get wallet info', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get wallet info: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user's Bundlr balance
     */
    public function updateBalance(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $walletAddress = $request->input('wallet_address');
            $balanceAR = $request->input('balance_ar');

            if (!$walletAddress || $balanceAR === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet address and balance are required'
                ], 400);
            }

            $wallet = ArweaveWallet::where('user_id', $user->id)
                ->where('wallet_address', strtolower($walletAddress))
                ->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            $wallet->update([
                'balance_ar' => $balanceAR,
                'last_balance_check' => now()
            ]);

            return response()->json([
                'success' => true,
                'balance' => $balanceAR
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update balance', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update balance'
            ], 500);
        }
    }

    /**
     * Save upload record after client-side upload
     */
    public function saveUpload(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'arweave_url' => 'required|string',
                'file_name' => 'nullable|string'
            ]);

            // Simple insert into arweave_urls table
            \DB::table('arweave_urls')->insert([
                'user_id' => $user->id,
                'url' => $validated['arweave_url'],
                'file_name' => $validated['file_name'] ?? 'Untitled',
                'created_at' => now()
            ]);

            Log::info('Arweave URL saved', [
                'user_id' => $user->id,
                'url' => $validated['arweave_url']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'URL saved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save Arweave URL', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's upload history
     */
    public function getUploads(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = $request->input('per_page', 20);

            $uploads = \DB::table('arweave_urls')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'uploads' => $uploads->items(),
                'pagination' => [
                    'current_page' => $uploads->currentPage(),
                    'last_page' => $uploads->lastPage(),
                    'per_page' => $uploads->perPage(),
                    'total' => $uploads->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get upload history', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get upload history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's Arweave statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $stats = [
                'total_uploads' => ArweaveTransaction::where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->count(),
                
                'total_size_bytes' => ArweaveTransaction::where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->sum('file_size'),
                
                'total_cost_ar' => ArweaveTransaction::where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->sum('upload_cost'),
                
                'wallets' => ArweaveWallet::where('user_id', $user->id)
                    ->where('is_active', true)
                    ->get(['wallet_address', 'balance_ar', 'last_balance_check']),
                
                'recent_uploads' => ArweaveTransaction::where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(['file_name', 'file_size', 'arweave_url', 'created_at'])
            ];

            // Format size
            $stats['total_size_formatted'] = $this->formatBytes($stats['total_size_bytes']);

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get stats', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics'
            ], 500);
        }
    }

    /**
     * Format bytes to human readable
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
