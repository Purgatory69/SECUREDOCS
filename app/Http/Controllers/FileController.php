<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\User;
use App\Services\BlockchainStorage\BlockchainStorageManager;
use App\Services\BlockchainStorage\BlockchainPreflightValidator;
use App\Services\VectorStoreManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Str;

class FileController extends Controller
{
    protected BlockchainStorageManager $blockchainManager;
    protected BlockchainPreflightValidator $validator;
    protected VectorStoreManager $vectorManager;

    public function __construct(BlockchainStorageManager $blockchainManager, BlockchainPreflightValidator $validator, VectorStoreManager $vectorManager)
    {
        $this->blockchainManager = $blockchainManager;
        $this->validator = $validator;
        $this->vectorManager = $vectorManager;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        // Normalize incoming query params
        $parentRaw = $request->query('parent_id', null);
        $search = $request->query('q');

        // Explicitly exclude soft-deleted items to ensure trashed files don't appear in main list
        // Also exclude blockchain-only files (file_path starts with 'ipfs://')
        $query = $user->files()->whereNull('deleted_at')
            ->where(function($q) {
                $q->whereNull('file_path')
                  ->orWhere('file_path', 'not like', 'ipfs://%');
            });

        // Apply parent filter robustly
        if ($request->has('parent_id')) {
            if ($parentRaw === null || $parentRaw === '' || $parentRaw === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', (int) $parentRaw);
            }
        } else {
            $query->whereNull('parent_id');
        }

        if ($search) {
            $query->where('file_name', 'LIKE', '%' . $search . '%');
        }

        $files = $query->orderBy('is_folder', 'desc')->orderBy('file_name', 'asc')->paginate(20);

        return response()->json($files);
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        $user = Auth::user();
        // Include trashed so the frontend can inspect items in trash as well
        $file = $user->files()->withTrashed()->findOrFail($id);
        return response()->json($file);
    }

    /**
     * Show file preview page.
     */
    public function preview($id)
    {
        $user = Auth::user();
        $file = $user->files()->findOrFail($id);
        
        // Only allow preview for actual files, not folders
        if ($file->is_folder) {
            return redirect()->route('user.dashboard')->with('error', 'Folders cannot be previewed');
        }
        
        return view('file-preview', compact('file'));
    }

