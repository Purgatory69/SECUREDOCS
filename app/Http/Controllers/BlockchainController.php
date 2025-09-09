<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\BlockchainStorage\BlockchainStorageManager;
use App\Services\BlockchainStorage\BlockchainPreflightValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class BlockchainController extends Controller
{
    protected BlockchainStorageManager $manager;
    protected BlockchainPreflightValidator $validator;

    public function __construct(BlockchainStorageManager $manager, BlockchainPreflightValidator $validator)
    {
        $this->middleware(['auth', 'verified']);
        $this->manager = $manager;
        $this->validator = $validator;
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
            // Get blockchain-only files for the authenticated user
            $files = File::where('user_id', Auth::id())
                ->where('file_path', 'like', 'ipfs://%')
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'files' => $files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->file_name,
                        'file_size' => $file->file_size,
                        'mime_type' => $file->mime_type,
                        'created_at' => $file->created_at,
                        'updated_at' => $file->updated_at,
                        'file_path' => $file->file_path,
                        'ipfs_hash' => str_replace('ipfs://', '', $file->file_path),
                        'is_blockchain_stored' => true,
                    ];
                })
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch blockchain files: ' . $e->getMessage(),
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
                // Ensure file_path points to IPFS for blockchain-only visibility if unset
                if (($result['success'] ?? false) && empty($fileModel->file_path) && !empty($result['ipfs_hash'])) {
                    $fileModel->update(['file_path' => 'ipfs://' . $result['ipfs_hash']]);
                }
                return response()->json($result);
            }

            // Blockchain-only tracked upload (no existing file_id):
            // Enforce premium if required
            if (config('blockchain.premium.require_premium', true)) {
                $user = Auth::user();
                if (!($user->is_premium ?? false)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Premium subscription required for blockchain storage.'
                    ], 402);
                }
            }

            // Create a minimal File record linked to the user so uploads are tracked and visible per-user
            $placeholderFile = new File([
                'user_id' => Auth::id(),
                'file_name' => $uploaded->getClientOriginalName(),
                'file_path' => 'ipfs://pending', // placeholder; will be updated after success
                'file_size' => (string) ($uploaded->getSize() ?? 0),
                'mime_type' => $uploaded->getMimeType(),
                // Do not set is_folder explicitly; rely on DB default boolean false for Postgres
            ]);
            $placeholderFile->save();

            $result = $this->manager->uploadFileWithTracking($placeholderFile, $uploaded, Auth::user(), $provider);

            if (($result['success'] ?? false) && !empty($result['ipfs_hash'])) {
                $placeholderFile->update(['file_path' => 'ipfs://' . $result['ipfs_hash']]);
            }

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
            $hash = (string) $request->string('ipfs_hash');

            // Ensure the file belongs to the authenticated user
            $file = File::where('user_id', Auth::id())
                ->where('ipfs_hash', $hash)
                ->first();

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found or not owned by user.'
                ], 404);
            }

            $ok = $this->manager->removeFile($file);
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

    /**
     * Validate if a file can be uploaded to blockchain before actual upload
     */
    public function preflightValidation(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_id' => 'required|integer|exists:files,id',
                'provider' => 'nullable|string',
            ]);

            $file = File::where('id', $request->integer('file_id'))
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $provider = $request->input('provider', config('blockchain.default'));
            $validation = $this->validator->validateFileUpload($file, Auth::user(), $provider);

            return response()->json([
                'success' => $validation['success'],
                'validation' => $validation,
                'requirements' => $this->validator->getStorageRequirements($provider),
            ]);

        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->getMessage(),
                'errors' => $ve->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload an existing user file to blockchain storage
     */
    public function uploadExistingFile(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_id' => 'required|integer|exists:files,id',
                'provider' => 'nullable|string',
                'force' => 'boolean', // Skip non-critical validations
            ]);

            $file = File::where('id', $request->integer('file_id'))
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $provider = $request->input('provider', config('blockchain.default'));
            $force = $request->boolean('force', false);

            // Run preflight validation unless forced
            if (!$force) {
                $validation = $this->validator->validateFileUpload($file, Auth::user(), $provider);
                if (!$validation['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Preflight validation failed',
                        'validation' => $validation,
                    ], 400);
                }
            }

            // Create UploadedFile instance from existing file
            $filePath = Storage::path($file->file_path);
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found in storage',
                ], 404);
            }

            $uploadedFile = new UploadedFile(
                $filePath,
                $file->file_name,
                $file->mime_type,
                null,
                true // Mark as test to avoid validation errors
            );

            // Upload with tracking
            $result = $this->manager->uploadFileWithTracking($file, $uploadedFile, Auth::user(), $provider);

            if ($result['success']) {
                Log::info('Existing file uploaded to blockchain', [
                    'file_id' => $file->id,
                    'ipfs_hash' => $result['ipfs_hash'],
                    'provider' => $provider,
                    'user_id' => Auth::id(),
                ]);
            }

            return response()->json($result);

        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->getMessage(),
                'errors' => $ve->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Existing file blockchain upload error', [
                'error' => $e->getMessage(),
                'file_id' => $request->input('file_id'),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get blockchain storage requirements and user's current usage
     */
    public function getStorageInfo(): JsonResponse
    {
        try {
            $user = Auth::user();
            $requirements = $this->validator->getStorageRequirements();
            $stats = $this->manager->getUserStats($user);

            // Get user's files that could be uploaded to blockchain
            $eligibleFiles = File::where('user_id', $user->id)
                ->whereRaw('is_folder IS FALSE')
                ->whereRaw('is_blockchain_stored IS FALSE')
                ->whereNotNull('file_path')
                ->count();

            return response()->json([
                'success' => true,
                'requirements' => $requirements,
                'current_stats' => $stats,
                'eligible_files_count' => $eligibleFiles,
                'user_premium' => $user->is_premium ?? false,
            ]);

        } catch (Throwable $e) {
            Log::error('Blockchain storage-info error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'default_provider' => config('blockchain.default'),
                'pinata_enabled' => (bool) (config('blockchain.providers.pinata.enabled') ?? false),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get storage info: ' . $e->getMessage(),
            ], 500);
        }
    }
}
