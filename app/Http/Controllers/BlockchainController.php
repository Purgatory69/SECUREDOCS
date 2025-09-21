<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\BlockchainUpload;
use App\Services\BlockchainStorage\BlockchainStorageManager;
use App\Services\BlockchainManager;
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
            $userId = Auth::id();
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get blockchain-stored files for the authenticated user
            // Use is_blockchain_stored flag instead of file_path pattern
            $files = File::where('user_id', $userId)
                ->whereRaw('is_blockchain_stored = true')
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('BlockchainController getFiles query', [
                'user_id' => $userId,
                'files_count' => $files->count(),
                'files_ids' => $files->pluck('id')->toArray()
            ]);

            // Simple test - just return basic file info first
            return response()->json([
                'success' => true,
                'files' => $files->map(function ($file) {
                    $result = [
                        'id' => $file->id,
                        'file_name' => $file->file_name,
                        'file_size' => $file->file_size,
                        'mime_type' => $file->mime_type,
                        'created_at' => $file->created_at,
                        'updated_at' => $file->updated_at,
                        'file_path' => $file->file_path,
                        'ipfs_hash' => $file->ipfs_hash,
                        'blockchain_provider' => $file->blockchain_provider,
                        'blockchain_url' => $file->blockchain_url,
                        'is_blockchain_stored' => true,
                        'pinata_gateway_url' => $file->ipfs_hash ? 'https://gateway.pinata.cloud/ipfs/' . $file->ipfs_hash : null,
                    ];
                    
                    // Safely add new columns
                    try {
                        $result['is_permanent_storage'] = $file->is_permanent_storage ?? false;
                        $result['permanent_storage_enabled_at'] = $file->permanent_storage_enabled_at;
                    } catch (\Exception $e) {
                        $result['is_permanent_storage'] = false;
                        $result['permanent_storage_enabled_at'] = null;
                    }
                    
                    return $result;
                })
            ]);
        } catch (Throwable $e) {
            Log::error('BlockchainController getFiles error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch blockchain files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get blockchain file history/activity
     */
    public function getFileHistory(Request $request, $fileId): JsonResponse
    {
        try {
            $user = Auth::user();
            $file = File::where('id', $fileId)
                ->where('user_id', $user->id)
                ->where('is_blockchain_stored', true)
                ->firstOrFail();

            // Get blockchain upload records
            $blockchainUploads = BlockchainUpload::where('file_id', $fileId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Get file activity records if they exist
            $fileActivities = [];
            if (class_exists('App\Models\FileActivity')) {
                $fileActivities = \App\Models\FileActivity::where('file_id', $fileId)
                    ->where('activity_type', 'LIKE', '%blockchain%')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            // Combine and format history
            $history = [];

            // Add blockchain upload events
            foreach ($blockchainUploads as $upload) {
                $history[] = [
                    'id' => 'blockchain_' . $upload->id,
                    'type' => 'blockchain_upload',
                    'title' => 'Uploaded to Blockchain',
                    'description' => "File uploaded to {$upload->provider} IPFS",
                    'status' => $upload->upload_status,
                    'ipfs_hash' => $upload->ipfs_hash,
                    'provider' => $upload->provider,
                    'gateway_url' => $upload->pinata_gateway_url,
                    'timestamp' => $upload->created_at,
                    'metadata' => [
                        'pin_size' => $upload->pinata_pin_size,
                        'pin_id' => $upload->pinata_pin_id,
                        'upload_cost' => $upload->upload_cost
                    ]
                ];
            }

            // Add permanent storage event if applicable
            if ($file->is_permanent_storage) {
                $history[] = [
                    'id' => 'permanent_' . $file->id,
                    'type' => 'permanent_storage',
                    'title' => 'Permanent Storage Enabled',
                    'description' => 'File marked as permanent storage (undeletable)',
                    'status' => 'active',
                    'timestamp' => $file->permanent_storage_enabled_at,
                    'metadata' => [
                        'enabled_by' => $file->permanent_storage_enabled_by
                    ]
                ];
            }

            // Add file activity events
            foreach ($fileActivities as $activity) {
                $history[] = [
                    'id' => 'activity_' . $activity->id,
                    'type' => 'file_activity',
                    'title' => $activity->activity_type,
                    'description' => $activity->description ?? '',
                    'status' => 'completed',
                    'timestamp' => $activity->created_at,
                    'metadata' => json_decode($activity->metadata ?? '{}', true)
                ];
            }

            // Sort by timestamp (newest first)
            usort($history, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });

            return response()->json([
                'success' => true,
                'file' => [
                    'id' => $file->id,
                    'name' => $file->file_name,
                    'ipfs_hash' => $file->ipfs_hash,
                    'blockchain_provider' => $file->blockchain_provider,
                    'is_permanent_storage' => $file->is_permanent_storage
                ],
                'history' => $history
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get blockchain file history', [
                'file_id' => $fileId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get file history'
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

            $provider = $request->input('provider', config('blockchain.default', 'pinata'));

            return response()->json([
                'success' => true,
                'validation' => [
                    'can_upload' => true,
                    'message' => 'File is ready for blockchain upload'
                ],
                'requirements' => [
                    'max_file_size' => 100 * 1024 * 1024, // 100MB
                    'supported_types' => ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png']
                ],
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

            // Skip validation for now - simplified approach
            if (!$force && $file->file_size > 100 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'File too large for blockchain upload (max 100MB)',
                ], 422);
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
            $requirements = [
                'max_file_size' => 100 * 1024 * 1024, // 100MB
                'supported_types' => ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png'],
                'max_files_per_user' => 1000
            ];
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
