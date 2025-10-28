<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ArweaveController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Save client-side Arweave upload record with encryption support and cost tracking
     */
    public function saveClientUpload(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'arweave_url' => 'required|url',
                'file_name' => 'required|string|max:255',
                'is_encrypted' => 'boolean',
                'encryption_method' => 'nullable|string|max:50',
                'password_hash' => 'nullable|string|max:255',
                'salt' => 'nullable|array',
                'iv' => 'nullable|array',
                // Cost tracking fields
                'upload_cost_matic' => 'nullable|numeric|min:0',
                'upload_cost_usd' => 'nullable|numeric|min:0',
                'transaction_id' => 'nullable|string|max:255',
                'bundlr_receipt' => 'nullable|array',
                'file_size_bytes' => 'nullable|integer|min:0',
                'mime_type' => 'nullable|string|max:255',
                'gateway_urls' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $data = $validator->validated();

            // Prepare data for insertion
            $insertData = [
                'user_id' => $user->id,
                'url' => $data['arweave_url'],
                'file_name' => $data['file_name'],
                'created_at' => now(),
                'is_encrypted' => (bool) ($data['is_encrypted'] ?? false), // Explicit boolean conversion for PostgreSQL
                'access_count' => 0
            ];

            // Add encryption metadata if file is encrypted
            if ((bool) ($data['is_encrypted'] ?? false)) {
                $insertData['encryption_method'] = $data['encryption_method'] ?? 'AES-256-GCM';
                $insertData['password_hash'] = $data['password_hash'];
                $insertData['salt'] = json_encode($data['salt']);
                $insertData['iv'] = json_encode($data['iv']);
            }

            // Add cost tracking data
            if (isset($data['upload_cost_matic'])) {
                $insertData['upload_cost_matic'] = $data['upload_cost_matic'];
            }
            if (isset($data['upload_cost_usd'])) {
                $insertData['upload_cost_usd'] = $data['upload_cost_usd'];
            }
            if (isset($data['transaction_id'])) {
                $insertData['transaction_id'] = $data['transaction_id'];
            }
            if (isset($data['bundlr_receipt'])) {
                $insertData['bundlr_receipt'] = json_encode($data['bundlr_receipt']);
            }
            if (isset($data['file_size_bytes'])) {
                $insertData['file_size_bytes'] = $data['file_size_bytes'];
            }
            if (isset($data['mime_type'])) {
                $insertData['mime_type'] = $data['mime_type'];
            }
            if (isset($data['gateway_urls'])) {
                $insertData['gateway_urls'] = json_encode($data['gateway_urls']);
            }

            // Insert into arweave_urls table
            $id = DB::table('arweave_urls')->insertGetId($insertData);

            Log::info('Arweave upload record saved', [
                'user_id' => $user->id,
                'file_name' => $data['file_name'],
                'url' => $data['arweave_url'],
                'is_encrypted' => $data['is_encrypted'] ?? false,
                'record_id' => $id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Upload record saved successfully',
                'id' => $id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save Arweave upload record', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save upload record'
            ], 500);
        }
    }

    /**
     * Get user's Arweave files with encryption status
     */
    public function getUserFiles(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $files = DB::table('arweave_urls')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->file_name,
                        'url' => $file->url,
                        'is_encrypted' => (bool) $file->is_encrypted,
                        'access_count' => $file->access_count ?? 0,
                        'last_accessed_at' => $file->last_accessed_at,
                        'created_at' => $file->created_at,
                        'can_access_directly' => !$file->is_encrypted // Public files can be accessed directly
                    ];
                });

            return response()->json([
                'success' => true,
                'files' => $files
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user Arweave files', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve files'
            ], 500);
        }
    }

    /**
     * Verify password and get decryption metadata for encrypted file
     */
    public function verifyFileAccess(Request $request, $fileId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $password = $request->input('password');

            // Get file record
            $file = DB::table('arweave_urls')
                ->where('id', $fileId)
                ->where('user_id', $user->id)
                ->where('is_encrypted', true)
                ->first();

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'Encrypted file not found'
                ], 404);
            }

            // Verify password using client-side compatible method
            // Note: We'll need to verify this client-side since we use Web Crypto API
            $decryptionData = [
                'url' => $file->url,
                'file_name' => $file->file_name,
                'salt' => json_decode($file->salt),
                'iv' => json_decode($file->iv),
                'password_hash' => $file->password_hash
            ];

            // Update access tracking
            DB::table('arweave_urls')
                ->where('id', $fileId)
                ->increment('access_count');
            
            DB::table('arweave_urls')
                ->where('id', $fileId)
                ->update(['last_accessed_at' => now()]);

            Log::info('File access granted', [
                'user_id' => $user->id,
                'file_id' => $fileId,
                'file_name' => $file->file_name
            ]);

            return response()->json([
                'success' => true,
                'decryption_data' => $decryptionData
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to verify file access', [
                'user_id' => Auth::id(),
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Access verification failed'
            ], 500);
        }
    }

    /**
     * Delete Arweave file record (note: actual file on Arweave cannot be deleted)
     */
    public function deleteFileRecord(Request $request, $fileId): JsonResponse
    {
        try {
            $user = Auth::user();

            $deleted = DB::table('arweave_urls')
                ->where('id', $fileId)
                ->where('user_id', $user->id)
                ->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'File record not found'
                ], 404);
            }

            Log::info('Arweave file record deleted', [
                'user_id' => $user->id,
                'file_id' => $fileId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File record deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete Arweave file record', [
                'user_id' => Auth::id(),
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file record'
            ], 500);
        }
    }

    /**
     * Get detailed file information
     */
    public function getFileDetails(Request $request, $fileId): JsonResponse
    {
        try {
            $user = Auth::user();

            $file = DB::table('arweave_urls')
                ->where('id', $fileId)
                ->where('user_id', $user->id)
                ->first();

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            // Parse JSON fields
            $fileData = [
                'id' => $file->id,
                'file_name' => $file->file_name,
                'url' => $file->url,
                'is_encrypted' => (bool) $file->is_encrypted,
                'encryption_method' => $file->encryption_method,
                'file_size_bytes' => $file->file_size_bytes,
                'mime_type' => $file->mime_type,
                'upload_cost_matic' => $file->upload_cost_matic,
                'upload_cost_usd' => $file->upload_cost_usd,
                'transaction_id' => $file->transaction_id,
                'access_count' => $file->access_count ?? 0,
                'last_accessed_at' => $file->last_accessed_at,
                'created_at' => $file->created_at,
                'bundlr_receipt' => $file->bundlr_receipt ? json_decode($file->bundlr_receipt, true) : null,
                'gateway_urls' => $file->gateway_urls ? json_decode($file->gateway_urls, true) : null
            ];

            return response()->json([
                'success' => true,
                'file' => $fileData
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get file details', [
                'user_id' => Auth::id(),
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get file details'
            ], 500);
        }
    }

    /**
     * Get file statistics and cost analytics
     */
    public function getFileStats(): JsonResponse
    {
        try {
            $user = Auth::user();

            $stats = DB::table('arweave_urls')
                ->where('user_id', $user->id)
                ->selectRaw('
                    COUNT(*) as total_files,
                    COUNT(CASE WHEN is_encrypted = true THEN 1 END) as encrypted_files,
                    COUNT(CASE WHEN is_encrypted = false THEN 1 END) as public_files,
                    SUM(access_count) as total_accesses,
                    SUM(upload_cost_matic) as total_cost_matic,
                    SUM(upload_cost_usd) as total_cost_usd,
                    AVG(upload_cost_matic) as avg_cost_matic,
                    SUM(file_size_bytes) as total_size_bytes
                ')
                ->first();

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_files' => $stats->total_files ?? 0,
                    'encrypted_files' => $stats->encrypted_files ?? 0,
                    'public_files' => $stats->public_files ?? 0,
                    'total_accesses' => $stats->total_accesses ?? 0,
                    'total_cost_matic' => round($stats->total_cost_matic ?? 0, 8),
                    'total_cost_usd' => round($stats->total_cost_usd ?? 0, 2),
                    'avg_cost_matic' => round($stats->avg_cost_matic ?? 0, 8),
                    'total_size_bytes' => $stats->total_size_bytes ?? 0
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get file stats', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics'
            ], 500);
        }
    }
}
