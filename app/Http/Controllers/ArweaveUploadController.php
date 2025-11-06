<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

class ArweaveUploadController extends Controller
{
    public function __construct()
    {
        Log::info('🔧 ArweaveUploadController constructed');
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Calculate upload cost based on file size
     * Cost: 0.005 MATIC per MB with 0.005 MATIC minimum
     */
    private function calculateUploadCost(int $fileSizeBytes): float
    {
        $fileSizeMB = $fileSizeBytes / (1024 * 1024);
        $costPerMB = 0.005; // MATIC per MB
        $minimumCost = 0.005; // MATIC
        
        $calculatedCost = $fileSizeMB * $costPerMB;
        return max($calculatedCost, $minimumCost);
    }

    /**
     * Format bytes to human readable format
     */
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
     * Validate if a file can be uploaded to Arweave before actual upload
     */
    public function preflightValidation(Request $request): JsonResponse
    {
        try {
            Log::info('🚀 Arweave preflight validation started', [
                'file_id' => $request->input('file_id'),
                'user_id' => Auth::id(),
                'route' => $request->route()->getName() ?? 'unknown',
                'method' => $request->method(),
                'path' => $request->path(),
            ]);

            $request->validate([
                'file_id' => 'required|integer|exists:files,id',
            ]);

            Log::info('✅ Validation passed, fetching file', [
                'file_id' => $request->integer('file_id'),
                'user_id' => Auth::id(),
            ]);

            $file = File::where('id', $request->integer('file_id'))
                ->where('user_id', Auth::id())
                ->firstOrFail();

            Log::info('✅ File found', [
                'file_id' => $file->id,
                'file_name' => $file->file_name,
                'file_size' => $file->file_size,
                'user_id' => Auth::id(),
            ]);

            // Validate file is not a folder
            if ($file->is_folder) {
                return response()->json([
                    'success' => false,
                    'validation' => [
                        'errors' => ['Folders cannot be uploaded to Arweave']
                    ]
                ], 422);
            }

            // Validate file exists in Supabase storage
            Log::info('🔍 Checking if file exists in Supabase storage', [
                'file_id' => $file->id,
                'file_path' => $file->file_path,
            ]);
            
            $supabaseUrl = env('SUPABASE_URL');
            $fileExists = $this->checkFileExistsInSupabase($file->file_path);
            
            Log::info('📁 Supabase storage check result', [
                'file_path' => $file->file_path,
                'exists' => $fileExists,
                'supabase_url' => $supabaseUrl ? '✅ Configured' : '❌ Missing',
            ]);
            
            if (!$fileExists) {
                Log::error('❌ File not found in Supabase storage', [
                    'file_id' => $file->id,
                    'file_path' => $file->file_path,
                    'supabase_url' => $supabaseUrl,
                ]);
                
                return response()->json([
                    'success' => false,
                    'validation' => [
                        'errors' => ['File not found in Supabase storage. The file may have been deleted.']
                    ]
                ], 404);
            }

            // Validate file size (max 100MB)
            $maxSize = 100 * 1024 * 1024; // 100MB
            if ($file->file_size > $maxSize) {
                return response()->json([
                    'success' => false,
                    'validation' => [
                        'errors' => ['File too large for Arweave upload (max 100MB)']
                    ]
                ], 422);
            }

            // Calculate upload cost
            $uploadCost = $this->calculateUploadCost($file->file_size);

            // Get file info
            $fileInfo = [
                'id' => $file->id,
                'name' => $file->file_name,
                'size' => $file->file_size,
                'size_human' => $this->formatBytes($file->file_size),
                'type' => $file->mime_type,
                'extension' => pathinfo($file->file_name, PATHINFO_EXTENSION),
            ];

            $responseData = [
                'success' => true,
                'validation' => [
                    'errors' => [],
                    'warnings' => [],
                    'file_info' => $fileInfo,
                ],
                'upload_cost' => [
                    'matic' => round($uploadCost, 6),
                    'usd' => round($uploadCost * 0.7, 4), // Approximate USD conversion (0.7 USDC per MATIC)
                    'formatted' => round($uploadCost, 6) . ' MATIC (~$' . round($uploadCost * 0.7, 4) . ')',
                ],
                'requirements' => [
                    'max_file_size' => 100 * 1024 * 1024,
                    'max_file_size_human' => '100 MB',
                ]
            ];

            Log::info('✅ Returning success response', [
                'file_id' => $file->id,
                'upload_cost' => $responseData['upload_cost']['matic'],
                'response_keys' => array_keys($responseData),
            ]);

            $response = response()->json($responseData, 200);
            
            Log::info('✅ Response object created', [
                'status_code' => $response->status(),
                'content_type' => $response->headers->get('content-type'),
            ]);

            return $response;

        } catch (ValidationException $ve) {
            Log::warning('❌ Arweave validation failed', [
                'errors' => $ve->errors(),
                'file_id' => $request->input('file_id'),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $ve->getMessage(),
                'errors' => $ve->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('❌ Arweave preflight validation error', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'file_id' => $request->input('file_id'),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload an existing user file to Arweave
     */
    public function uploadExistingFile(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_id' => 'required|integer|exists:files,id',
                'transaction_id' => 'nullable|string', // Bundlr transaction ID
                'arweave_url' => 'nullable|url', // Arweave gateway URL
                'upload_cost_matic' => 'nullable|numeric|min:0',
            ]);

            $file = File::where('id', $request->integer('file_id'))
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Validate file still exists
            if (!Storage::exists($file->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found in storage',
                ], 404);
            }

            // Validate file size
            if ($file->file_size > 100 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'File too large for Arweave upload (max 100MB)',
                ], 422);
            }

            $transactionId = $request->input('transaction_id');
            $arweaveUrl = $request->input('arweave_url');
            $uploadCostMatic = $request->input('upload_cost_matic');

            // If no Arweave URL provided, we can't complete the upload
            if (!$arweaveUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Arweave URL is required',
                ], 422);
            }

