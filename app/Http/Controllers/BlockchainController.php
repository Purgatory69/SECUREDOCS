<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\BlockchainStorage\BlockchainStorageManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class BlockchainController extends Controller
{
    protected BlockchainStorageManager $manager;

    public function __construct(BlockchainStorageManager $manager)
    {
        $this->middleware(['auth', 'verified']);
        $this->manager = $manager;
    }

    public function getProviders(): JsonResponse
    {
        try {
            $providers = $this->manager->getAvailableProviders();

            return response()->json([
                'success' => true,
                'default_provider' => config('blockchain.default'),
                'providers' => $providers,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch providers: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->manager->getUserStats(Auth::user());
            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getFiles(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->input('limit', 50);
            $files = File::query()
                ->where('user_id', Auth::id())
                ->blockchainStored()
                ->latest('updated_at')
                ->limit($limit)
                ->get();

            $mapped = $files->map(function (File $f) {
                $meta = $f->blockchain_metadata ?? [];
                $uploadTs = $meta['upload_timestamp'] ?? optional($f->updated_at)->toISOString();
                $gateway = $meta['gateway_url'] ?? $f->blockchain_url;
                return [
                    'id' => $f->id,
                    'file_name' => $f->file_name,
                    'file_size' => (int) $f->file_size,
                    'size_human' => $this->formatBytes((int) $f->file_size),
                    'provider' => $f->blockchain_provider,
                    'ipfs_hash' => $f->ipfs_hash,
                    'gateway_url' => $gateway,
                    'status' => 'pinned',
                    'encrypted' => (bool) ($meta['encrypted'] ?? false),
                    'upload_timestamp' => $uploadTs,
                    'created_at' => optional($f->created_at)->toISOString(),
                    'updated_at' => optional($f->updated_at)->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'files' => $mapped,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch files: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file',
                'provider' => 'nullable|string',
                'file_id' => 'nullable|integer',
            ]);

            $provider = $request->input('provider', config('blockchain.default'));
            $uploaded = $request->file('file');

            $fileModel = null;
            if ($request->filled('file_id')) {
                $fileModel = File::where('id', $request->integer('file_id'))
                    ->where('user_id', Auth::id())
                    ->firstOrFail();
            }

            if ($fileModel) {
                $result = $this->manager->uploadFileWithTracking($fileModel, $uploaded, Auth::user(), $provider);
                return response()->json($result);
            }

            // Enforce premium if required for the fallback path
            if (config('blockchain.premium.require_premium', true)) {
                $user = Auth::user();
                if (!($user->is_premium ?? false)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Premium subscription required for blockchain storage.'
                    ], 402);
                }
            }

            // Fallback: direct provider upload without DB association (MVP/testing)
            $providerSvc = $this->manager->provider($provider);
            $result = $providerSvc->uploadFile($uploaded, [
                'name' => $uploaded->getClientOriginalName(),
                'keyvalues' => [
                    'user_id' => Auth::id(),
                    'origin' => 'blockchain-modal',
                ],
            ]);

            return response()->json($result);
        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->getMessage(),
                'errors' => $ve->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Blockchain upload error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function unpinFile(File $file): JsonResponse
    {
        try {
            if ($file->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $ok = $this->manager->removeFile($file);
            return response()->json(['success' => (bool) $ok]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unpin failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function unpinByHash(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ipfs_hash' => 'required|string',
                'provider' => 'nullable|string',
            ]);
            $provider = $request->input('provider', config('blockchain.default'));
            $hash = $request->string('ipfs_hash');

            $ok = $this->manager->provider($provider)->unpinFile($hash);
            return response()->json(['success' => (bool) $ok]);
        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->getMessage(),
                'errors' => $ve->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unpin failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? floor(log($bytes) / log(1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
