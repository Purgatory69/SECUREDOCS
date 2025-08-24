<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\BlockchainStorage\BlockchainStorageManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class DebugController extends Controller
{
    protected BlockchainStorageManager $manager;

    public function __construct(BlockchainStorageManager $manager)
    {
        $this->middleware(['auth', 'verified']);
        $this->manager = $manager;
    }

    public function debugBlockchainStats(): JsonResponse
    {
        try {
            // Test each step individually
            $user = Auth::user();
            Log::info('Debug: User authenticated', ['user_id' => $user->id]);
            
            $stats = $this->manager->getUserStats($user);
            Log::info('Debug: Got blockchain stats', $stats);
            
            return response()->json([
                'success' => true,
                'debug' => 'Stats retrieved successfully',
                'stats' => $stats,
                'user_id' => $user->id
            ]);
        } catch (Throwable $e) {
            Log::error('Debug blockchain stats error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function debugBlockchainFiles(): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('Debug: Starting files debug for user', ['user_id' => $user->id]);
            
            // Test File model and scope
            $files = File::query()
                ->where('user_id', $user->id)
                ->blockchainStored()
                ->latest('updated_at')
                ->limit(10)
                ->get();
                
            Log::info('Debug: Found blockchain files', ['count' => $files->count()]);
            
            return response()->json([
                'success' => true,
                'debug' => 'Files retrieved successfully',
                'file_count' => $files->count(),
                'files' => $files->map(function($f) {
                    return [
                        'id' => $f->id,
                        'file_name' => $f->file_name,
                        'blockchain_provider' => $f->blockchain_provider,
                        'ipfs_hash' => $f->ipfs_hash,
                        'is_blockchain_stored' => $f->is_blockchain_stored
                    ];
                })
            ]);
        } catch (Throwable $e) {
            Log::error('Debug blockchain files error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