            // Calculate cost if not provided
            if (!$uploadCostMatic) {
                $uploadCostMatic = $this->calculateUploadCost($file->file_size);
            }

            // Update file record with Arweave metadata
            $file->update([
                'is_arweave' => true,
                'arweave_url' => $arweaveUrl,
            ]);

            // Save upload record to arweave_urls table
            $arweaveRecord = \DB::table('arweave_urls')->insertGetId([
                'user_id' => Auth::id(),
                'url' => $arweaveUrl,
                'file_name' => $file->file_name,
                'is_encrypted' => false,
                'file_size_bytes' => $file->file_size,
                'mime_type' => $file->mime_type,
                'upload_cost_matic' => $uploadCostMatic,
                'upload_cost_usd' => round($uploadCostMatic * 0.7, 4),
                'transaction_id' => $transactionId,
                'access_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('File uploaded to Arweave', [
                'file_id' => $file->id,
                'arweave_url' => $arweaveUrl,
                'transaction_id' => $transactionId,
                'upload_cost_matic' => $uploadCostMatic,
                'user_id' => Auth::id(),
                'arweave_record_id' => $arweaveRecord,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded to Arweave successfully',
                'file' => [
                    'id' => $file->id,
                    'name' => $file->file_name,
                    'arweave_url' => $arweaveUrl,
                    'transaction_id' => $transactionId,
                    'upload_cost_matic' => $uploadCostMatic,
                    'upload_cost_usd' => round($uploadCostMatic * 0.7, 4),
                ],
                'arweave_record_id' => $arweaveRecord,
            ]);

        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->getMessage(),
                'errors' => $ve->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Arweave upload error', [
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
     * Get user's Arweave uploads
     */
    public function getUserUploads(): JsonResponse
    {
        try {
            $uploads = \DB::table('arweave_urls')
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($upload) {
                    return [
                        'id' => $upload->id,
                        'file_name' => $upload->file_name,
                        'url' => $upload->url,
                        'file_size_bytes' => $upload->file_size_bytes,
                        'file_size_human' => $this->formatBytes($upload->file_size_bytes),
                        'mime_type' => $upload->mime_type,
                        'upload_cost_matic' => $upload->upload_cost_matic,
                        'upload_cost_usd' => $upload->upload_cost_usd,
                        'transaction_id' => $upload->transaction_id,
                        'access_count' => $upload->access_count,
                        'created_at' => $upload->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'uploads' => $uploads,
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to get Arweave uploads', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve uploads: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if file exists in Supabase storage
     */
    private function checkFileExistsInSupabase(string $filePath): bool
    {
        try {
            $supabaseUrl = env('SUPABASE_URL');
            $supabaseKey = env('SUPABASE_SERVICE_ROLE_KEY');
            
            if (!$supabaseUrl || !$supabaseKey) {
                Log::error('Supabase configuration missing for file existence check', [
                    'has_url' => !empty($supabaseUrl),
                    'has_key' => !empty($supabaseKey),
                ]);
                return false;
            }
            
            $client = new \GuzzleHttp\Client([
                'base_uri' => $supabaseUrl,
                'verify' => !(config('app.env') === 'local' || config('app.debug')),
                'timeout' => 10,
            ]);
            
            // Try to HEAD the file to check existence
            $response = $client->head("/storage/v1/object/public/docs/{$filePath}", [
                'http_errors' => false,
                'headers' => ['Authorization' => 'Bearer ' . $supabaseKey]
            ]);
            
            $statusCode = $response->getStatusCode();
            Log::debug('Supabase file existence check', [
                'file_path' => $filePath,
                'status_code' => $statusCode,
                'exists' => $statusCode === 200,
            ]);
            
            return $statusCode === 200;
            
        } catch (\Exception $e) {
            Log::error('Error checking file existence in Supabase', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get Arweave upload statistics
     */
    public function getUploadStats(): JsonResponse
    {
        try {
            $user = Auth::user();

            $stats = \DB::table('arweave_urls')
                ->where('user_id', $user->id)
                ->selectRaw('
                    COUNT(*) as total_uploads,
                    SUM(file_size_bytes) as total_size_bytes,
                    SUM(upload_cost_matic) as total_cost_matic,
                    SUM(upload_cost_usd) as total_cost_usd,
                    AVG(upload_cost_matic) as avg_cost_matic,
                    SUM(access_count) as total_accesses
                ')
                ->first();

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_uploads' => $stats->total_uploads ?? 0,
                    'total_size_bytes' => $stats->total_size_bytes ?? 0,
                    'total_size_human' => $this->formatBytes($stats->total_size_bytes ?? 0),
                    'total_cost_matic' => round($stats->total_cost_matic ?? 0, 6),
                    'total_cost_usd' => round($stats->total_cost_usd ?? 0, 2),
                    'avg_cost_matic' => round($stats->avg_cost_matic ?? 0, 6),
                    'total_accesses' => $stats->total_accesses ?? 0,
                ]
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to get Arweave stats', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage(),
            ], 500);
        }
    }
}
