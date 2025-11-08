<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\User;
use App\Models\SystemActivity;
use App\Services\BlockchainStorage\BlockchainStorageManager;
use App\Services\BlockchainStorage\BlockchainPreflightValidator;
use App\Services\VectorStoreManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

        // When searching, show ALL files across all folders (like Google Drive)
        // Only apply parent filter when NOT searching
        if ($search) {
            // Get advanced search options
            $matchType = $request->query('match_type', 'contains');
            $caseSensitive = $request->query('case_sensitive', 'insensitive');
            $wholeWord = $request->query('whole_word', false);
            
            // Build search query based on options
            if ($caseSensitive === 'sensitive') {
                // Case-sensitive search using LIKE BINARY
                switch ($matchType) {
                    case 'exact':
                        $query->where('file_name', '=', $search);
                        break;
                    case 'starts_with':
                        $query->where('file_name', 'LIKE BINARY', $search . '%');
                        break;
                    case 'ends_with':
                        $query->where('file_name', 'LIKE BINARY', '%' . $search);
                        break;
                    case 'contains':
                    default:
                        if ($wholeWord) {
                            $query->where('file_name', 'REGEXP', '[[:<:]]' . preg_quote($search, '/') . '[[:>:]]');
                        } else {
                            $query->where('file_name', 'LIKE BINARY', '%' . $search . '%');
                        }
                        break;
                }
            } else {
                // Case-insensitive search (default)
                switch ($matchType) {
                    case 'exact':
                        $query->whereRaw('LOWER(file_name) = ?', [strtolower($search)]);
                        break;
                    case 'starts_with':
                        $query->where('file_name', 'LIKE', $search . '%');
                        break;
                    case 'ends_with':
                        $query->where('file_name', 'LIKE', '%' . $search);
                        break;
                    case 'contains':
                    default:
                        if ($wholeWord) {
                            $query->whereRaw('file_name REGEXP ?', ['[[:<:]]' . preg_quote($search, '/') . '[[:>:]]']);
                        } else {
                            $query->where('file_name', 'LIKE', '%' . $search . '%');
                        }
                        break;
                }
            }
        } else {
            // Apply parent filter robustly when browsing (not searching)
            // BUT: Skip parent filter if requesting all folders for move modal
            if ($request->query('type') !== 'folders') {
                if ($request->has('parent_id')) {
                    if ($parentRaw === null || $parentRaw === '' || $parentRaw === 'null') {
                        $query->whereNull('parent_id');
                    } else {
                        $query->where('parent_id', (int) $parentRaw);
                    }
                } else {
                    $query->whereNull('parent_id');
                }
            }
        }

        // Filter by type if specified (for move modal folder selection)
        // When type=folders, return ALL folders (including nested ones) for tree building
        if ($request->query('type') === 'folders') {
            $query->where('is_folder', DB::raw('true'));
        }

        // Add OTP security status to the query
        $query->leftJoin('file_otp_security', function($join) {
            $join->on('files.id', '=', 'file_otp_security.file_id')
                 ->where('file_otp_security.is_otp_enabled', DB::raw('true'));
        })
        ->select('files.*', 
                 DB::raw('CASE WHEN file_otp_security.id IS NOT NULL THEN true ELSE false END as has_otp_protection'));

        $files = $query->orderByRaw('is_folder DESC')->orderBy('file_name', 'asc')->paginate(20);

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
        
        // Check if OTP protection is required
        $otpSecurity = \App\Models\FileOtpSecurity::where('user_id', $user->id)
            ->where('file_id', $file->id)
            ->where('is_otp_enabled', DB::raw('true'))
            ->first();
            
        if ($otpSecurity && $otpSecurity->isOtpRequiredFor('preview')) {
            // Check if user has verified OTP for this file recently
            $sessionKey = "otp_verified_file_{$file->id}";
            if (!session()->has($sessionKey) || session($sessionKey) < now()->subMinutes($otpSecurity->otp_valid_duration_minutes)) {
                return response()->json([
                    'success' => false,
                    'requires_otp' => true,
                    'message' => 'OTP verification required to access this file',
                    'file_id' => $file->id,
                    'file_name' => $file->file_name
                ], 403);
            }
        }

        // Log file access activity (only for non-trashed files to avoid spam)
        if (!$file->trashed()) {
            SystemActivity::logFileActivity(
                SystemActivity::ACTION_ACCESSED,
                $file,
                null, // Let it auto-generate description
                ['otp_protected' => $otpSecurity ? true : false],
                SystemActivity::RISK_LOW,
                $user
            );
        }
        
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
        
        // Check if OTP protection is required for preview
        $otpSecurity = \App\Models\FileOtpSecurity::where('user_id', $user->id)
            ->where('file_id', $file->id)
            ->where('is_otp_enabled', DB::raw('true'))
            ->first();
            
        if ($otpSecurity && $otpSecurity->isOtpRequiredFor('preview')) {
            // Check if user has verified OTP for this file recently
            $sessionKey = "otp_verified_file_{$file->id}";
            $sessionTime = session($sessionKey);
            $expiryTime = now()->subMinutes($otpSecurity->otp_valid_duration_minutes);
            
            Log::info('OTP Preview Check', [
                'file_id' => $file->id,
                'session_key' => $sessionKey,
                'has_session' => session()->has($sessionKey),
                'session_time' => $sessionTime ? $sessionTime->toISOString() : null,
                'expiry_time' => $expiryTime->toISOString(),
                'is_expired' => $sessionTime ? $sessionTime < $expiryTime : true,
                'otp_duration_minutes' => $otpSecurity->otp_valid_duration_minutes
            ]);
            
            if (!session()->has($sessionKey) || $sessionTime < $expiryTime) {
                return redirect()->route('user.dashboard')->with('error', 'OTP verification required to preview this file. Please verify via email first.');
            }
        }
        
        return view('file-preview', compact('file'));
    }

    /**
     * Proxy file content to bypass CORS restrictions
     */
    public function proxyFile($id)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        
        // Allow proxying even for trashed items so previews work from Trash view
        $file = $user->files()->withTrashed()->findOrFail($id);
        
        if ($file->is_folder) {
            abort(404, 'Cannot proxy folder');
        }

        // Check if OTP protection is required for file access
        $otpSecurity = \App\Models\FileOtpSecurity::where('user_id', $user->id)
            ->where('file_id', $file->id)
            ->where('is_otp_enabled', DB::raw('true'))
            ->first();
            
        if ($otpSecurity && ($otpSecurity->isOtpRequiredFor('preview') || $otpSecurity->isOtpRequiredFor('download'))) {
            // Check if user has verified OTP for this file recently
            $sessionKey = "otp_verified_file_{$file->id}";
            $sessionTime = session($sessionKey);
            $expiryTime = now()->subMinutes($otpSecurity->otp_valid_duration_minutes);
            
            Log::info('OTP Proxy Check', [
                'file_id' => $file->id,
                'session_key' => $sessionKey,
                'has_session' => session()->has($sessionKey),
                'session_time' => $sessionTime ? $sessionTime->toISOString() : null,
                'expiry_time' => $expiryTime->toISOString(),
                'is_expired' => $sessionTime ? $sessionTime < $expiryTime : true,
                'require_preview' => $otpSecurity->isOtpRequiredFor('preview'),
                'require_download' => $otpSecurity->isOtpRequiredFor('download')
            ]);
            
            if (!session()->has($sessionKey) || $sessionTime < $expiryTime) {
                abort(403, 'OTP verification required to access this file');
            }
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

        // Handle Arweave-stored files by redirecting to Arweave gateway
        try {
            if (Str::startsWith($filePath, 'arweave://') && !empty($file->arweave_url)) {
                return redirect()->away($file->arweave_url, 302);
            }
        } catch (\Throwable $t) {
            // Non-fatal; continue to Supabase proxy path
            Log::debug('Arweave handling error, falling back to Supabase proxy', ['error' => $t->getMessage()]);
        }

        $possiblePaths = [];

        // Always include the raw stored path (normalized) as first candidate
        $normalizedStoredPath = ltrim($filePath, '/');
        if (!empty($normalizedStoredPath)) {
            $possiblePaths[] = $normalizedStoredPath;
        }

        // Files are generally stored under user_{id}/timestamp_filename format – try that next
        $derivedPath = "user_{$file->user_id}/" . basename($filePath);
        if (!in_array($derivedPath, $possiblePaths, true)) {
            $possiblePaths[] = $derivedPath;
        }

        // For very old uploads the database stored only the display name; try that as well
        if (!preg_match('/^\d+_/', basename($filePath))) {
            Log::info('Attempting legacy file path resolution', [
                'file_id' => $id,
                'stored_path' => $filePath,
                'user_id' => $file->user_id,
                'filename' => $file->file_name
            ]);

            $legacyPath = "user_{$file->user_id}/" . $file->file_name;
            if (!in_array($legacyPath, $possiblePaths, true)) {
                $possiblePaths[] = $legacyPath;
            }
        }
        
        $verifySsl = !(config('app.env') === 'local' || config('app.debug'));
        $client = new Client([
            'verify' => $verifySsl,
            'timeout' => 10,
        ]);
        
        foreach ($possiblePaths as $testPath) {
            try {
                $testUrl = "{$supabaseUrl}/storage/v1/object/public/docs/" . $testPath;
                Log::info('Testing file path', [
                    'file_id' => $id,
                    'test_path' => $testPath,
                    'test_url' => $testUrl
                ]);
                
                $response = $client->head($testUrl, ['http_errors' => false]);
                
                if ($response->getStatusCode() === 200) {
                    // File exists, get the content
                    $response = $client->get($testUrl, ['http_errors' => false]);
                    if ($response->getStatusCode() === 200) {
                        $content = $response->getBody()->getContents();
                        $contentType = $response->getHeader('Content-Type')[0] ?? $file->mime_type ?? 'application/octet-stream';
                        
                        // Log file download activity
                        SystemActivity::logFileActivity(
                            SystemActivity::ACTION_DOWNLOADED,
                            $file,
                            null, // Let it auto-generate description
                            [
                                'file_path' => $testPath,
                                'otp_protected' => $otpSecurity ? true : false,
                                'content_type' => $contentType
                            ],
                            SystemActivity::RISK_LOW,
                            $user
                        );

                        Log::info('File found and served successfully', [
                            'file_id' => $id,
                            'successful_path' => $testPath
                        ]);
                        
                        return response($content)
                            ->header('Content-Type', $contentType)
                            ->header('Access-Control-Allow-Origin', '*')
                            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                            ->header('Content-Disposition', 'inline; filename="' . $file->file_name . '"')
                            ->header('Cache-Control', 'public, max-age=3600');
                    }
                }
            } catch (\Exception $e) {
                Log::debug('Path test failed', [
                    'file_id' => $id,
                    'test_path' => $testPath,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
        
        // File not found with any path strategy
        Log::error('File not found in Supabase storage with any path strategy', [
            'file_id' => $id,
            'stored_path' => $filePath,
            'user_id' => $file->user_id,
            'paths_tried' => $possiblePaths
        ]);
        
        abort(404, 'File not found in storage. The file may not have been uploaded to Supabase storage yet.');
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
                'message' => 'Vectors restored successfully',
                'item' => $file
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
     * Start AI categorization for user's files
     */
    public function startAICategorization(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check if categorization is already in progress
            $currentStatus = Cache::get("ai_categorization_status_{$user->id}");
            if ($currentStatus && $currentStatus['status'] === 'in_progress') {
                return response()->json([
                    'success' => false,
                    'message' => 'Categorization already in progress',
                    'status' => $currentStatus
                ], 409);
            }

            // Set initial status
            $status = [
                'status' => 'in_progress',
                'progress' => 0,
                'message' => 'Starting AI categorization...',
                'started_at' => now()->toISOString(),
                'user_id' => $user->id
            ];
            
            Cache::put("ai_categorization_status_{$user->id}", $status, 3600); // 1 hour TTL

            // Trigger N8N workflow for AI categorization
            $webhookUrl = config('services.n8n.categorization_webhook_url', 'http://localhost:5678/webhook/ai-categorization');
            
            $payload = [
                'user_id' => $user->id,
                'action' => 'categorize_files',
                'timestamp' => now()->toISOString()
            ];

            Http::timeout(30)->post($webhookUrl, $payload);

            Log::info('AI categorization started', [
                'user_id' => $user->id,
                'webhook_url' => $webhookUrl
            ]);

            return response()->json([
                'success' => true,
                'message' => 'AI categorization started',
                'status' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to start AI categorization', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start categorization'
            ], 500);
        }
    }

    /**
     * Get current categorization status for authenticated user
     */
    public function getCategorizationStatus(Request $request): JsonResponse
    {
        $user = Auth::user();
        $status = Cache::get("ai_categorization_status_{$user->id}", [
            'status' => 'idle',
            'progress' => 0,
            'message' => 'No categorization in progress'
        ]);

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }

    /**
     * Update categorization status (called by AI)
     */
    public function updateCategorizationStatus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer',
                'status' => 'required|string|in:in_progress,completed,failed',
                'progress' => 'nullable|integer|min:0|max:100',
                'message' => 'nullable|string',
                'details' => 'nullable|array'
            ]);

            $userId = $validated['user_id'];
            
            // Verify user exists
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Update status in cache
            $currentStatus = Cache::get("ai_categorization_status_{$userId}", []);
            
            $newStatus = array_merge($currentStatus, [
                'status' => $validated['status'],
                'progress' => $validated['progress'] ?? $currentStatus['progress'] ?? 0,
                'message' => $validated['message'] ?? $currentStatus['message'] ?? '',
                'updated_at' => now()->toISOString(),
                'details' => $validated['details'] ?? null
            ]);

            if ($validated['status'] === 'completed') {
                $newStatus['completed_at'] = now()->toISOString();
                Cache::put("ai_categorization_status_{$userId}", $newStatus, 600); // Keep for 10 mins after completion
            } elseif ($validated['status'] === 'failed') {
                $newStatus['failed_at'] = now()->toISOString();
                Cache::put("ai_categorization_status_{$userId}", $newStatus, 600);
            } else {
                Cache::put("ai_categorization_status_{$userId}", $newStatus, 3600); // 1 hour for in-progress
            }

            Log::info('AI categorization status updated', [
                'user_id' => $userId,
                'status' => $validated['status'],
                'progress' => $validated['progress']
            ]);

            // Broadcast real-time update to frontend (if using broadcasting)
            if ($validated['status'] === 'in_progress') {
                // Trigger frontend polling start
                Cache::put("ai_categorization_polling_active_{$userId}", true, 3600);
            } elseif (in_array($validated['status'], ['completed', 'failed'])) {
                // Stop frontend polling
                Cache::forget("ai_categorization_polling_active_{$userId}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'status' => $newStatus
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update categorization status', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Get categorization status without authentication (for public access)
     */
    public function getCategorizationStatusPublic(Request $request): JsonResponse
    {
        // Log the request for monitoring
        $userId = $request->input('user_id') ?? $request->header('X-User-ID');
        $logData = [
            'timestamp' => now()->toISOString(),
            'user_id' => $userId ?? 'anonymous',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
        ];

        // Check if AI categorization is disabled globally
        if (!config('services.ai_categorization.enabled', true)) {
            $logData['response_status'] = 'disabled';
            Log::channel('ai_categorization_pings')->info('AI Categorization Status Request', $logData);
            
            return response()->json([
                'success' => true,
                'status' => [
                    'status' => 'disabled',
                    'progress' => 0,
                    'message' => 'AI categorization is currently disabled'
                ]
            ]);
        }

        // Get user_id from request metadata if available
        
        if ($userId) {
            // Check if user is premium (AI categorization is premium-only)
            $user = User::find($userId);
            if (!$user || !$user->is_premium) {
                $logData['response_status'] = 'premium_required';
                $logData['user_premium'] = $user ? $user->is_premium : 'user_not_found';
                Log::channel('ai_categorization_pings')->info('AI Categorization Status Request', $logData);
                
                return response()->json([
                    'success' => true,
                    'status' => [
                        'status' => 'premium_required',
                        'progress' => 0,
                        'message' => 'AI categorization requires premium subscription'
                    ]
                ]);
            }

            // Check cache for this specific user
            $status = Cache::get("ai_categorization_status_{$userId}", [
                'status' => 'idle',
                'progress' => 0,
                'message' => 'No categorization in progress',
                'updated_at' => now()->toISOString()
            ]);
            
            $logData['response_status'] = $status['status'];
            $logData['user_premium'] = true;
            Log::channel('ai_categorization_pings')->info('AI Categorization Status Request', $logData);
            
            return response()->json([
                'success' => true,
                'status' => $status
            ]);
        }
        
        // Return idle status if no user context
        $logData['response_status'] = 'idle_no_user';
        Log::channel('ai_categorization_pings')->info('AI Categorization Status Request', $logData);
        
        return response()->json([
            'success' => true,
            'status' => [
                'status' => 'idle',
                'progress' => 0,
                'message' => 'No categorization in progress'
            ]
        ]);
    }

    /**
     * Check if AI categorization polling should be active for a user (lightweight endpoint)
     */
    public function shouldPollCategorizationStatus(Request $request): JsonResponse
    {
        // Check if AI categorization is disabled globally
        if (!config('services.ai_categorization.enabled', true)) {
            return response()->json(['should_poll' => false, 'reason' => 'disabled']);
        }

        // Get user_id from request
        $userId = $request->input('user_id');
        if (!$userId) {
            return response()->json(['should_poll' => false, 'reason' => 'no_user_id']);
        }

        // Check if user is premium
        $user = User::find($userId);
        if (!$user || !$user->is_premium) {
            return response()->json(['should_poll' => false, 'reason' => 'not_premium']);
        }

        // Check if there's an active polling flag set by AI
        $shouldPoll = Cache::has("ai_categorization_polling_active_{$userId}");
        
        return response()->json([
            'should_poll' => $shouldPoll,
            'reason' => $shouldPoll ? 'ai_active' : 'no_active_ai'
        ]);
    }

    /**
     * Store a new file or folder
     */
    public function store(Request $request): JsonResponse
    {
        try {
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
            'enable_permanent_storage' => 'sometimes|boolean',
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

        // Log file/folder creation activity
        try {
            SystemActivity::logFileActivity(
                SystemActivity::ACTION_CREATED,
                $item,
                null, // Let it auto-generate description
                [
                    'processing_type' => $processingType,
                    'permanent_storage' => $validated['enable_permanent_storage'] ?? false
                ],
                SystemActivity::RISK_LOW,
                $user
            );
            Log::info('File creation activity logged successfully', ['file_id' => $item->id]);
        } catch (\Exception $e) {
            Log::error('Failed to log file creation activity', [
                'file_id' => $item->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        if (!$isFolder && $processingType !== 'standard') {
            $this->handlePostUploadProcessing($item, $processingType, $user);
        }

            return response()->json($item, 201);
        } catch (\Exception $e) {
            Log::error('Error creating file/folder', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create file/folder'
            ], 500);
        }
    }

    /**
     * Upload file with standard storage (Supabase only)
     */
    public function uploadStandard(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('Standard upload request received', ['user_id' => $user->id]);

            // Define allowed file types and size limits
            $allowedExtensions = [
                'pdf', 'doc', 'docx', 'txt', 'rtf', 'odt',
                'xls', 'xlsx', 'csv', 'ods',
                'ppt', 'pptx', 'odp',
                'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg',
                'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm',
                'mp3', 'wav', 'flac', 'aac', 'ogg',
                'zip', 'rar', '7z', 'tar', 'gz',
                'json', 'xml', 'html', 'css', 'js', 'md'
            ];
            
            $maxFileSize = 104857600; // 100MB in bytes

            $validated = $request->validate([
                'file_name' => 'required|string|max:255',
                'file_path' => 'required|string',
                'file_size' => 'required|integer|max:' . $maxFileSize,
                'file_type' => 'required|string',
                'mime_type' => 'required|string',
                'parent_id' => 'nullable|integer|exists:files,id',
                'replace_existing' => 'nullable|boolean',
            ]);

            $fileName = $validated['file_name'];
            $parentId = $validated['parent_id'] ?? null;
            
            // Validate file extension
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                return response()->json([
                    'success' => false,
                    'message' => "File type '.{$fileExtension}' is not supported. Allowed types: " . implode(', ', $allowedExtensions)
                ], 422);
            }

            // Additional file size check
            if ($validated['file_size'] > $maxFileSize) {
                return response()->json([
                    'success' => false,
                    'message' => 'File size exceeds the maximum limit of 100MB'
                ], 422);
            }
            $replaceExisting = $validated['replace_existing'] ?? false;

            // Check for duplicate and handle accordingly
            $existingFile = $this->findDuplicateFile($user->id, $fileName, $parentId);
            
            if ($existingFile && $replaceExisting) {
                // Replace existing file - update its record
                $existingFile->update([
                    'file_path' => $validated['file_path'],
                    'file_size' => $validated['file_size'],
                    'mime_type' => $validated['mime_type'],
                    'updated_at' => now(),
                ]);

                Log::info('File replaced successfully', [
                    'user_id' => $user->id,
                    'file_id' => $existingFile->id,
                    'file_name' => $fileName
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'File replaced successfully',
                    'file' => $existingFile
                ], 200);
                
            } elseif ($existingFile && !$replaceExisting) {
                // Keep both - generate unique name
                $fileName = $this->generateUniqueFileName($user->id, $fileName, $parentId);
                
                Log::info('Duplicate file detected, using unique name', [
                    'user_id' => $user->id,
                    'original_name' => $validated['file_name'],
                    'unique_name' => $fileName
                ]);
            }

            // Create new file record
            $file = File::create([
                'user_id' => $user->id,
                'file_name' => $fileName,
                'file_path' => $validated['file_path'],
                'file_size' => $validated['file_size'],
                'file_type' => $validated['file_type'],
                'mime_type' => $validated['mime_type'],
                'parent_id' => $parentId,
                'is_folder' => DB::raw('FALSE'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded to standard storage successfully',
                'file' => $file
            ], 201);

        } catch (\Exception $e) {
            Log::error('Standard upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Standard upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload file directly to blockchain (Arweave) - NO VECTOR PROCESSING
     */
    public function uploadBlockchain(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check premium access
            if (!$user->is_premium) {
                return response()->json([
                    'success' => false,
                    'message' => 'Premium subscription required for blockchain storage'
                ], 403);
            }

            Log::info('Blockchain upload request received', ['user_id' => $user->id]);

            $validated = $request->validate([
                'file_name' => 'required|string|max:255',
                'file_content' => 'required|string', // Base64 encoded file content
                'file_size' => 'required|integer',
                'file_type' => 'required|string',
                'mime_type' => 'required|string',
                'parent_id' => 'nullable|integer|exists:files,id',
            ]);

            // Create temporary file for Arweave upload
            $tempFile = $this->createTempFileFromBase64($validated['file_content'], $validated['file_name']);
            
            // Create file record first and mark as uploading
            $file = File::create([
                'user_id' => $user->id,
                'file_name' => $validated['file_name'],
                'file_path' => $tempFile, // Temporary path
                'file_size' => $validated['file_size'],
                'file_type' => $validated['file_type'],
                'mime_type' => $validated['mime_type'],
                'parent_id' => $validated['parent_id'],
                'is_folder' => false,
                'uploading' => true, // Mark as currently uploading
                'is_arweave' => false, // Will be updated after successful upload
            ]);

            // TODO: Replace with BlockchainStorageManager implementation
            // Upload to Arweave using ArweaveService
            // $arweaveService = new ArweaveService();
            // $arweaveResult = $arweaveService->uploadFile($file, $user->id);
            
            // Temporary fallback - return error for now
            $file->markUploadFailed();
            return response()->json([
                'success' => false,
                'message' => 'Direct blockchain upload is temporarily disabled. Use permanent storage modal instead.'
            ], 503);

        } catch (\Exception $e) {
            Log::error('Blockchain upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Blockchain upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload file and process with AI vectorization only
     */
    public function uploadAiVectorize(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check premium access
            if (!$user->is_premium) {
                return response()->json([
                    'success' => false,
                    'message' => 'Premium subscription required for AI vectorization'
                ], 403);
            }

            Log::info('AI vectorize upload request received', ['user_id' => $user->id]);

            $validated = $request->validate([
                'file_name' => 'required|string|max:255',
                'file_path' => 'required|string',
                'file_size' => 'required|integer',
                'file_type' => 'required|string',
                'mime_type' => 'required|string',
                'parent_id' => 'nullable|integer|exists:files,id',
                'replace_existing' => 'nullable|boolean',
            ]);

            $fileName = $validated['file_name'];
            $parentId = $validated['parent_id'] ?? null;
            $replaceExisting = $validated['replace_existing'] ?? false;

            // Check for duplicate and handle accordingly
            $existingFile = $this->findDuplicateFile($user->id, $fileName, $parentId);
            
            if ($existingFile && $replaceExisting) {
                // Replace existing file - update its record
                $existingFile->update([
                    'file_path' => $validated['file_path'],
                    'file_size' => $validated['file_size'],
                    'mime_type' => $validated['mime_type'],
                    'updated_at' => now(),
                ]);

                // Process with AI vectorization
                $this->processN8nVectorization($existingFile, $user);

                Log::info('File replaced and queued for vectorization', [
                    'user_id' => $user->id,
                    'file_id' => $existingFile->id,
                    'file_name' => $fileName
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'File replaced and queued for AI vectorization',
                    'file' => $existingFile
                ], 200);
                
            } elseif ($existingFile && !$replaceExisting) {
                // Keep both - generate unique name
                $fileName = $this->generateUniqueFileName($user->id, $fileName, $parentId);
                
                Log::info('Duplicate file detected, using unique name', [
                    'user_id' => $user->id,
                    'original_name' => $validated['file_name'],
                    'unique_name' => $fileName
                ]);
            }

            // Create new file record
            $file = File::create([
                'user_id' => $user->id,
                'file_name' => $fileName,
                'file_path' => $validated['file_path'],
                'file_size' => $validated['file_size'],
                'file_type' => $validated['file_type'],
                'mime_type' => $validated['mime_type'],
                'parent_id' => $parentId,
                'is_folder' => false,
            ]);

            // Process with AI vectorization
            $this->processN8nVectorization($file, $user);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded and queued for AI vectorization',
                'file' => $file
            ], 201);

        } catch (\Exception $e) {
            Log::error('AI vectorize upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI vectorize upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to create temporary file from base64 content
     */
    private function createTempFileFromBase64(string $base64Content, string $fileName): string
    {
        $decodedContent = base64_decode($base64Content);
        $tempDir = storage_path('app/temp');
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $tempFile = $tempDir . '/' . uniqid() . '_' . $fileName;
        file_put_contents($tempFile, $decodedContent);
        
        return $tempFile;
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

            // Send to N8N webhook (disable SSL verification for self-signed certificates)
            $response = Http::timeout(30)->withoutVerifying()->post($n8nWebhookUrl, $payload);

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
            $file = File::where('id', $id)->where('user_id', $user->id)->firstOrFail();

            if (!$file->isStoredOnArweave()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is not stored on Arweave'
                ], 400);
            }

            // Arweave files are permanent and cannot be removed
            return response()->json([
                'success' => false,
                'message' => 'Arweave files are permanently stored and cannot be removed'
            ], 403);

        } catch (\Exception $e) {
            Log::error('Failed to check Arweave file status', [
                'file_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process request'
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
     * Download Arweave file to Supabase storage
     */
    public function downloadFromBlockchain(File $file): JsonResponse
    {
        try {
            if ($file->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            if (!$file->isStoredOnArweave()) {
                return response()->json(['success' => false, 'message' => 'File is not on Arweave'], 400);
            }

            // Arweave files are already accessible via their URL
            // No need to download to Supabase - just redirect to Arweave URL
            return response()->json([
                'success' => true,
                'message' => 'File is available on Arweave',
                'arweave_url' => $file->arweave_url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process request: ' . $e->getMessage()
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
            // Files are stored in Supabase, so we need to download them first
            $supabaseUrl = env('SUPABASE_URL');
            $filePath = $file->file_path;
            
            // Construct the Supabase URL for the file
            $fileUrl = "{$supabaseUrl}/storage/v1/object/public/docs/{$filePath}";
            
            Log::info('Attempting to download file from Supabase for blockchain upload', [
                'file_id' => $file->id,
                'file_url' => $fileUrl,
                'file_path' => $filePath
            ]);

            // Download file content from Supabase
            $verifySsl = !(config('app.env') === 'local' || config('app.debug'));
            $client = new Client(['verify' => $verifySsl, 'timeout' => 30]);
            $response = $client->get($fileUrl);
            
            if ($response->getStatusCode() !== 200) {
                Log::error('Failed to download file from Supabase for blockchain upload', [
                    'file_id' => $file->id,
                    'status_code' => $response->getStatusCode()
                ]);
                return;
            }

            $fileContent = $response->getBody()->getContents();
            
            // Create a temporary file
            $tempPath = tempnam(sys_get_temp_dir(), 'blockchain_upload_');
            file_put_contents($tempPath, $fileContent);

            $uploadedFile = new UploadedFile(
                $tempPath,
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

            // Clean up temporary file
            unlink($tempPath);

            if ($result['success']) {
                Log::info('Blockchain upload successful during post-processing', [
                    'file_id' => $file->id,
                    'ipfs_hash' => $result['ipfs_hash']
                ]);
                
                // Mark file as successfully uploaded to Arweave
                $file->markAsArweaveStored($result['blockchain_url'] ?? $file->arweave_url);

                // Log blockchain storage activity
                SystemActivity::logFileActivity(
                    SystemActivity::ACTION_UPLOADED,
                    $file,
                    "{$user->name} stored '{$file->file_name}' on blockchain",
                    [
                        'blockchain_provider' => 'arweave',
                        'arweave_url' => $file->arweave_url,
                        'processing_type' => 'post_upload_blockchain'
                    ],
                    SystemActivity::RISK_LOW,
                    $user
                );
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
     * Get user storage usage statistics
     */
    public function getStorageUsage(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Calculate total storage used by user (cast to bigint for PostgreSQL)
            $totalUsed = File::where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->whereNotNull('file_size')
                ->sum(DB::raw('CAST(COALESCE(file_size, \'0\') AS BIGINT)')) ?? 0;

            // Storage limits based on user type
            $storageLimit = $user->is_premium ? 1073741824 : 524288000; // 1GB for premium, 500MB for free
            $usagePercentage = $storageLimit > 0 ? ($totalUsed / $storageLimit) * 100 : 0;

            // Get breakdown by storage type (simplified to avoid column issues)
            $supabaseUsed = $totalUsed; // For now, assume all files are in Supabase
            $blockchainUsed = 0; // Will be calculated separately if needed

            return response()->json([
                'success' => true,
                'storage' => [
                    'total_used' => $totalUsed,
                    'total_limit' => $storageLimit,
                    'usage_percentage' => round($usagePercentage, 2),
                    'is_premium' => $user->is_premium,
                    'breakdown' => [
                        'supabase' => $supabaseUsed,
                        'blockchain' => $blockchainUsed
                    ],
                    'formatted' => [
                        'used' => $this->formatBytes($totalUsed),
                        'limit' => $this->formatBytes($storageLimit),
                        'supabase' => $this->formatBytes($supabaseUsed),
                        'blockchain' => $this->formatBytes($blockchainUsed)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get storage usage', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            // Return a fallback response to prevent frontend errors
            return response()->json([
                'success' => true,
                'storage' => [
                    'total_used' => 0,
                    'total_limit' => 524288000, // 500MB default
                    'usage_percentage' => 0,
                    'is_premium' => false,
                    'breakdown' => [
                        'supabase' => 0,
                        'blockchain' => 0
                    ],
                    'formatted' => [
                        'used' => '0 B',
                        'limit' => '500 MB',
                        'supabase' => '0 B',
                        'blockchain' => '0 B'
                    ]
                ]
            ]);
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

            // Make direct HTTP call to n8n webhook (disable SSL verification for self-signed certificates)
            $response = Http::timeout(30)->withoutVerifying()->post($n8nWebhookUrl, $payload);

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

            // Log deletion activity after successful trash operation
            try {
                SystemActivity::logFileActivity(
                    SystemActivity::ACTION_DELETED,
                    $file,
                    null, // Let it auto-generate description
                    ['moved_to_trash' => true],
                    SystemActivity::RISK_LOW,
                    Auth::user()
                );
            } catch (\Exception $e) {
                Log::error('Failed to log deletion activity', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

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

        // Disable SSL verification for local development
        $verifySsl = !(config('app.env') === 'local' || config('app.debug'));
        $client = new Client([
            'base_uri' => $supabaseUrl,
            'verify' => $verifySsl,
            'timeout' => 30,
        ]);
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
                    Rule::exists('files', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->whereRaw('is_folder IS TRUE');
                }),
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

            // Log folder creation activity
            try {
                SystemActivity::logFileActivity(
                    SystemActivity::ACTION_CREATED,
                    $folder,
                    null, // Let it auto-generate description
                    ['folder_path' => $path],
                    SystemActivity::RISK_LOW,
                    $user
                );
                Log::info('Folder creation activity logged successfully', ['folder_id' => $folder->id]);
            } catch (\Exception $e) {
                Log::error('Failed to log folder creation activity', [
                    'folder_id' => $folder->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

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
    public function restore(Request $request, $id): JsonResponse
    {
        try {
            // Use withTrashed to find the file, as it's soft-deleted.
            $file = Auth::user()->files()->withTrashed()->findOrFail($id);
            
            // Check if restore_to_root flag is set (for files inside deleted folders)
            $restoreToRoot = $request->input('restore_to_root', false);

            DB::transaction(function () use ($file, $restoreToRoot) {
                if ($restoreToRoot) {
                    // Restore to root by setting parent_id to null
                    $file->parent_id = null;
                    $file->save();
                    
                    // Move file from trash storage if needed
                    if (!$file->is_folder && !empty($file->file_path)) {
                        $this->moveFileFromTrashStorage($file);
                    }
                    
                    // Restore vectors if file was vectorized
                    if (!$file->is_folder && $file->isVectorized()) {
                        $hasVectors = $this->vectorManager->hasVectors($file->id, $file->user_id);
                        $hasSoftDeletedVectors = $this->vectorManager->areVectorsSoftDeleted($file->id, $file->user_id);
                        
                        if ($hasVectors && $hasSoftDeletedVectors) {
                            Log::info('File restored to root but vectors remain soft-deleted pending user confirmation', [
                                'file_id' => $file->id,
                                'user_id' => $file->user_id
                            ]);
                        }
                    }
                } else {
                    // Normal restore (to original location)
                    $this->restoreItem($file);
                }
            });

            // Log restore activity
            SystemActivity::logFileActivity(
                SystemActivity::ACTION_RESTORED,
                $file,
                null, // Let it auto-generate description
                ['restored_from_trash' => true, 'restore_to_root' => $restoreToRoot],
                SystemActivity::RISK_LOW,
                Auth::user()
            );

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
     * Empty trash - permanently delete all trashed items for the authenticated user.
     */
    public function emptyTrash(): JsonResponse
    {
        try {
            $user = Auth::user();
            $trashedItems = $user->files()->onlyTrashed()->get();
            
            if ($trashedItems->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Trash is already empty',
                    'deleted_count' => 0
                ]);
            }

            $deletedCount = 0;

            DB::transaction(function () use ($trashedItems, &$deletedCount) {
                // Separate folders and files
                $folders = $trashedItems->where('is_folder', true);
                $files = $trashedItems->where('is_folder', false);

                // Delete all files first
                foreach ($files as $file) {
                    // Clean up vectors if vectorized
                    if ($file->isVectorized()) {
                        $this->vectorManager->unvector($file->id, $file->user_id);
                    }
                    
                    // Delete from storage
                    if (!empty($file->file_path)) {
                        $this->deleteFilesFromStorage(collect([$file]));
                    }
                    
                    $file->forceDelete();
                    $deletedCount++;
                }

                // Delete all folders (they should be empty now or contain only other trashed items)
                foreach ($folders as $folder) {
                    // Get all children (including nested)
                    $allChildren = $this->getAllChildren($folder, true);
                    
                    // Delete children files
                    $childFiles = $allChildren->where('is_folder', false);
                    foreach ($childFiles as $childFile) {
                        if ($childFile->isVectorized()) {
                            $this->vectorManager->unvector($childFile->id, $childFile->user_id);
                        }
                        if (!empty($childFile->file_path)) {
                            $this->deleteFilesFromStorage(collect([$childFile]));
                        }
                        $childFile->forceDelete();
                        $deletedCount++;
                    }
                    
                    // Delete child folders
                    $childFolders = $allChildren->where('is_folder', true);
                    foreach ($childFolders as $childFolder) {
                        $childFolder->forceDelete();
                        $deletedCount++;
                    }
                    
                    // Delete the parent folder
                    $folder->forceDelete();
                    $deletedCount++;
                }
            });

            Log::info('Trash emptied successfully', [
                'user_id' => $user->id,
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} item(s) permanently",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error emptying trash', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error emptying trash',
                'message' => $e->getMessage()
            ], 500);
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

        // Disable SSL verification for local development
        $verifySsl = !(config('app.env') === 'local' || config('app.debug'));
        $client = new Client([
            'base_uri' => $supabaseUrl,
            'verify' => $verifySsl,
            'timeout' => 30,
        ]);
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

        // Disable SSL verification for local development
        $verifySsl = !(config('app.env') === 'local' || config('app.debug'));
        $client = new Client([
            'base_uri' => $supabaseUrl,
            'verify' => $verifySsl,
            'timeout' => 30,
        ]);

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

    /**
     * Move a file or folder to a different parent folder
     */
    public function move(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'parent_id' => 'nullable|integer|exists:files,id'
            ]);

            $user = Auth::user();
            $file = $user->files()->findOrFail($id);

            // Validation: Cannot move trashed items
            if ($file->deleted_at !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot move trashed items. Restore the item first.'
                ], 422);
            }

            $newParentId = $validated['parent_id'];

            // Validation: Cannot move a folder into itself or its descendants
            if ($file->is_folder && $newParentId) {
                $newParent = $user->files()->findOrFail($newParentId);
                
                // Check if the new parent is the same as the file being moved
                if ($newParent->id === $file->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot move a folder into itself'
                    ], 422);
                }

                // Check if the new parent is a descendant of the file being moved
                if ($this->isDescendant($newParent, $file)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot move a folder into its own subfolder'
                    ], 422);
                }

                // Ensure the new parent is actually a folder
                if (!$newParent->is_folder) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Destination must be a folder'
                    ], 422);
                }
            }

            // Validation: If new parent is specified, ensure it exists and belongs to the user
            if ($newParentId) {
                $newParent = $user->files()->where('id', $newParentId)->where('is_folder', DB::raw('true'))->first();
                if (!$newParent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Destination folder not found or is not a folder'
                    ], 404);
                }
            }

            // Check for name conflicts in destination
            $conflictingFile = $user->files()
                ->where('parent_id', $newParentId)
                ->where('file_name', $file->file_name)
                ->where('is_folder', DB::raw($file->is_folder ? 'true' : 'false'))
                ->where('id', '!=', $file->id)
                ->first();

            if ($conflictingFile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Name conflict: An item with this name already exists in the destination',
                    'conflict' => true,
                    'conflict_item' => [
                        'id' => $conflictingFile->id,
                        'file_name' => $conflictingFile->file_name,
                        'is_folder' => $conflictingFile->is_folder
                    ]
                ], 409);
            }

            // Update the parent_id
            $file->parent_id = $newParentId;
            $file->save();

            Log::info('File moved successfully', [
                'file_id' => $file->id,
                'file_name' => $file->file_name,
                'old_parent_id' => $file->getOriginal('parent_id'),
                'new_parent_id' => $newParentId,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item moved successfully',
                'data' => [
                    'id' => $file->id,
                    'file_name' => $file->file_name,
                    'parent_id' => $file->parent_id
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to move file', [
                'file_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to move item'
            ], 500);
        }
    }

    /**
     * Move multiple files/folders in a batch (transactional)
     */
    public function moveBatch(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'item_ids' => 'required|array|min:1|max:50',
                'item_ids.*' => 'integer|exists:files,id',
                'parent_id' => 'nullable|integer|exists:files,id'
            ]);

            $user = Auth::user();
            $itemIds = $validated['item_ids'];
            $newParentId = $validated['parent_id'];

            // Validation: If new parent is specified, ensure it exists and belongs to the user
            if ($newParentId) {
                $newParent = $user->files()->where('id', $newParentId)->where('is_folder', DB::raw('true'))->first();
                if (!$newParent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Destination folder not found or is not a folder'
                    ], 404);
                }
            }

            // Pre-validate all items before moving any
            $validationErrors = [];
            $itemsToMove = [];

            foreach ($itemIds as $itemId) {
                $file = $user->files()->find($itemId);

                if (!$file) {
                    $validationErrors[] = [
                        'item_id' => $itemId,
                        'error' => 'Item not found'
                    ];
                    continue;
                }

                // Check if item is trashed
                if ($file->deleted_at !== null) {
                    $validationErrors[] = [
                        'item_id' => $itemId,
                        'file_name' => $file->file_name,
                        'error' => 'Cannot move trashed items'
                    ];
                    continue;
                }

                // Check if folder is being moved into itself or its descendants
                if ($file->is_folder && $newParentId) {
                    $newParent = $user->files()->find($newParentId);
                    if ($newParent->id === $file->id) {
                        $validationErrors[] = [
                            'item_id' => $itemId,
                            'file_name' => $file->file_name,
                            'error' => 'Cannot move a folder into itself'
                        ];
                        continue;
                    }

                    if ($this->isDescendant($newParent, $file)) {
                        $validationErrors[] = [
                            'item_id' => $itemId,
                            'file_name' => $file->file_name,
                            'error' => 'Cannot move a folder into its own subfolder'
                        ];
                        continue;
                    }
                }

                // Check for name conflicts
                $conflictingFile = $user->files()
                    ->where('parent_id', $newParentId)
                    ->where('file_name', $file->file_name)
                    ->where('is_folder', DB::raw($file->is_folder ? 'true' : 'false'))
                    ->where('id', '!=', $file->id)
                    ->first();

                if ($conflictingFile) {
                    $validationErrors[] = [
                        'item_id' => $itemId,
                        'file_name' => $file->file_name,
                        'error' => 'Name conflict in destination',
                        'conflict_item' => [
                            'id' => $conflictingFile->id,
                            'file_name' => $conflictingFile->file_name
                        ]
                    ];
                    continue;
                }

                $itemsToMove[] = $file;
            }

            // If there are validation errors, return them without moving anything (all-or-nothing)
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed for some items. No items were moved.',
                    'validation_errors' => $validationErrors,
                    'error_count' => count($validationErrors),
                    'total_items' => count($itemIds)
                ], 422);
            }

            // All items validated, now move them in a transaction
            DB::transaction(function () use ($itemsToMove, $newParentId, $user) {
                foreach ($itemsToMove as $file) {
                    $file->parent_id = $newParentId;
                    $file->save();

                    Log::info('File moved in batch', [
                        'file_id' => $file->id,
                        'file_name' => $file->file_name,
                        'new_parent_id' => $newParentId,
                        'user_id' => $user->id
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => count($itemsToMove) . ' item(s) moved successfully',
                'moved_count' => count($itemsToMove),
                'data' => array_map(fn ($file) => [
                    'id' => $file->id,
                    'file_name' => $file->file_name,
                    'parent_id' => $file->parent_id
                ], $itemsToMove)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to move batch', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to move items'
            ], 500);
        }
    }

    /**
     * Check if a folder is a descendant of another folder
     */
    private function isDescendant(File $potentialDescendant, File $ancestor): bool
    {
        $current = $potentialDescendant;
        
        while ($current->parent_id) {
            if ($current->parent_id === $ancestor->id) {
                return true;
            }
            
            $current = File::find($current->parent_id);
            if (!$current) {
                break;
            }
        }
        
        return false;
    }
    /**
     * Enable permanent storage for a blockchain file
     */
    public function enablePermanentStorage(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $file = File::where('id', $id)->where('user_id', $user->id)->firstOrFail();

            if (!$file->isStoredOnBlockchain()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File must be stored on blockchain to enable permanent storage'
                ], 400);
            }

            if ($file->is_permanent_storage) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is already marked as permanent storage'
                ], 400);
            }

            // Update file to permanent storage
            $file->is_permanent_storage = true;
            $file->permanent_storage_enabled_at = now();
            $file->permanent_storage_enabled_by = $user->id;
            $file->save();

            Log::info('Permanent storage enabled for file', [
                'file_id' => $file->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permanent storage enabled successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to enable permanent storage', [
                'file_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to enable permanent storage'
            ], 500);
        }
    }

    /**
     * Get user's processing options based on premium status
     */
    public function getProcessingOptions(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $options = [
                'standard' => [
                    'name' => 'Standard Upload',
                    'description' => 'Regular file upload to secure storage',
                    'available' => true
                ]
            ];

            // Only show processing options for non-premium users
            if (!$user->is_premium) {
                $options['blockchain'] = [
                    'name' => 'Blockchain Storage',
                    'description' => 'Store file on IPFS blockchain for decentralized access',
                    'available' => true
                ];

                $options['vectorize'] = [
                    'name' => 'AI Vectorization',
                    'description' => 'Process file for AI search and analysis',
                    'available' => true
                ];

                $options['hybrid'] = [
                    'name' => 'Hybrid Processing',
                    'description' => 'Combine blockchain storage with AI vectorization',
                    'available' => true
                ];
            }

            return response()->json([
                'success' => true,
                'user_is_premium' => $user->is_premium,
                'processing_options' => $options
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get processing options', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get processing options'
            ], 500);
        }
    }

    /**
     * Rename a file or folder
     */
    public function rename(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Validate the new name
            $validated = $request->validate([
                'new_name' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s._-]+$/'
            ]);

            // Find the file/folder (including trashed items)
            $file = $user->files()->withTrashed()->findOrFail($id);
            
            // Don't allow renaming trashed items
            if ($file->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot rename items in trash'
                ], 400);
            }

            $newName = trim($validated['new_name']);
            $oldName = $file->file_name;

            // Check if the name is actually different
            if ($newName === $oldName) {
                return response()->json([
                    'success' => false,
                    'message' => 'The new name is the same as the current name'
                ], 400);
            }

            // Check for duplicates in the same directory
            $duplicate = $user->files()
                ->where('parent_id', $file->parent_id)
                ->where('file_name', $newName)
                ->where('is_folder', DB::raw($file->is_folder ? 'true' : 'false'))
                ->where('id', '!=', $file->id)
                ->first();

            if ($duplicate) {
                $type = $file->is_folder ? 'folder' : 'file';
                return response()->json([
                    'success' => false,
                    'message' => "A {$type} with the name '{$newName}' already exists in this location"
                ], 400);
            }

            // If it's a file (not folder), update the file path in storage
            $newFilePath = null;
            if (!$file->is_folder && $file->file_path) {
                // Extract the directory and extension from the original file path
                $pathInfo = pathinfo($file->file_path);
                $directory = $pathInfo['dirname'];
                $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
                
                // Generate new file path
                $newFilePath = $directory . '/' . $newName . $extension;
                
                Log::info('File rename - path update', [
                    'user_id' => $user->id,
                    'file_id' => $file->id,
                    'old_path' => $file->file_path,
                    'new_path' => $newFilePath,
                    'old_name' => $oldName,
                    'new_name' => $newName
                ]);

                // Move the file in Supabase storage
                $this->moveFileInSupabase($file->file_path, $newFilePath);
            }

            // Update the file record
            $updateData = [
                'file_name' => $newName,
                'updated_at' => now()
            ];

            if ($newFilePath) {
                $updateData['file_path'] = $newFilePath;
            }

            DB::table('files')
                ->where('id', $file->id)
                ->update($updateData);

            // Update the model instance with new data for activity logging
            $file->file_name = $newName;
            if ($newFilePath) {
                $file->file_path = $newFilePath;
            }

            // Log rename activity
            try {
                SystemActivity::logFileActivity(
                    SystemActivity::ACTION_UPDATED,
                    $file,
                    "{$user->name} renamed " . ($file->is_folder ? 'folder' : 'file') . " from '{$oldName}' to '{$newName}'",
                    [
                        'old_name' => $oldName,
                        'new_name' => $newName,
                        'file_path_updated' => !is_null($newFilePath),
                        'action_type' => 'rename'
                    ],
                    SystemActivity::RISK_LOW,
                    $user
                );
                Log::info('Rename activity logged successfully', ['file_id' => $file->id]);
            } catch (\Exception $e) {
                Log::error('Failed to log rename activity', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the rename operation if activity logging fails
            }

            // Log the rename action
            Log::info('File/folder renamed successfully', [
                'user_id' => $user->id,
                'file_id' => $file->id,
                'old_name' => $oldName,
                'new_name' => $newName,
                'is_folder' => $file->is_folder,
                'file_path_updated' => !is_null($newFilePath)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item renamed successfully',
                'file' => [
                    'id' => $file->id,
                    'old_name' => $oldName,
                    'new_name' => $newName,
                    'is_folder' => $file->is_folder,
                    'file_path' => $newFilePath ?? $file->file_path
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = collect($e->errors())->flatten()->implode(' ');
            return response()->json([
                'success' => false,
                'message' => 'Invalid name: ' . $errors
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('File rename failed', [
                'user_id' => Auth::id(),
                'file_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to rename item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a file with the same name exists in the target folder
     */
    public function checkDuplicate(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $validated = $request->validate([
                'file_name' => 'required|string|max:255',
                'parent_id' => 'nullable|integer|exists:files,id',
            ]);

            $fileName = $validated['file_name'];
            $parentId = $validated['parent_id'] ?? null;

            // Check if file exists in the same folder
            $existingFile = $this->findDuplicateFile($user->id, $fileName, $parentId);

            if ($existingFile) {
                // Generate suggested name with increment
                $suggestedName = $this->generateUniqueFileName($user->id, $fileName, $parentId);
                
                return response()->json([
                    'success' => true,
                    'duplicate_exists' => true,
                    'existing_file' => [
                        'id' => $existingFile->id,
                        'name' => $existingFile->file_name,
                        'size' => $existingFile->file_size,
                        'updated_at' => $existingFile->updated_at->format('M d, Y')
                    ],
                    'suggested_name' => $suggestedName
                ]);
            }

            return response()->json([
                'success' => true,
                'duplicate_exists' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Duplicate check failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check for duplicates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find duplicate file in the same folder
     */
    private function findDuplicateFile(int $userId, string $fileName, ?int $parentId)
    {
        $query = File::where('user_id', $userId)
            ->where('file_name', $fileName)
            ->where('is_folder', DB::raw('false'))
            ->whereNull('deleted_at');

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        return $query->first();
    }

    /**
     * Generate unique file name with incrementing number (Google Drive style)
     * Example: file.pdf -> file (1).pdf -> file (2).pdf
     */
    private function generateUniqueFileName(int $userId, string $fileName, ?int $parentId): string
    {
        // Extract file name and extension
        $pathInfo = pathinfo($fileName);
        $baseName = $pathInfo['filename'];
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

        $counter = 1;
        $newFileName = $fileName;

        // Keep incrementing until we find a unique name
        while ($this->findDuplicateFile($userId, $newFileName, $parentId)) {
            $newFileName = $baseName . ' (' . $counter . ')' . $extension;
            $counter++;
        }

        return $newFileName;
    }

    /**
     * Move/rename a file in Supabase storage
     */
    private function moveFileInSupabase(string $oldPath, string $newPath): void
    {
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = config('services.supabase.service_key');
        $bucketName = 'docs';

        if (!$supabaseUrl || !$supabaseKey) {
            Log::error('Supabase URL or Service Key is not configured for file rename');
            throw new \Exception('Supabase configuration missing');
        }

        // Disable SSL verification for local development
        $verifySsl = !(config('app.env') === 'local' || config('app.debug'));
        $client = new Client([
            'base_uri' => $supabaseUrl,
            'verify' => $verifySsl,
            'timeout' => 30,
        ]);

        try {
            Log::info('Moving file in Supabase storage for rename', [
                'from' => $oldPath,
                'to' => $newPath
            ]);

            $response = $client->post("/storage/v1/object/move", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'bucketId' => $bucketName,
                    'sourceKey' => $oldPath,
                    'destinationKey' => $newPath,
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Supabase move operation failed with status: ' . $response->getStatusCode());
            }

            Log::info('File successfully moved in Supabase storage', [
                'old_path' => $oldPath,
                'new_path' => $newPath
            ]);

        } catch (RequestException $e) {
            Log::error('Failed to move file in Supabase storage during rename', [
                'old_path' => $oldPath,
                'new_path' => $newPath,
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'N/A',
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to rename file in storage: ' . $e->getMessage());
        }
    }

    /**
     * Show specific folder in dashboard (URL-based navigation)
     */
    public function showFolder(Request $request, int $id)
    {
        $user = Auth::user();
        
        // Get the folder - use DB::raw for PostgreSQL boolean compatibility
        $folder = $user->files()
            ->where('id', $id)
            ->where('is_folder', DB::raw('true'))
            ->whereNull('deleted_at')
            ->firstOrFail();
        
        // Check if user is accessing their own share link while logged in
        if ($request->has('share_redirect') && $folder->share_token) {
            // Redirect to dashboard with folder open
            return redirect()->route('user.dashboard')->with([
                'open_folder' => $id,
                'folder_name' => $folder->file_name
            ]);
        }
        
        // Redirect to user dashboard with folder context
        return redirect()->route('user.dashboard')->with([
            'current_folder' => $folder,
            'folder_id' => $id
        ]);
    }

    /**
     * Show specific file in dashboard (URL-based navigation)
     */
    public function showFile(Request $request, int $id)
    {
        $user = Auth::user();
        
        // Get the file - use DB::raw for PostgreSQL boolean compatibility
        $file = $user->files()
            ->where('id', $id)
            ->where('is_folder', DB::raw('false'))
            ->whereNull('deleted_at')
            ->firstOrFail();
        
        // Check if user is accessing their own share link while logged in
        if ($request->has('share_redirect') && $file->share_token) {
            // Redirect to dashboard with file selected
            return redirect()->route('user.dashboard')->with([
                'selected_file' => $id,
                'file_name' => $file->file_name
            ]);
        }
        
        // Redirect to user dashboard with file context
        return redirect()->route('user.dashboard')->with([
            'current_file' => $file,
            'file_id' => $id
        ]);
    }
}
