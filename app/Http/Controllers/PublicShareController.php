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

            // Create the share
            $share = PublicShare::createShare($file, $options);

            // Log the activity
            SystemActivity::logFileActivity(
                SystemActivity::ACTION_SHARED,
                $file,
                "{$user->name} created a public share link for " . ($file->is_folder ? 'folder' : 'file') . " '{$file->file_name}'",
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

        } catch (\Exception $e) {
            Log::error('Failed to create public share', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'file_id' => $request->file_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create share link'
            ], 500);
        }
    }

    /**
     * Show public share page (MediaFire style)
     */
    public function show(string $token)
    {
        try {
            $share = PublicShare::with(['file', 'user'])
                ->where('share_token', $token)
                ->firstOrFail();

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
            
            // If it's a folder, get the folder contents
            if ($share->file->is_folder) {
                $folderFiles = $this->getFolderFiles($share->file);
            }

            // Build breadcrumbs for root folder
            $breadcrumbs = [[
                'id' => $share->file->id,
                'name' => $share->file->file_name,
                'is_root' => true
            ]];

            return view('public.share-download', [
                'share' => $share,
                'file' => $share->file,
                'folderFiles' => $folderFiles,
                'breadcrumbs' => $breadcrumbs
            ]);

        } catch (\Exception $e) {
            Log::error('Public share not found', [
                'token' => $token,
                'error' => $e->getMessage()
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

            // Download from Supabase
            $fileUrl = "{$supabaseUrl}/storage/v1/object/public/docs/{$filePath}";
            
            return response()->streamDownload(function () use ($fileUrl) {
                $stream = fopen($fileUrl, 'r');
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
     * Get all files in folder recursively
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
                ->where('is_folder', true)
                ->firstOrFail();

            // Verify folder is within the shared folder hierarchy
            if (!$this->isFolderWithinSharedHierarchy($folder, $parentShare->file)) {
                abort(404, 'Folder not found in shared hierarchy');
            }

            // Create or get individual share for this folder (MediaFire style)
            $folderShare = $this->getOrCreateNestedShare($folder, $parentShare);

            // Get files in this nested folder
            $folderFiles = $this->getFolderFiles($folder);
            
            // Build breadcrumb path
            $breadcrumbs = $this->buildBreadcrumbPath($folder, $parentShare);

            return view('public.share-download', compact('folderFiles', 'breadcrumbs'))
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

        // Create new share with inherited settings from parent
        $nestedShare = PublicShare::create([
            'user_id' => $file->user_id,
            'file_id' => $file->id,
            'share_token' => PublicShare::generateUniqueToken(),
            'share_type' => $file->is_folder ? 'folder' : 'file',
            'is_one_time' => $parentShare->is_one_time,
            'max_downloads' => $parentShare->max_downloads,
            'expires_at' => $parentShare->expires_at,
            'password_protected' => $parentShare->password_protected,
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
     * Build breadcrumb path for nested navigation
     */
    private function buildBreadcrumbPath(File $currentFolder, PublicShare $share): array
    {
        $breadcrumbs = [];
        $current = $currentFolder;
        $maxDepth = 20;
        $depth = 0;

        // Build path from current folder up to shared root
        while ($current && $depth < $maxDepth) {
            array_unshift($breadcrumbs, [
                'id' => $current->id,
                'name' => $current->file_name,
                'is_root' => $current->id === $share->file_id
            ]);

            if ($current->id === $share->file_id) {
                break;
            }

            $current = $current->parent_id ? File::find($current->parent_id) : null;
            $depth++;
        }

        return $breadcrumbs;
    }

    /**
     * API: Get or create individual share token for nested items
     */
    public function getOrCreateShareToken(Request $request): JsonResponse
    {
        try {
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