    /**
     * Proxy file content to bypass CORS restrictions
     */
    public function proxyFile($id)
    {
        $user = Auth::user();
        // Allow proxying even for trashed items so previews work from Trash view
        $file = $user->files()->withTrashed()->findOrFail($id);
        
        if ($file->is_folder) {
            abort(404, 'Cannot proxy folder');
        }

        $supabaseUrl = env('SUPABASE_URL');
        $filePath = $file->file_path;

        if (empty($supabaseUrl) || empty($filePath)) {
            Log::error('Proxy config or file path missing', [
                'file_id' => $id,
                'supabase_url_set' => !empty($supabaseUrl),
                'file_path_set' => !empty($filePath),
            ]);
            abort(404);
        }

        $publicUrl = "{$supabaseUrl}/storage/v1/object/public/docs/{$filePath}";

        try {
            $client = new Client();
            $response = $client->get($publicUrl);
            
            $content = $response->getBody()->getContents();
            $contentType = $response->getHeader('Content-Type')[0] ?? $file->mime_type ?? 'application/octet-stream';
            
            return response($content)
                ->header('Content-Type', $contentType)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                ->header('Content-Disposition', 'inline; filename="' . $file->file_name . '"')
                ->header('Cache-Control', 'public, max-age=3600');
                
        } catch (\Exception $e) {
            Log::error('Error proxying file', [
                'file_id' => $id,
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            abort(404, 'File not found or cannot be accessed');
        }
    }

    /**
     * Restore vectors for a file (clear soft-delete flags in vector DB)
     */
    public function restoreVectors(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            // Allow restoring vectors even if the file is currently in trash
            $file = $user->files()->withTrashed()->findOrFail($id);

            if (!$file->isVectorized()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is not vectorized'
                ], 400);
            }

            $restored = $this->vectorManager->restoreHidden($file->id, $user->id);

            if (!$restored) {
                Log::warning('Failed to restore vectors (soft-deleted) for file', [
                    'file_id' => $file->id,
                    'user_id' => $user->id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to restore vectors'
                ], 500);
            }

            Log::info('Vectors restored (soft-delete cleared) for file', [
                'file_id' => $file->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vectors restored successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error restoring vectors for file', [
                'file_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to restore vectors'
            ], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        Log::info('File store request received', ['user_id' => $user->id, 'data' => $request->all()]);

        $validated = $request->validate([
            'file_name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('files', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->whereRaw('is_folder IS TRUE');
                }),
            ],
            'is_folder' => 'required|boolean',
            'file_path' => 'sometimes|string',
            'file_size' => 'sometimes|integer',
            'file_type' => 'sometimes|string',
            'mime_type' => 'sometimes|string',
            'processing_type' => 'sometimes|string|in:standard,blockchain,vectorize,hybrid',
        ]);

        $parentId = $validated['parent_id'] ?? null;
        
        // Force boolean conversion for PostgreSQL compatibility - explicit casting
        $isFolder = $validated['is_folder'] === true || $validated['is_folder'] === 'true' || $validated['is_folder'] === '1' || $validated['is_folder'] === 1;

        // Duplicate name check within the same directory (files only)
        // Use PostgreSQL-safe boolean handling and ignore trashed items
        $duplicateCheck = $user->files()
            ->whereNull('deleted_at')
            ->where('file_name', $validated['file_name'])
            ->whereRaw('is_folder IS FALSE');
        if ($parentId) {
            $duplicateCheck->where('parent_id', $parentId);
        } else {
            $duplicateCheck->whereNull('parent_id');
        }
        if ($duplicateCheck->exists()) {
            return response()->json(['message' => 'A file with this name already exists in this directory.'], 409);
        }

        // Create file record using raw SQL insert to force boolean casting
        $item = File::create([
            'user_id' => $user->id,
            'file_name' => $validated['file_name'],
            'parent_id' => $parentId,
            'is_folder' => DB::raw($isFolder ? 'TRUE' : 'FALSE'),
            'file_path' => $validated['file_path'] ?? '',
            'file_size' => $validated['file_size'] ?? 0,
            'file_type' => $validated['file_type'] ?? ($isFolder ? 'folder' : 'file'),
            'mime_type' => $validated['mime_type'] ?? ($isFolder ? 'inode/directory' : 'application/octet-stream'),
        ]);

        // Handle post-upload processing based on processing_type
        $processingType = $validated['processing_type'] ?? 'standard';
        if (!$isFolder && $processingType !== 'standard') {
            $this->handlePostUploadProcessing($item, $processingType, $user);
        }

        return response()->json($item, 201);
    }

    /**
     * Remove file from vector database
     */
    public function removeFromVector(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $file = $user->files()->findOrFail($id);

            if (!$file->isVectorized()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is not vectorized'
                ], 400);
            }

            // Remove vectors using VectorStoreManager
            $vectorsRemoved = $this->vectorManager->unvector($file->id, $user->id);

            if (!$vectorsRemoved) {
                Log::warning('Failed to remove vectors from database', [
                    'file_id' => $file->id,
                    'user_id' => $user->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove file from vector database'
                ], 500);
            }

            Log::info('File removed from vector database', [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'vectors_removed' => $vectorsRemoved
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File removed from vector database',
                'file' => $file->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to remove file from vector database', [
                'file_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove file from vector database'
            ], 500);
        }
    }

    /**
     * Add a file to the AI vector database by sending it to N8N webhook
     */
    public function addToVector(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $file = $user->files()->findOrFail($id);

            // Check if file is already vectorized
            if ($file->isVectorized()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is already vectorized'
                ], 400);
            }

            // Check if user is premium
            if (!$user->is_premium) {
                return response()->json([
                    'success' => false,
                    'message' => 'Premium subscription required for AI vectorization'
                ], 403);
            }

            // Get N8N webhook URL
            $n8nWebhookUrl = config('services.n8n.premium_webhook_url');
            if (empty($n8nWebhookUrl)) {
                Log::error('N8n webhook URL not configured', [
                    'file_id' => $file->id,
                    'user_id' => $user->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Vectorization service is not available'
                ], 503);
            }

            Log::info('Starting file vectorization via N8n webhook', [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'file_name' => $file->file_name,
                'webhook_url' => $n8nWebhookUrl
            ]);

            // Prepare payload similar to upload flow
            $payload = array_merge($file->toArray(), [
                'user_id_for_n8n' => $user->id,
                'processing_type' => 'vectorization',
                'timestamp' => now()->toISOString(),
                'source' => 'manual_add_to_vector'
            ]);

            // Send to N8N webhook
            $response = Http::timeout(30)->post($n8nWebhookUrl, $payload);

            if ($response->successful()) {
                Log::info('N8n vectorization webhook call successful', [
                    'file_id' => $file->id,
                    'user_id' => $user->id,
                    'response_status' => $response->status()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'File sent for vectorization processing',
                    'file' => $file->fresh()
                ]);
            } else {
                Log::error('N8n vectorization webhook call failed', [
                    'file_id' => $file->id,
                    'user_id' => $user->id,
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send file for vectorization'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Exception during add to vector', [
                'file_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding to vector database',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle vectorization completion notification from N8N
     */
    public function handleVectorizationComplete(Request $request): JsonResponse
    {
        try {
            $fileId = $request->input('file_id');
            $userId = $request->input('user_id');
            $status = $request->input('status');
            
            Log::info('Received vectorization completion notification', [
                'file_id' => $fileId,
                'user_id' => $userId,
                'status' => $status,
                'payload' => $request->all()
            ]);

            if (!$fileId || !$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing file_id or user_id'
                ], 400);
            }

            // Find the file and mark as vectorized
            $file = File::find($fileId);
            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            // Mark file as vectorized if status is completed
            if ($status === 'completed') {
                $file->markAsVectorized([
                    'n8n_completion_timestamp' => now()->toISOString(),
                    'notification_received' => true
                ]);

                // Find user and send notification
                $user = User::find($userId);
                if ($user) {
                    // Create database notification
                    $user->notifications()->create([
                        'type' => 'vectorization_complete',
                        'title' => 'AI Vectorization Complete',
                        'message' => "File '{$file->file_name}' has been successfully added to AI vector database",
                        'data' => [
                            'file_id' => $fileId,
                            'file_name' => $file->file_name,
                            'action' => 'vectorization_complete'
                        ]
                    ]);
                }

                Log::info('File marked as vectorized from N8N completion', [
                    'file_id' => $fileId,
                    'user_id' => $userId,
                    'file_name' => $file->file_name
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Vectorization completion processed'
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling vectorization completion', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing completion notification'
            ], 500);
        }
    }

    /**
     * Remove file from blockchain storage
     */
    public function removeFromBlockchain(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $file = $user->files()->findOrFail($id);

            if (!$file->isStoredOnBlockchain()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is not stored on blockchain'
                ], 400);
            }

            // Use blockchain manager to remove file
            $result = $this->blockchainManager->removeFile($file);

            if ($result) {
                Log::info('File removed from blockchain storage', [
                    'file_id' => $file->id,
                    'user_id' => $user->id,
                    'ipfs_hash' => $file->ipfs_hash
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'File removed from blockchain storage successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove file from blockchain storage'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to remove file from blockchain storage', [
                'file_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove file from blockchain storage'
            ], 500);
        }
    }

    /**
     * Get file processing status
     */
    public function getProcessingStatus(File $file): JsonResponse
    {
        try {
            $user = Auth::user();
            if ($file->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $status = $file->getProcessingStatus();
            // Include whether vectors are currently soft-deleted (hidden)
            $status['vectors_soft_deleted'] = $file->isVectorized()
                ? $this->vectorManager->areVectorsSoftDeleted($file->id, $user->id)
                : false;

            return response()->json([
                'success' => true,
                'status' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get processing status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download blockchain file to Supabase storage
     */
    public function downloadFromBlockchain(File $file): JsonResponse
    {
        try {
            if ($file->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            if (!$file->isStoredOnBlockchain()) {
                return response()->json(['success' => false, 'message' => 'File is not on blockchain'], 400);
            }

            // Download from IPFS and save to Supabase
            $provider = $this->blockchainManager->provider($file->blockchain_provider);
            $content = $provider->getFile($file->ipfs_hash);
            
            if (!$content) {
                return response()->json(['success' => false, 'message' => 'Failed to download from blockchain'], 500);
            }

            // Save to Supabase storage
            $path = 'files/' . Auth::id() . '/' . uniqid() . '_' . $file->file_name;
            Storage::put($path, $content);

            // Update file record
            $file->update(['file_path' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'File downloaded to Supabase storage'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle post-upload processing for blockchain and n8n workflows
{{ ... }}
     */
    protected function handlePostUploadProcessing(File $file, string $processingType, $user): void
    {
        try {
            Log::info('Starting post-upload processing', [
                'file_id' => $file->id,
                'processing_type' => $processingType,
                'user_id' => $user->id
            ]);

            switch ($processingType) {
                case 'blockchain':
                    $this->processBlockchainUpload($file, $user);
                    break;
                
                case 'vectorize':
                    $this->processN8nVectorization($file, $user);
                    break;
                
                case 'hybrid':
                    $this->processBlockchainUpload($file, $user);
                    $this->processN8nVectorization($file, $user);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Post-upload processing failed', [
                'file_id' => $file->id,
                'processing_type' => $processingType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process blockchain upload
     */
    protected function processBlockchainUpload(File $file, $user): void
    {
        try {
            // Create UploadedFile from existing file
            $filePath = Storage::path($file->file_path);
            
            if (!file_exists($filePath)) {
                Log::error('File not found for blockchain upload', [
                    'file_id' => $file->id,
                    'file_path' => $file->file_path
                ]);
                return;
            }

            $uploadedFile = new UploadedFile(
                $filePath,
                $file->file_name,
                $file->mime_type,
                null,
                true // Mark as test to avoid validation errors
            );

            // Upload to blockchain with tracking
            $result = $this->blockchainManager->uploadFileWithTracking(
                $file, 
                $uploadedFile, 
                $user
            );

            if ($result['success']) {
                Log::info('Blockchain upload successful during post-processing', [
                    'file_id' => $file->id,
                    'ipfs_hash' => $result['ipfs_hash']
                ]);
                
                // File should already be updated by uploadFileWithTracking, but ensure consistency
                $file->refresh();
            } else {
                Log::error('Blockchain upload failed during post-processing', [
                    'file_id' => $file->id,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception during blockchain upload processing', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process n8n vectorization via direct webhook call
     */
    protected function processN8nVectorization(File $file, $user): void
    {
        try {
            // Check if user is premium
            if (!$user->is_premium) {
                Log::info('N8n vectorization skipped: User is not premium', [
                    'file_id' => $file->id,
                    'user_id' => $user->id
                ]);
                return;
            }

            $n8nWebhookUrl = config('services.n8n.premium_webhook_url');
            
            if (empty($n8nWebhookUrl)) {
                Log::error('N8n webhook URL not configured', [
                    'file_id' => $file->id,
                    'user_id' => $user->id
                ]);
                return;
            }

            Log::info('Starting N8n vectorization via direct webhook', [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'webhook_url' => $n8nWebhookUrl
            ]);

            // Prepare payload with file metadata
            $payload = array_merge($file->toArray(), [
                'user_id_for_n8n' => $user->id,
                'processing_type' => 'vectorization',
                'timestamp' => now()->toISOString()
            ]);

            // Make direct HTTP call to n8n webhook
            $response = Http::timeout(30)->post($n8nWebhookUrl, $payload);

            if ($response->successful()) {
                // Mark file as vectorized
                $responseData = $response->json();
                $file->markAsVectorized([
                    'webhook_response' => $responseData,
                    'processing_timestamp' => now()->toISOString(),
                    'webhook_url' => $n8nWebhookUrl
                ]);

                Log::info('N8n vectorization webhook call successful', [
                    'file_id' => $file->id,
                    'user_id' => $user->id,
                    'response_status' => $response->status()
                ]);
            } else {
                Log::error('N8n vectorization webhook call failed', [
                    'file_id' => $file->id,
                    'user_id' => $user->id,
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception during N8n vectorization webhook call', [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Move a file or folder to the trash (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            Log::info('Destroy request received', [
                'user_id' => Auth::id(),
                'file_id' => $id,
                'via' => 'ajax',
            ]);
            $file = Auth::user()->files()->findOrFail($id);

            DB::transaction(function () use ($file) {
                if ($file->is_folder) {
                    $this->trashFolder($file);
                } else {
                    $this->trashFile($file);
                }
            });

            $message = $file->is_folder ? 'Folder moved to trash' : 'File moved to trash';
            return response()->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            Log::error('Error moving file/folder to trash', [
                'file_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error moving item to trash',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recursively move a folder and its contents to trash
     */
    private function trashFolder($folder)
    {
        $allChildren = $this->getAllChildren($folder, true);
        $allChildren->push($folder);

        foreach ($allChildren as $item) {
            if (!$item->is_folder && !empty($item->file_path)) {
                $this->moveFileToTrashStorage($item);
            }
            $item->delete(); // Soft delete
        }
    }

    /**
     * Move a single file to trash
     */
    private function trashFile($file)
    {
        if (!empty($file->file_path)) {
            $this->moveFileToTrashStorage($file);
        }
        
        // Soft-hide vectors if file is vectorized
        if ($file->isVectorized()) {
            $vectorsHidden = $this->vectorManager->softHide($file->id, $file->user_id);
            Log::info('Vectors soft-hidden for trashed file', [
                'file_id' => $file->id,
                'user_id' => $file->user_id,
                'vectors_hidden' => $vectorsHidden
            ]);
        }
        
        $file->delete(); // Soft delete
    }

    /**
     * Recursively get all children of a folder, including nested children.
     */
    private function getAllChildren($folder, $includeFolders = false)
    {
        $allChildren = collect();
        $children = $folder->children()->get();

        foreach ($children as $child) {
            if ($includeFolders || !$child->is_folder) {
                $allChildren->push($child);
            }
            if ($child->is_folder) {
                $allChildren = $allChildren->merge($this->getAllChildren($child, $includeFolders));
            }
        }
        return $allChildren;
    }

    /**
     * Move a file in Supabase storage to the 'trash' directory
     */
    private function moveFileToTrashStorage($file)
    {
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = config('services.supabase.service_key');
        $bucketName = 'docs';

        if (!$supabaseUrl || !$supabaseKey || empty($file->file_path)) {
            Log::error('Cannot move file to trash due to missing config or file path.', ['file_id' => $file->id]);
            return;
        }

        $client = new Client(['base_uri' => $supabaseUrl]);
        $originalPath = $file->file_path;
        $trashPath = 'trash/' . $originalPath;

        try {
            Log::info('Moving file to trash in Supabase', ['from' => $originalPath, 'to' => $trashPath]);

            $client->post("/storage/v1/object/move", [
                'headers' => ['Authorization' => 'Bearer ' . $supabaseKey, 'Content-Type' => 'application/json'],
                'json' => [
                    'bucketId' => $bucketName,
                    'sourceKey' => $originalPath,
                    'destinationKey' => $trashPath,
                ]
            ]);

            // Update the file path in the database to the new trash path
            $file->file_path = $trashPath;
            $file->save();

            Log::info('File moved to trash successfully', ['file_id' => $file->id, 'new_path' => $trashPath]);

        } catch (RequestException $e) {
            Log::error('Failed to move file to trash in Supabase', [
                'file_path' => $originalPath,
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'N/A',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a new folder
     */
    public function createFolder(Request $request)
    {
        $user = Auth::user();

        Log::info('Folder creation request started.', [
            'user_id' => $user->id, 
            'request_data' => $request->all()
        ]);

        try {
            $validated = $request->validate([
                'file_name' => [
                    'required', 
                    'string', 
                    'max:255', 
                    'regex:/^[a-zA-Z0-9_ .-]+$/'
                ],
                'parent_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('files', 'id')->where('user_id', $user->id)->where('is_folder', true),
                ],
            ], [
                'file_name.regex' => 'Folder name can only contain letters, numbers, spaces, underscores, dots, and hyphens.',
                'parent_id.exists' => 'The selected parent folder is invalid or does not exist.',
            ]);

            $parentId = $validated['parent_id'] ?? null;
            $folderName = $validated['file_name'];

            // Check for duplicate folder name in the same directory
            $duplicateCheck = $user->files()
                ->where('file_name', $folderName)
                ->whereRaw('is_folder IS TRUE');

            if ($parentId) {
                $duplicateCheck->where('parent_id', $parentId);
            } else {
                $duplicateCheck->whereNull('parent_id');
            }

            if ($duplicateCheck->exists()) {
                Log::warning('Duplicate folder creation attempt.', ['name' => $folderName, 'parent_id' => $parentId]);
                return response()->json(['message' => 'A folder with this name already exists in this directory.'], 409);
            }

            // Construct the folder path
            $path = 'user_' . $user->id . '/' . $folderName;
            if ($parentId) {
                $parent = File::find($parentId);
                // Ensure parent exists and has a path before trying to append to it
                if ($parent && !empty($parent->file_path)) {
                    $path = rtrim($parent->file_path, '/') . '/' . $folderName;
                } else {
                    // This is a fallback. With the fix, all folders should have a path.
                    Log::warning('Parent folder found but has no file_path. Constructing path from root.', ['parent_id' => $parentId]);
                }
            }

            $folder = new File();
            $folder->user_id = $user->id;
            $folder->file_name = $folderName;
            $folder->is_folder = DB::raw('true');
            $folder->parent_id = $parentId;
            $folder->file_path = $path;
            $folder->file_size = 0;
            $folder->file_type = 'folder';
            $folder->mime_type = 'inode/directory';
            $folder->save();

            Log::info('Folder created successfully', ['folder_id' => $folder->id, 'path' => $path]);

            return response()->json($folder, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Folder creation validation error', ['errors' => $e->errors()]);
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred while creating folder', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'An unexpected error occurred while creating the folder.'], 500);
        }
    }

    /**
     * Display a listing of the trashed resources.
     */
    public function indexTrash(Request $request)
    {
        $user = Auth::user();
        $files = $user->files()->onlyTrashed()->orderBy('deleted_at', 'desc')->get();
        return response()->json($files);
    }

    /**
     * Backwards compatible alias for indexTrash used by routes: /files/trash
     */
    public function getTrashItems(Request $request)
    {
        return $this->indexTrash($request);
    }

    /**
     * Restore a soft-deleted file or folder from the trash.
     */
    public function restore($id): JsonResponse
    {
        try {
            // Use withTrashed to find the file, as it's soft-deleted.
            $file = Auth::user()->files()->withTrashed()->findOrFail($id);

            DB::transaction(function () use ($file) {
                $this->restoreItem($file);
            });

            $message = $file->is_folder ? 'Folder restored successfully' : 'File restored successfully';
            return response()->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            Log::error('Error restoring item from trash', [
                'file_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['success' => false, 'error' => 'Error restoring item', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Permanently delete a file or folder from the trash.
     */
    public function forceDelete($id): JsonResponse
    {
        try {
            $file = Auth::user()->files()->withTrashed()->findOrFail($id);

            DB::transaction(function () use ($file) {
                if ($file->is_folder) {
                    $allChildren = $this->getAllChildren($file, true);
                    $allChildren->push($file);

                    // Clean up vectors for all vectorized files in folder
                    $vectorizedFiles = $allChildren->where('is_folder', false)->filter(function($item) {
                        return $item->isVectorized();
                    });
                    
                    foreach($vectorizedFiles as $vectorizedFile) {
                        $this->vectorManager->unvector($vectorizedFile->id, $vectorizedFile->user_id);
                        Log::info('Vectors removed for force-deleted file', [
                            'file_id' => $vectorizedFile->id,
                            'user_id' => $vectorizedFile->user_id
                        ]);
                    }

                    $filesToDeleteFromStorage = $allChildren->where('is_folder', false)->where('file_path', '!=', '');
                    if ($filesToDeleteFromStorage->isNotEmpty()) {
                        $this->deleteFilesFromStorage($filesToDeleteFromStorage);
                    }
                    
                    // Force delete all children and the folder itself
                    foreach($allChildren as $item) {
                        $item->forceDelete();
                    }
                } else {
                    // Clean up vectors for single file if vectorized
                    if ($file->isVectorized()) {
                        $this->vectorManager->unvector($file->id, $file->user_id);
                        Log::info('Vectors removed for force-deleted file', [
                            'file_id' => $file->id,
                            'user_id' => $file->user_id
                        ]);
                    }
                    
                    if (!empty($file->file_path)) {
                        $this->deleteFilesFromStorage(collect([$file]));
                    }
                    $file->forceDelete();
                }
            });

            return response()->json(['success' => true, 'message' => 'Item permanently deleted']);

        } catch (\Exception $e) {
            Log::error('Error permanently deleting item', [
                'file_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['success' => false, 'error' => 'Error permanently deleting item', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper to restore a file or folder and its contents.
     */
    private function restoreItem($item)
    {
        // If it's a folder, restore all its children first
        if ($item->is_folder) {
            $children = $this->getAllChildren($item, true);
            foreach ($children as $child) {
                $this->restoreItem($child);
            }
        }

        // For files, move them from trash storage
        if (!$item->is_folder && !empty($item->file_path)) {
            $this->moveFileFromTrashStorage($item);
        }

        // Restore vectors if file was vectorized (but keep them soft-deleted until user explicitly requests)
        if (!$item->is_folder && $item->isVectorized()) {
            $hasVectors = $this->vectorManager->hasVectors($item->id, $item->user_id);
            $hasSoftDeletedVectors = $this->vectorManager->areVectorsSoftDeleted($item->id, $item->user_id);
            
            if ($hasVectors && $hasSoftDeletedVectors) {
                // For now, keep vectors soft-deleted until user explicitly chooses to restore them
                // This prevents accidental re-exposure to AI without user consent
                Log::info('File restored but vectors remain soft-deleted pending user confirmation', [
                    'file_id' => $item->id,
                    'user_id' => $item->user_id
                ]);
            }
        }

        // Restore the item itself
        $item->restore();
    }

    /**
     * Move a file in Supabase storage from the 'trash' directory back to its original path.
     */
    private function moveFileFromTrashStorage($file)
    {
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = config('services.supabase.service_key');
        $bucketName = 'docs';

        if (!$supabaseUrl || !$supabaseKey || empty($file->file_path) || !Str::startsWith($file->file_path, 'trash/')) {
            Log::warning('Cannot restore file from storage.', ['file_id' => $file->id, 'path' => $file->file_path]);
            return;
        }

        $client = new Client(['base_uri' => $supabaseUrl]);
        $trashPath = $file->file_path;
        $originalPath = Str::after($trashPath, 'trash/');

        try {
            Log::info('Moving file from trash in Supabase', ['from' => $trashPath, 'to' => $originalPath]);

            $client->post("/storage/v1/object/move", [
                'headers' => ['Authorization' => 'Bearer ' . $supabaseKey, 'Content-Type' => 'application/json'],
                'json' => [
                    'bucketId' => $bucketName,
                    'sourceKey' => $trashPath,
                    'destinationKey' => $originalPath,
                ]
            ]);

            $file->file_path = $originalPath;
            $file->save();

        } catch (RequestException $e) {
            Log::error('Failed to move file from trash in Supabase', [
                'file_path' => $trashPath,
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'N/A',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete multiple files from Supabase storage (used for force delete).
     */
    private function deleteFilesFromStorage($files)
    {
        if ($files->isEmpty()) {
            return;
        }

        $filePaths = $files->pluck('file_path')->filter()->toArray();
        
        if (empty($filePaths)) {
            return;
        }

        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = config('services.supabase.service_key');
        $bucketName = 'docs';

        if (!$supabaseUrl || !$supabaseKey) {
            Log::error('Supabase URL or Service Key is not configured.');
            return;
        }

        $client = new Client(['base_uri' => $supabaseUrl]);

        foreach ($filePaths as $path) {
            try {
                $client->delete("/storage/v1/object/{$bucketName}/{$path}", [
                    'headers' => ['Authorization' => 'Bearer ' . $supabaseKey]
                ]);
            } catch (RequestException $e) {
                Log::error('Failed to permanently delete file from Supabase', [
                    'file_path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
