<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\PublicShare;
use App\Models\SharedFileCopy;
use App\Models\FileOtpSecurity;
use App\Models\SystemActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class PublicShareController extends Controller
{
    /**
     * Create a new public share
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_id' => 'required|integer|exists:files,id',
                'is_one_time' => 'boolean',
                'expires_in_days' => 'nullable|integer|min:1|max:365',
                'password' => 'nullable|string|min:6|max:50',
                'max_downloads' => 'nullable|integer|min:1|max:1000',
            ]);

            $user = Auth::user();
            $file = File::where('id', $request->file_id)
                       ->where('user_id', $user->id)
                       ->firstOrFail();

            // Check if file has OTP protection enabled
            $otpSecurity = FileOtpSecurity::where('user_id', $user->id)
                ->where('file_id', $file->id)
                ->where('is_otp_enabled', DB::raw('true'))
                ->first();

            if ($otpSecurity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot share files with OTP protection enabled. Please disable OTP first.',
                    'requires_otp_disable' => true
                ], 400);
            }

            // Check if password protection is requested (Premium feature)
            if ($request->filled('password') && !$user->is_premium) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password protection is a premium feature. Upgrade to premium to use this feature.',
                    'requires_premium' => true
                ], 403);
            }

            // Prepare share options with explicit boolean conversion for PostgreSQL
            $options = [
                'is_one_time' => (bool) $request->get('is_one_time', false),
                'max_downloads' => $request->get('max_downloads'),
                'password_protected' => (bool) $request->filled('password'),
            ];

            if ($request->filled('password')) {
                $options['password'] = $request->password;
            }

            if ($request->filled('expires_in_days')) {
                $options['expires_at'] = now()->addDays($request->expires_in_days);
            }

            // Check if user already has an active share for this file
            $existingShare = PublicShare::where('user_id', $user->id)
                ->where('file_id', $file->id)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($existingShare) {
                // Return existing share instead of creating a new one
                $share = $existingShare;
                Log::info('Returning existing share', [
                    'share_id' => $share->id,
                    'existing_is_one_time' => $share->is_one_time,
                    'existing_password_protected' => $share->password_protected,
                    'requested_options' => $options
                ]);
            } else {
                // Create new share
                $share = PublicShare::createShare($file, $options);
                Log::info('Created new share', [
                    'share_id' => $share->id,
                    'new_options' => $options
                ]);
            }

            // Log activity
            $activityMessage = $existingShare 
                ? "{$user->name} accessed existing share link for " . ($file->is_folder ? 'folder' : 'file') . " '{$file->file_name}'"
                : "{$user->name} created a public share link for " . ($file->is_folder ? 'folder' : 'file') . " '{$file->file_name}'";
                
            SystemActivity::logFileActivity(
                SystemActivity::ACTION_SHARED,
                $file,
                $activityMessage,
                [
                    'share_token' => $share->share_token,
                    'share_type' => $share->share_type,
                    'is_one_time' => $share->is_one_time,
                    'password_protected' => $share->password_protected,
                    'expires_at' => $share->expires_at?->toISOString(),
                ],
                SystemActivity::RISK_LOW,
                $user
            );


            return response()->json([
                'success' => true,
                'message' => 'Share link created successfully',
                'share' => [
                    'id' => $share->id,
                    'token' => $share->share_token,
                    'url' => $share->getPublicUrl(),
                    'type' => $share->share_type,
                    'is_one_time' => $share->is_one_time,
                    'password_protected' => $share->password_protected,
                    'expires_at' => $share->expires_at?->toISOString(),
                    'download_count' => $share->download_count,
                    'max_downloads' => $share->max_downloads,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors (like password too short)
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create public share', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'file_id' => $request->file_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create share link. Please try again.'
            ], 500);
        }
    }

    /**
     * Show public share page (MediaFire style)
     */
    public function show(string $token)
    {
        try {
            // Check if token is a valid UUID format before searching UUID columns
            $isValidUuid = $this->isValidUuid($token);
            
            $file = null;
            
            // Only search UUID columns if token is valid UUID format
            if ($isValidUuid) {
                $file = File::where('share_token', $token)
                    ->orWhere('uuid', $token)
                    ->first();
            }
            
            if ($file) {
                // Check if user is logged in and owns this file
                if (Auth::check() && $file->isOwnedBy(Auth::user())) {
                    // Redirect to dashboard with folder navigation parameters
                    if ($file->is_folder) {
                        return redirect()->route('user.dashboard')->with([
                            'navigate_to_folder' => $file->id,
                            'folder_name' => $file->file_name
                        ]);
                    } else {
                        return redirect()->route('user.dashboard')->with([
                            'navigate_to_parent' => $file->parent_id,
                            'select_file' => $file->id,
                            'file_name' => $file->file_name
                        ]);
                    }
                }
                
                // Create temporary share object for public view
                $share = (object) [
                    'share_token' => $token,
                    'file' => $file,
                    'user' => $file->user,
                    'share_type' => $file->is_folder ? 'folder' : 'file',
                    'password_protected' => false,
                    'expires_at' => null,
                    'is_one_time' => false
                ];
                
                $folderFiles = collect();
                $otpInfo = ['has_otp_files' => false, 'otp_count' => 0, 'otp_files' => []];
                
                if ($file->is_folder) {
                    $folderFiles = $this->getFolderDirectChildren($file);
                    $otpInfo = $this->folderContainsOtpFiles($file);
                }
                
                // Build proper breadcrumb path for nested folders
                $breadcrumbs = $this->buildBreadcrumbPathForFile($file);
                
                Log::info('UUID share breadcrumbs generated', [
                    'file_id' => $file->id,
                    'file_name' => $file->file_name,
                    'breadcrumbs_count' => count($breadcrumbs),
                    'breadcrumbs' => $breadcrumbs,
                    'otp_info' => $otpInfo
                ]);
                
                return view('public.share-download', [
                    'share' => $share,
                    'file' => $file,
                    'folderFiles' => $folderFiles,
                    'breadcrumbs' => $breadcrumbs,
                    'otpInfo' => $otpInfo
                ]);
            }

            // Fallback to old PublicShare system
            $share = PublicShare::with(['file', 'user'])
                ->where('share_token', $token)
                ->firstOrFail();

            // Check if user is logged in and owns this file (old system)
            if (Auth::check() && $share->file->isOwnedBy(Auth::user())) {
                // Redirect to dashboard with folder navigation parameters
                if ($share->file->is_folder) {
                    return redirect()->route('user.dashboard')->with([
                        'navigate_to_folder' => $share->file->id,
                        'folder_name' => $share->file->file_name
                    ]);
                } else {
                    return redirect()->route('user.dashboard')->with([
                        'navigate_to_parent' => $share->file->parent_id,
                        'select_file' => $share->file->id,
                        'file_name' => $share->file->file_name
                    ]);
                }
            }

            // Check if share is valid
            if (!$share->isValid()) {
                return view('public.share-expired', [
                    'share' => $share,
                    'reason' => $share->status
                ]);
            }

            // If password protected, show password form
            if ($share->hasPassword() && !session("share_password_verified_{$token}")) {
                return view('public.share-password', [
                    'share' => $share
                ]);
            }

            $folderFiles = collect();
            
            // If it's a folder, get the folder contents (direct children only)
            $otpInfo = ['has_otp_files' => false, 'otp_count' => 0, 'otp_files' => []];
            if ($share->file->is_folder) {
                $folderFiles = $this->getFolderDirectChildren($share->file);
                $otpInfo = $this->folderContainsOtpFiles($share->file);
            }

            // Build breadcrumbs for root folder
            $breadcrumbs = [[
                'id' => $share->file->id,
                'name' => $share->file->file_name,
                'is_root' => true,
                'share_token' => $share->share_token,
                'url' => route('public.share.show', $share->share_token)
            ]];

            return view('public.share-download', [
                'share' => $share,
                'file' => $share->file,
                'folderFiles' => $folderFiles,
                'breadcrumbs' => $breadcrumbs,
                'otpInfo' => $otpInfo
            ]);

        } catch (\Exception $e) {
            Log::error('Public share error', [
                'token' => $token,
                'token_length' => strlen($token),
                'is_uuid_format' => $this->isValidUuid($token),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('public.share-not-found');
        }
    }

    /**
     * Verify password for protected share
     */
    public function verifyPassword(Request $request, string $token): JsonResponse
    {
        try {
            $request->validate([
                'password' => 'required|string'
            ]);

            $share = PublicShare::where('share_token', $token)->firstOrFail();

            if (!$share->checkPassword($request->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect password'
                ], 400);
            }

            // Set session to remember password verification
            session(["share_password_verified_{$token}" => true]);

            return response()->json([
                'success' => true,
                'message' => 'Password verified',
                'redirect_url' => url("/s/{$token}")
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid share link'
            ], 404);
        }
    }

    /**
     * Download file from public share
     */
    public function download(string $token)
    {
        try {
            $share = PublicShare::with(['file', 'user'])
                ->where('share_token', $token)
                ->firstOrFail();

            // Check if share is valid
            if (!$share->isValid()) {
                abort(410, 'This share link has expired or is no longer available');
            }

            // Check password if required
            if ($share->hasPassword() && !session("share_password_verified_{$token}")) {
                abort(403, 'Password required to access this file');
            }

            $file = $share->file;

            // Handle folder download (ZIP)
            if ($file->is_folder) {
                return $this->downloadFolderAsZip($share, $file);
            }

            // Handle single file download
            return $this->downloadSingleFile($share, $file);

        } catch (\Exception $e) {
            Log::error('Public download failed', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            abort(404, 'File not found or no longer available');
        }
    }

    /**
     * Download single file
     */
    private function downloadSingleFile(PublicShare $share, File $file)
    {
        // Increment download count
        $share->incrementDownload();

        // Get file content from Supabase
        $supabaseUrl = env('SUPABASE_URL');
        $filePath = ltrim($file->file_path, '/');

        if (empty($supabaseUrl) || empty($filePath)) {
            abort(404, 'File not found in storage');
        }

        try {
            // Handle Arweave files
            if (!empty($file->arweave_url)) {
                return redirect()->away($file->arweave_url);
            }

            // Download from Supabase with proper error handling
            $fileUrl = "{$supabaseUrl}/storage/v1/object/public/docs/{$filePath}";
            
            // Check if file exists and is accessible
            $headers = @get_headers($fileUrl);
            if (!$headers || strpos($headers[0], '200') === false) {
                Log::error('Supabase file not accessible', [
                    'file_url' => $fileUrl,
                    'file_id' => $file->id,
                    'headers' => $headers
                ]);
                abort(404, 'File not found or no longer available');
            }
            
            return response()->streamDownload(function () use ($fileUrl) {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 30,
                        'method' => 'GET'
                    ]
                ]);
                
                $stream = fopen($fileUrl, 'r', false, $context);
                if (!$stream) {
                    throw new \Exception('Failed to open file stream');
                }
                
                fpassthru($stream);
                fclose($stream);
            }, $file->file_name, [
                'Content-Type' => $file->mime_type ?? 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . $file->file_name . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('File download failed', [
                'file_id' => $file->id,
                'share_token' => $share->share_token,
                'error' => $e->getMessage()
            ]);

            abort(404, 'File not found or could not be downloaded');
        }
    }

    /**
     * Download folder as ZIP (synchronous)
     */
    private function downloadFolderAsZip(PublicShare $share, File $folder)
    {
        // Get all files in folder (recursive)
        $files = $this->getFolderFiles($folder);

        if ($files->isEmpty()) {
            abort(404, 'Folder is empty or files not found');
        }

        // Check total size limit (500MB)
        $totalSize = $files->sum(function ($file) {
            return (int) str_replace(['KB', 'MB', 'GB'], '', $file->file_size) * 1024;
        });

        if ($totalSize > 500 * 1024 * 1024) { // 500MB limit
            abort(413, 'Folder too large for download. Maximum size is 500MB.');
        }

        // Increment download count
        $share->incrementDownload();

        // Create temporary ZIP file
        $zipFileName = $folder->file_name . '_' . time() . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Ensure temp directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            // Fallback: Download first file or show error
            $firstFile = $files->where('is_folder', false)->first();
            if ($firstFile) {
                return $this->downloadSingleFile($share, $firstFile);
            }
            abort(500, 'ZIP functionality not available. Please install PHP ZIP extension.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            abort(500, 'Could not create ZIP file');
        }

        $supabaseUrl = env('SUPABASE_URL');

        foreach ($files as $file) {
            if ($file->is_folder) continue;

            try {
                $filePath = ltrim($file->file_path, '/');
                $fileUrl = "{$supabaseUrl}/storage/v1/object/public/docs/{$filePath}";
                
                $fileContent = file_get_contents($fileUrl);
                if ($fileContent !== false) {
                    $zip->addFromString($file->file_name, $fileContent);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to add file to ZIP', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage()
                ]);
                // Continue with other files
            }
        }

        $zip->close();

        // Return ZIP file and schedule cleanup
        return response()->download($zipPath, $folder->file_name . '.zip')->deleteFileAfterSend(true);
    }

    /**
     * Get all files in folder recursively (for ZIP downloads)
     */
    private function getFolderFiles(File $folder)
    {
        return File::where('user_id', $folder->user_id)
            ->where(function ($query) use ($folder) {
                $query->where('parent_id', $folder->id)
                      ->orWhere('file_path', 'like', $folder->file_path . '/%');
            })
            ->whereNull('deleted_at')
            ->get();
    }

    /**
     * Get direct children of folder only (non-recursive), excluding OTP files
     */
    private function getFolderDirectChildren(File $folder)
    {
        return File::where('user_id', $folder->user_id)
            ->where('parent_id', $folder->id)
            ->whereNull('deleted_at')
            ->orderByRaw('is_folder DESC')
            ->orderBy('file_name', 'asc')
            ->get();
    }

    /**
     * Check if folder contains OTP files (for warning message)
     */
    private function folderContainsOtpFiles(File $folder): array
    {
        // Check for OTP files by looking at public_shares table instead
        $otpShares = PublicShare::whereIn('file_id', function($query) use ($folder) {
                $query->select('id')
                      ->from('files')
                      ->where('user_id', $folder->user_id)
                      ->where('parent_id', $folder->id)
                      ->whereNull('deleted_at');
            })
            ->where('is_one_time', DB::raw('true'))
            ->with('file')
            ->get();

        return [
            'has_otp_files' => $otpShares->count() > 0,
            'otp_count' => $otpShares->count(),
            'otp_files' => $otpShares->pluck('file.file_name')->filter()->toArray()
        ];
    }

    /**
     * Save file to user's account (Copy to My Files)
     */
    public function saveToMyFiles(Request $request, string $token): JsonResponse
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login to save files to your account',
                    'requires_login' => true
                ], 401);
            }

            $user = Auth::user();
            $share = PublicShare::with(['file', 'user'])
                ->where('share_token', $token)
                ->firstOrFail();

            // Check if share is valid
            if (!$share->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This share link has expired'
                ], 410);
            }

            // Check if user already copied this file
            if (SharedFileCopy::hasUserCopied($share, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already saved this file to your account'
                ], 400);
            }

            $originalFile = $share->file;

            // Create copy of the file in user's root directory
            $copiedFile = File::create([
                'user_id' => $user->id,
                'file_name' => $originalFile->file_name,
                'file_path' => $originalFile->file_path, // Same storage path
                'file_size' => $originalFile->file_size,
                'file_type' => $originalFile->file_type,
                'mime_type' => $originalFile->mime_type,
                'parent_id' => null, // Root directory
                'is_folder' => $originalFile->is_folder,
                'arweave_url' => $originalFile->arweave_url,
                'is_arweave' => $originalFile->is_arweave,
            ]);

            // Create copy record
            SharedFileCopy::createCopy($share, $user, $copiedFile);

            // Log activity
            SystemActivity::logFileActivity(
                SystemActivity::ACTION_COPIED,
                $copiedFile,
                "{$user->name} saved shared " . ($originalFile->is_folder ? 'folder' : 'file') . " '{$originalFile->file_name}' to their account",
                [
                    'original_owner' => $share->user->name,
                    'share_token' => $share->share_token,
                    'copied_from_public_share' => true
                ],
                SystemActivity::RISK_LOW,
                $user
            );

            return response()->json([
                'success' => true,
                'message' => 'File saved to your account successfully',
                'copied_file' => [
                    'id' => $copiedFile->id,
                    'name' => $copiedFile->file_name,
                    'type' => $copiedFile->is_folder ? 'folder' : 'file'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save file to user account', [
                'token' => $token,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save file to your account'
            ], 500);
        }
    }

    /**
     * Get user's public shares
     */
    public function getUserShares(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $shares = PublicShare::with(['file'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($share) {
                    return [
                        'id' => $share->id,
                        'token' => $share->share_token,
                        'url' => $share->getPublicUrl(),
                        'file_name' => $share->file->file_name,
                        'file_type' => $share->share_type,
                        'is_one_time' => $share->is_one_time,
                        'password_protected' => $share->password_protected,
                        'download_count' => $share->download_count,
                        'max_downloads' => $share->max_downloads,
                        'expires_at' => $share->expires_at?->toISOString(),
                        'status' => $share->status,
                        'created_at' => $share->created_at->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'shares' => $shares
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user shares', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load shares'
            ], 500);
        }
    }

    /**
     * Get files shared with the current user
     */
    public function getSharedWithMe(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $sharedFiles = SharedFileCopy::with([
                'originalShare.user',
                'copiedFile'
            ])
            ->where('copied_by_user_id', $user->id)
            ->orderBy('copied_at', 'desc')
            ->get()
            ->map(function ($sharedFile) {
                return [
                    'id' => $sharedFile->id,
                    'copied_at' => $sharedFile->copied_at->toISOString(),
                    'original_share' => [
                        'id' => $sharedFile->originalShare->id,
                        'share_token' => $sharedFile->originalShare->share_token,
                        'user' => [
                            'id' => $sharedFile->originalShare->user->id,
                            'name' => $sharedFile->originalShare->user->name,
                        ]
                    ],
                    'copied_file' => [
                        'id' => $sharedFile->copiedFile->id,
                        'file_name' => $sharedFile->copiedFile->file_name,
                        'file_size' => $sharedFile->copiedFile->file_size,
                        'file_type' => $sharedFile->copiedFile->file_type,
                        'mime_type' => $sharedFile->copiedFile->mime_type,
                        'is_folder' => $sharedFile->copiedFile->is_folder,
                        'created_at' => $sharedFile->copiedFile->created_at->toISOString(),
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'shared_files' => $sharedFiles
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get shared files', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load shared files'
            ], 500);
        }
    }

    /**
     * Delete a public share
     */
    public function delete(int $shareId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $share = PublicShare::where('id', $shareId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $share->delete();

            return response()->json([
                'success' => true,
                'message' => 'Share link deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete share', [
                'share_id' => $shareId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete share link'
            ], 500);
        }
    }

    /**
     * Show individual file within shared folder
     */
    public function showFile(string $token, int $fileId)
    {
        try {
            $share = PublicShare::where('share_token', $token)->firstOrFail();
            
            // Verify share is valid
            if ($share->isExpired()) {
                return view('public.share-expired', compact('share'));
            }

            // Check password protection
            if ($share->password_protected && !session("share_verified_{$share->id}")) {
                return view('public.share-password', compact('share'));
            }

            // Get the specific file
            $file = File::where('id', $fileId)
                ->where('user_id', $share->user_id)
                ->firstOrFail();

            // Verify file is within the shared folder
            if ($share->file->is_folder) {
                $folderFiles = $this->getFolderFiles($share->file);
                if (!$folderFiles->contains('id', $fileId)) {
                    abort(404, 'File not found in shared folder');
                }
            } else {
                // If sharing a single file, only allow access to that file
                if ($file->id !== $share->file_id) {
                    abort(404, 'File not found');
                }
            }

            return view('public.share-download', compact('share', 'file'));

        } catch (\Exception $e) {
            Log::error('Failed to show individual file', [
                'token' => $token,
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return view('public.share-not-found');
        }
    }

    /**
     * Download individual file within shared folder
     */
    public function downloadFile(string $token, int $fileId)
    {
        try {
            $share = PublicShare::where('share_token', $token)->firstOrFail();
            
            // Verify share is valid
            if ($share->isExpired()) {
                abort(404, 'Share has expired');
            }

            // Check password protection
            if ($share->password_protected && !session("share_verified_{$share->id}")) {
                abort(403, 'Password required');
            }

            // Get the specific file
            $file = File::where('id', $fileId)
                ->where('user_id', $share->user_id)
                ->firstOrFail();

            // Verify file is within the shared folder
            if ($share->file->is_folder) {
                $folderFiles = $this->getFolderFiles($share->file);
                if (!$folderFiles->contains('id', $fileId)) {
                    abort(404, 'File not found in shared folder');
                }
            } else {
                // If sharing a single file, only allow access to that file
                if ($file->id !== $share->file_id) {
                    abort(404, 'File not found');
                }
            }

            return $this->downloadSingleFile($share, $file);

        } catch (\Exception $e) {
            Log::error('Failed to download individual file', [
                'token' => $token,
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Download failed');
        }
    }

    /**
     * Save individual file to user's account
     */
    public function saveFileToMyFiles(Request $request, string $token, int $fileId): JsonResponse
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be logged in to save files'
                ], 401);
            }

            $share = PublicShare::where('share_token', $token)->firstOrFail();
            $user = Auth::user();

            // Verify share is valid
            if ($share->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Share has expired'
                ], 404);
            }

            // Get the specific file
            $file = File::where('id', $fileId)
                ->where('user_id', $share->user_id)
                ->firstOrFail();

            // Verify file is within the shared folder
            if ($share->file->is_folder) {
                $folderFiles = $this->getFolderFiles($share->file);
                if (!$folderFiles->contains('id', $fileId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File not found in shared folder'
                    ], 404);
                }
            }

            // Check if already saved
            $existingCopy = SharedFileCopy::where('user_id', $user->id)
                ->where('original_file_id', $file->id)
                ->first();

            if ($existingCopy) {
                return response()->json([
                    'success' => false,
                    'message' => 'File already saved to your account'
                ]);
            }

            // Create copy record
            SharedFileCopy::create([
                'user_id' => $user->id,
                'public_share_id' => $share->id,
                'original_file_id' => $file->id,
                'file_name' => $file->file_name,
                'file_type' => $file->file_type,
                'file_size' => $file->file_size,
                'file_path' => $file->file_path,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File saved to your account successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save individual file', [
                'token' => $token,
                'file_id' => $fileId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save file'
            ], 500);
        }
    }

    /**
     * Show nested folder within shared folder
     */
    public function showFolder(string $token, int $folderId)
    {
        try {
            $parentShare = PublicShare::where('share_token', $token)->firstOrFail();
            
            // Verify parent share is valid
            if ($parentShare->isExpired()) {
                return view('public.share-expired', compact('parentShare'));
            }

            // Check password protection
            if ($parentShare->password_protected && !session("share_verified_{$parentShare->id}")) {
                return view('public.share-password', compact('parentShare'));
            }

            // Get the specific folder
            $folder = File::where('id', $folderId)
                ->where('user_id', $parentShare->user_id)
                ->where('is_folder', DB::raw('true'))
                ->firstOrFail();

            // Check if user is logged in and owns this folder
            if (Auth::check() && $folder->isOwnedBy(Auth::user())) {
                // Redirect to dashboard with folder navigation
                return redirect()->route('user.dashboard')->with([
                    'navigate_to_folder' => $folder->id,
                    'folder_name' => $folder->file_name
                ]);
            }

            // Verify folder is within the shared folder hierarchy
            if (!$this->isFolderWithinSharedHierarchy($folder, $parentShare->file)) {
                abort(404, 'Folder not found in shared hierarchy');
            }

            // Create or get individual share for this folder (MediaFire style)
            $folderShare = $this->getOrCreateNestedShare($folder, $parentShare);

            // Get files in this nested folder (direct children only)
            $folderFiles = $this->getFolderDirectChildren($folder);
            $otpInfo = $this->folderContainsOtpFiles($folder);
            
            // Build breadcrumb path
            $breadcrumbs = $this->buildBreadcrumbPath($folder, $parentShare);

            return view('public.share-download', compact('folderFiles', 'breadcrumbs', 'otpInfo'))
                ->with('share', $folderShare)
                ->with('file', $folder);

        } catch (\Exception $e) {
            Log::error('Failed to show nested folder', [
                'token' => $token,
                'folder_id' => $folderId,
                'error' => $e->getMessage()
            ]);
            return view('public.share-not-found');
        }
    }

    /**
     * Get or create individual share for nested items (MediaFire style)
     */
    private function getOrCreateNestedShare(File $file, PublicShare $parentShare): PublicShare
    {
        // Check if this file already has a share
        $existingShare = PublicShare::where('file_id', $file->id)
            ->where('user_id', $file->user_id)
            ->first();

        if ($existingShare) {
            return $existingShare;
        }

        // Create new share with inherited settings from parent - use DB::raw for PostgreSQL boolean compatibility
        $nestedShare = PublicShare::create([
            'user_id' => $file->user_id,
            'file_id' => $file->id,
            'share_token' => PublicShare::generateUniqueToken(),
            'share_type' => $file->is_folder ? 'folder' : 'file',
            'is_one_time' => DB::raw($parentShare->is_one_time ? 'true' : 'false'),
            'max_downloads' => $parentShare->max_downloads,
            'expires_at' => $parentShare->expires_at,
            'password_protected' => DB::raw($parentShare->password_protected ? 'true' : 'false'),
            'password_hash' => $parentShare->password_hash,
        ]);

        Log::info('Created nested share', [
            'parent_token' => $parentShare->share_token,
            'nested_token' => $nestedShare->share_token,
            'file_id' => $file->id,
            'file_name' => $file->file_name
        ]);

        return $nestedShare;
    }

    /**
     * Check if folder is within shared folder hierarchy
     */
    private function isFolderWithinSharedHierarchy(File $folder, File $sharedRoot): bool
    {
        // If the folder is the shared root itself
        if ($folder->id === $sharedRoot->id) {
            return true;
        }

        // If shared root is not a folder, only allow access to itself
        if (!$sharedRoot->is_folder) {
            return $folder->id === $sharedRoot->id;
        }

        // Traverse up the folder hierarchy to check if we reach the shared root
        $current = $folder;
        $maxDepth = 20; // Prevent infinite loops
        $depth = 0;

        while ($current && $depth < $maxDepth) {
            if ($current->id === $sharedRoot->id) {
                return true;
            }
            
            // Move up to parent folder
            $current = $current->parent_id ? File::find($current->parent_id) : null;
            $depth++;
        }

        return false;
    }

    /**
     * Build breadcrumb path for any file by finding the root share
     */
    private function buildBreadcrumbPathForFile(File $file): array
    {
        // Find the root share for this file's hierarchy
        $rootShare = $this->findRootShareForFile($file);
        
        if (!$rootShare) {
            // If no root share found, create simple breadcrumb
            return [[
                'id' => $file->id,
                'name' => $file->file_name,
                'is_root' => true,
                'share_token' => $file->share_token ?? $file->uuid,
                'url' => route('public.share.show', $file->share_token ?? $file->uuid)
            ]];
        }
        
        return $this->buildBreadcrumbPath($file, $rootShare);
    }

    /**
     * Find the root share for a given file
     */
    private function findRootShareForFile(File $file): ?PublicShare
    {
        // First, check if this file itself has a share
        $directShare = PublicShare::where('file_id', $file->id)->first();
        if ($directShare) {
            // Check if this is a root share (no parent shares in the hierarchy)
            $current = $file;
            while ($current->parent_id) {
                $parent = File::find($current->parent_id);
                if (!$parent) break;
                
                $parentShare = PublicShare::where('file_id', $parent->id)->first();
                if ($parentShare) {
                    // Found a parent share, so this is the root
                    return $parentShare;
                }
                $current = $parent;
            }
            // No parent shares found, this is the root
            return $directShare;
        }
        
        // If no direct share, look for shares in parent hierarchy
        $current = $file;
        while ($current->parent_id) {
            $parent = File::find($current->parent_id);
            if (!$parent) break;
            
            $parentShare = PublicShare::where('file_id', $parent->id)->first();
            if ($parentShare) {
                return $parentShare;
            }
            $current = $parent;
        }
        
        return null;
    }

    /**
     * Build breadcrumb path for nested navigation
     */
    private function buildBreadcrumbPath(File $currentFolder, PublicShare $rootShare): array
    {
        $breadcrumbs = [];
        $current = $currentFolder;
        $maxDepth = 20;
        $depth = 0;

        // Build path from current folder up to shared root
        while ($current && $depth < $maxDepth) {
            // Get or create share token for this folder level
            $shareToken = null;
            if ($current->id === $rootShare->file_id) {
                // Use root share token for the root folder
                $shareToken = $rootShare->share_token;
            } else {
                // Get existing share for this folder or use root token
                $existingShare = PublicShare::where('file_id', $current->id)
                    ->where('user_id', $current->user_id)
                    ->first();
                $shareToken = $existingShare ? $existingShare->share_token : $rootShare->share_token;
            }

            array_unshift($breadcrumbs, [
                'id' => $current->id,
                'name' => $current->file_name,
                'is_root' => $current->id === $rootShare->file_id,
                'share_token' => $shareToken,
                'url' => route('public.share.show', $shareToken)
            ]);

            if ($current->id === $rootShare->file_id) {
                break;
            }

            $current = $current->parent_id ? File::find($current->parent_id) : null;
            $depth++;
        }

        return $breadcrumbs;
    }

    /**
     * Check if a string is a valid UUID format
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    /**
     * API: Get or create individual share token for nested items
     */
    public function getOrCreateShareToken(Request $request): JsonResponse
    {
        try {
            Log::info('API: getOrCreateShareToken called', [
                'file_id' => $request->input('file_id'),
                'parent_token' => $request->input('parent_token'),
                'request_data' => $request->all()
            ]);
            
            $request->validate([
                'file_id' => 'required|integer',
                'parent_token' => 'required|string'
            ]);

            $fileId = $request->input('file_id');
            $parentToken = $request->input('parent_token');

            // Get parent share
            $parentShare = PublicShare::where('share_token', $parentToken)->firstOrFail();

            // Get the file
            $file = File::where('id', $fileId)
                ->where('user_id', $parentShare->user_id)
                ->firstOrFail();

            // Check if file has an existing OTP share (one-time access)
            $existingOtpShare = PublicShare::where('file_id', $file->id)
                ->where('is_one_time', DB::raw('true'))
                ->first();
                
            if ($existingOtpShare) {
                return response()->json([
                    'success' => false,
                    'message' => 'This file is marked as one-time access and cannot be shared'
                ], 403);
            }

            // Verify file is within shared hierarchy
            if (!$this->isFolderWithinSharedHierarchy($file, $parentShare->file)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found in shared hierarchy'
                ], 404);
            }

            // Get or create individual share
            $individualShare = $this->getOrCreateNestedShare($file, $parentShare);

            return response()->json([
                'success' => true,
                'share_token' => $individualShare->share_token,
                'nested_token' => $individualShare->share_token, // For backward compatibility
                'share_url' => $individualShare->getPublicUrl(),
                'file_name' => $file->file_name,
                'is_folder' => $file->is_folder
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get or create share token', [
                'file_id' => $request->input('file_id'),
                'parent_token' => $request->input('parent_token'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate share token'
            ], 500);
        }
    }

}
