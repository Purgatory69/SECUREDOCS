<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\FileActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class FileVersionController extends Controller
{
    /**
     * Get version history for a file
     */
    public function getVersionHistory(File $file)
    {
        // Check if user has access to this file
        if (!$this->userCanAccessFile($file)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $versions = FileVersion::where('file_id', $file->id)
            ->with(['user:id,name,email'])
            ->orderBy('version_number', 'desc')
            ->get()
            ->map(function ($version) {
                return [
                    'id' => $version->id,
                    'version_number' => $version->version_number,
                    'version_label' => $version->version_label,
                    'file_name' => $version->file_name,
                    'file_size' => $version->file_size,
                    'formatted_size' => $version->formatted_size,
                    'file_type' => $version->file_type,
                    'mime_type' => $version->mime_type,
                    'is_current' => $version->is_current,
                    'is_latest' => $version->is_latest,
                    'version_comment' => $version->version_comment,
                    'created_at' => $version->created_at,
                    'time_since_created' => $version->time_since_created,
                    'user' => [
                        'id' => $version->user->id,
                        'name' => $version->user->name,
                        'email' => $version->user->email,
                    ],
                    'download_url' => $version->getDownloadUrl(),
                    'can_restore' => $version->canRestore(),
                ];
            });

        return response()->json([
            'file' => [
                'id' => $file->id,
                'file_name' => $file->file_name,
                'current_version' => $versions->where('is_current', true)->first(),
            ],
            'versions' => $versions,
            'total_versions' => $versions->count(),
        ]);
    }

    /**
     * Get activity timeline for a file
     */
    public function getActivityTimeline(File $file)
    {
        // Check if user has access to this file
        if (!$this->userCanAccessFile($file)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $activities = FileActivity::getTimelineForFile($file->id, 50);

        $formattedActivities = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'action' => $activity->action,
                'formatted_action' => $activity->formatted_action,
                'action_icon' => $activity->action_icon,
                'detail_description' => $activity->detail_description,
                'time_since' => $activity->time_since,
                'created_at' => $activity->created_at,
                'user' => [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                    'email' => $activity->user->email,
                ],
            ];
        });

        return response()->json([
            'activities' => $formattedActivities,
            'total_count' => $activities->count(),
        ]);
    }

    /**
     * Create a new version of a file
     */
    public function createVersion(Request $request, File $file)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
            'comment' => 'nullable|string|max:500'
        ]);

        // Check if user has edit access to this file
        if (!$this->userCanEditFile($file)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $uploadedFile = $request->file('file');
        $comment = $request->input('comment');

        try {
            DB::beginTransaction();

            // Store the new file version
            $versionPath = $this->storeFileVersion($file, $uploadedFile);
            
            // Create version record
            $version = FileVersion::createFromUpload($file, $uploadedFile, Auth::id(), $comment);
            
            // Update the main file record
            $file->update([
                'file_path' => $versionPath,
                'file_size' => $uploadedFile->getSize(),
                'file_type' => $uploadedFile->getClientOriginalExtension(),
                'mime_type' => $uploadedFile->getMimeType(),
                'updated_at' => now(),
            ]);

            // Update version with correct path
            $version->update(['file_path' => $versionPath]);

            // Log activity
            FileActivity::log($file->id, Auth::id(), FileActivity::ACTION_VERSION_CREATED, [
                'version_number' => $version->version_number,
                'file_size' => $uploadedFile->getSize(),
                'comment' => $comment
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'New version created successfully',
                'version' => [
                    'id' => $version->id,
                    'version_number' => $version->version_number,
                    'version_label' => $version->version_label,
                    'file_size' => $version->file_size,
                    'formatted_size' => $version->formatted_size,
                    'created_at' => $version->created_at,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'error' => 'Failed to create new version',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a specific version
     */
    public function restoreVersion(File $file, FileVersion $version)
    {
        // Check if user has edit access to this file
        if (!$this->userCanEditFile($file)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Verify version belongs to this file
        if ($version->file_id !== $file->id) {
            return response()->json(['error' => 'Version does not belong to this file'], 400);
        }

        if (!$version->canRestore()) {
            return response()->json(['error' => 'This version cannot be restored'], 400);
        }

        try {
            DB::beginTransaction();

            // Restore the version
            $version->restore();

            // Log activity
            FileActivity::log($file->id, Auth::id(), FileActivity::ACTION_VERSION_RESTORED, [
                'version_number' => $version->version_number,
                'restored_from_version' => $version->version_number
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Version {$version->version_label} restored successfully",
                'current_version' => $version->version_number
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'error' => 'Failed to restore version',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a specific version
     */
    public function downloadVersion(File $file, FileVersion $version)
    {
        // Check if user has access to this file
        if (!$this->userCanAccessFile($file)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Verify version belongs to this file
        if ($version->file_id !== $file->id) {
            return response()->json(['error' => 'Version does not belong to this file'], 400);
        }

        // Log download activity
        FileActivity::log($file->id, Auth::id(), FileActivity::ACTION_DOWNLOADED, [
            'version_number' => $version->version_number
        ]);

        // Get download URL from Supabase
        try {
            $supabaseUrl = env('SUPABASE_URL');
            $supabaseKey = env('SUPABASE_SERVICE_KEY');
            
            if (!$supabaseUrl || !$supabaseKey) {
                throw new \Exception('Supabase configuration missing');
            }

            $client = new Client(['base_uri' => $supabaseUrl]);
            
            $response = $client->get("/storage/v1/object/sign/docs/{$version->file_path}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => ['expiresIn' => 3600] // 1 hour
            ]);

            $data = json_decode($response->getBody(), true);
            
            if (isset($data['signedURL'])) {
                return response()->json([
                    'download_url' => $supabaseUrl . $data['signedURL'],
                    'file_name' => $version->file_name,
                    'version_label' => $version->version_label
                ]);
            } else {
                throw new \Exception('Failed to generate download URL');
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate download URL',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific version (but keep current version)
     */
    public function deleteVersion(File $file, FileVersion $version)
    {
        // Check if user owns this file
        if ($file->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Verify version belongs to this file
        if ($version->file_id !== $file->id) {
            return response()->json(['error' => 'Version does not belong to this file'], 400);
        }

        // Can't delete the current version
        if ($version->is_current) {
            return response()->json(['error' => 'Cannot delete the current version'], 400);
        }

        try {
            DB::beginTransaction();

            // Delete file from storage (optional - you might want to keep for recovery)
            $this->deleteVersionFromStorage($version);

            // Delete version record
            $version->delete();

            // Log activity
            FileActivity::log($file->id, Auth::id(), 'version_deleted', [
                'version_number' => $version->version_number
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Version {$version->version_label} deleted successfully"
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete version',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new file version in Supabase storage
     */
    private function storeFileVersion(File $file, $uploadedFile)
    {
        $userId = Auth::id();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = $uploadedFile->getClientOriginalName();
        
        // Generate unique path for version
        $versionPath = "user_{$userId}/versions/{$file->id}/{$timestamp}_{$fileName}";

        // Upload to Supabase
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = env('SUPABASE_SERVICE_KEY');

        $client = new Client(['base_uri' => $supabaseUrl]);
        
        $response = $client->post("/storage/v1/object/docs/{$versionPath}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $supabaseKey,
                'Content-Type' => $uploadedFile->getMimeType(),
            ],
            'body' => file_get_contents($uploadedFile->getPathname())
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to upload file version to storage');
        }

        return $versionPath;
    }

    /**
     * Delete a version from storage
     */
    private function deleteVersionFromStorage(FileVersion $version)
    {
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = env('SUPABASE_SERVICE_KEY');

        try {
            $client = new Client(['base_uri' => $supabaseUrl]);
            
            $client->delete("/storage/v1/object/docs/{$version->file_path}", [
                'headers' => ['Authorization' => 'Bearer ' . $supabaseKey]
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the operation
            \Log::warning("Failed to delete version from storage: " . $e->getMessage());
        }
    }

    /**
     * Check if user can access file
     */
    private function userCanAccessFile(File $file)
    {
        $userId = Auth::id();
        
        // Owner can always access
        if ($file->user_id === $userId) {
            return true;
        }

        // Check if file is shared with user
        return $file->shares()->where('shared_with_id', $userId)->exists();
    }

    /**
     * Check if user can edit file
     */
    private function userCanEditFile(File $file)
    {
        $userId = Auth::id();
        
        // Owner can always edit
        if ($file->user_id === $userId) {
            return true;
        }

        // Check if user has edit permission through sharing
        return $file->shares()
                   ->where('shared_with_id', $userId)
                   ->where('role', 'editor')
                   ->exists();
    }
}
