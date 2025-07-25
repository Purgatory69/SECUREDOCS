<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\File;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB; // Added for DB facade
use PDO; // Added for PDO constants

// If Illuminate\Support\Facades\Storage was used by other methods, keep it.
// Otherwise, it can be removed if only store method used it and no longer does.

class FileController extends Controller
{
    /**
     * Store file metadata, then send it to an n8n webhook.
     * Also handles folder creation if is_folder is true.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        Log::info('File/Folder store request received', [
            'user_id' => $user->id,
            'request_data' => $request->all()
        ]);

        $validated = $request->validate([
            'file_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'parent_id' => 'nullable|exists:files,id,user_id,' . $user->id . ',is_folder,true', // Parent must be a folder owned by the user
            'is_folder' => 'required|boolean',
            // File specific validations - only required if not a folder
            'file_path' => Rule::requiredIf(!$request->boolean('is_folder')) . '|string',
            'file_size' => Rule::requiredIf(!$request->boolean('is_folder')) . '|integer|max:102400', // Max 100MB
            'file_type' => Rule::requiredIf(!$request->boolean('is_folder')) . '|string',
            'mime_type' => Rule::requiredIf(!$request->boolean('is_folder')) . '|string|in:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/plain',
        ]);

        try {
            $fileData = [
                'user_id' => $user->id,
                'file_name' => $validated['file_name'],
                'parent_id' => $validated['parent_id'] ?? null,
                // Workaround: Consistently use string 'true'/'false' for boolean
                'is_folder' => $validated['is_folder'] ? 'true' : 'false',
            ];

            // --- START: Duplicate Folder Name Handling ---
            if ($validated['is_folder']) {
                $baseName = $validated['file_name'];
                $parentId = $validated['parent_id'] ?? null;
                $newName = $baseName;
                $copyIndex = 1;

                // Check for existing folders with the same name in the same parent
                while (File::where('file_name', $newName)
                        ->where('parent_id', $parentId)
                        ->where('user_id', $user->id) // Ensure check is scoped to the user
                        ->whereRaw('is_folder IS TRUE') // Use whereRaw for unambiguous boolean check
                        ->exists()) {
                    $newName = $baseName . ' COPY(' . $copyIndex . ')';
                    $copyIndex++;
                }
                // Update the name in our data array
                $fileData['file_name'] = $newName;
            }
            // --- END: Duplicate Folder Name Handling ---

            if (!$validated['is_folder']) {
                // For files, add file-specific attributes
                $fileData['file_path'] = $validated['file_path'];
                $fileData['file_size'] = $validated['file_size'];
                $fileData['file_type'] = $validated['file_type'];
                $fileData['mime_type'] = $validated['mime_type'];
            } else {
                // For folders, construct path with potentially new name
                $parentPath = $validated['parent_id'] ? (File::find($validated['parent_id'])->file_path) : ('user_' . $user->id);
                $fileData['file_path'] = $parentPath . '/' . $fileData['file_name'];
                $fileData['mime_type'] = 'inode/directory'; // Common practice for representing folders
            }
            
            Log::info('Creating file/folder record in DB', ['data' => $fileData, 'user_id' => $user->id]);
            $file = File::create($fileData);
            Log::info('File/Folder record created successfully', ['id' => $file->id, 'user_id' => $user->id, 'is_folder' => $file->is_folder]);

            // After successful creation, find the file to return it
            $newFile = DB::table('files')->where('user_id', $user->id)->where('parent_id', $fileData['parent_id'])->where('file_name', $fileData['file_name'])->first();

            Log::info('File/Folder created successfully via DB facade', ['file' => $newFile, 'user_id' => $user->id]);

            // Only send to n8n if it's a file, not a folder
            if (!$newFile->is_folder) {
                // Re-implementing synchronous n8n call
                try {
                    $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
                    $bucketName = env('SUPABASE_BUCKET_PUBLIC');
                    $publicUrl = "{$supabaseUrl}/storage/v1/object/public/{$bucketName}/{$newFile->file_path}";

                    $webhookUrl = config('services.n8n.webhook_url');

                    if ($webhookUrl) {
                        Http::post($webhookUrl, [
                            'file_id' => $newFile->id,
                            'file_name' => $newFile->file_name,
                            'file_path' => $newFile->file_path,
                            'mime_type' => $newFile->mime_type,
                            'public_url' => $publicUrl,
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                        ]);
                        Log::info('Successfully sent file to n8n webhook.', ['file_id' => $newFile->id]);
                    } else {
                        Log::warning('N8N_WEBHOOK_URL is not configured. Skipping webhook call.');
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send file to n8n webhook.', ['file_id' => $newFile->id, 'error' => $e->getMessage()]);
                }
            }

            return response()->json($newFile, 201);

        } catch (\Exception $e) {
            Log::error('File/Folder save error to DB: '.$e->getMessage(), [
                'user_id' => $user->id,
                'exception_details' => $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine(),
                'validated_data' => $validated ?? null
            ]);
            return response()->json(['error' => 'Server error while saving metadata: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get all files and folders for the authenticated user, optionally filtered by parent_id.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        try {
            $parentId = $request->input('parent_id');

            Log::info('File/Folder index request received', [
                'user_id' => $user->id,
                'search_query' => $request->q ?? null,
                'parent_id' => $parentId
            ]);
            
            $query = $user->files();

            if ($parentId) {
                // Ensure parent_id exists and belongs to the user and is a folder
                $parentFolder = File::where('id', $parentId)
                                    ->where('user_id', $user->id)
                                    ->where('is_folder', 'true') // Workaround: Use string 'true'
                                    ->first();
                if (!$parentFolder) {
                    return response()->json(['error' => 'Parent folder not found or invalid.'], 404);
                }
                $query->where('parent_id', $parentId);
            } else {
                // Root level items
                $query->whereNull('parent_id');
            }
            
            // Search by file name if 'q' is provided
            if ($request->has('q') && trim($request->q) !== '') {
                $q = $request->q;
                // Search only within the current level (parent_id or root)
                $query->where('file_name', 'ILIKE', "$q%");
            }
            
            // Sort by is_folder descending (folders first), then file_name, then most recent
            $query->orderByDesc('is_folder')->orderBy('file_name')->orderByDesc('created_at');
            
            // Manual pagination to potentially avoid prepared statement issues with paginate()
            $perPage = 20;
            $currentPage = $request->input('page', 1);

            // Clone the query for counting to avoid modification issues
            $countQuery = $query->clone()->toBase();
            $total = $countQuery->getCountForPagination();

            $itemsResult = $query->forPage($currentPage, $perPage)->get();
            
            Log::info('Files/Folders retrieved successfully', [
                'user_id' => $user->id,
                'count' => $itemsResult->count() 
            ]);
            
            return response()->json([
                'status' => 'success',
                'files' => $itemsResult->values()->all(), // Ensure it's a plain array
                'total' => $total,
                'current_page' => (int)$currentPage,
                'last_page' => ceil($total / $perPage),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching files/folders', [
                'user_id' => $user->id ?? 'unauthenticated',
                'exception_details' => $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new folder.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createFolder(Request $request)
    {
        $user = Auth::user();

        try {
            // Diagnostic logging for PDO::ATTR_EMULATE_PREPARES
            $emulatePrepares = DB::connection()->getPdo()->getAttribute(PDO::ATTR_EMULATE_PREPARES);
            Log::info('PDO::ATTR_EMULATE_PREPARES value: ' . ($emulatePrepares ? 'true' : 'false'), ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('Error getting PDO::ATTR_EMULATE_PREPARES: ' . $e->getMessage(), ['user_id' => $user->id]);
        }

        Log::info('Create folder request received', [
            'user_id' => $user->id,
            'request_data' => $request->all()
        ]);

        $validated = $request->validate([
            'folder_name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                Rule::exists('files', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)->where('is_folder', true);
                }),
            ],
        ]);

        try {
            // --- START: Duplicate Folder Name Handling ---
            $baseName = $validated['folder_name'];
            $parentId = $validated['parent_id'] ?? null;
            $newName = $baseName;
            $counter = 1;

            // Check for existing folders with the same name in the same parent
            while (File::where('file_name', $newName)
                        ->where('parent_id', $parentId)
                        ->where('user_id', $user->id)
                        ->whereRaw('is_folder IS TRUE') // Use whereRaw for unambiguous boolean check
                        ->exists()) {
                $counter++;
                $newName = $baseName . ' COPY(' . $counter . ')';
            }
            // --- END: Duplicate Folder Name Handling ---

            $parentFolderPath = '';
            if ($validated['parent_id']) {
                $parentFolder = File::find($validated['parent_id']);
                if ($parentFolder) { // Should always exist due to validation rule
                    $parentFolderPath = $parentFolder->file_path;
                }
            }

            $folderData = [
                'user_id' => $user->id,
                'file_name' => $newName, // Use the new unique name
                'parent_id' => $validated['parent_id'] ?? null,
                'is_folder' => 'true', // Workaround: Use string 'true' for boolean
                'file_path' => $parentFolderPath ? ($parentFolderPath . '/' . $newName) : $newName, // Construct path with new name
                'mime_type' => 'inode/directory',
                // file_size, file_type can be null for folders
            ];

            // Diagnostic logging for is_folder type
            Log::info('is_folder type before create (after workaround): ' . gettype($folderData['is_folder']), [
                'user_id' => $user->id,
                'is_folder_value' => $folderData['is_folder']
            ]);

            Log::info('Creating folder record in DB', ['data' => $folderData, 'user_id' => $user->id]);
            $folder = File::create($folderData);
            Log::info('Folder record created successfully', ['id' => $folder->id, 'user_id' => $user->id]);

            return response()->json($folder, 201);

        } catch (\Exception $e) {
            Log::error('Folder creation error: '.$e->getMessage(), [
                'user_id' => $user->id,
                'exception_details' => $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine(),
                'validated_data' => $validated ?? null
            ]);
            return response()->json(['error' => 'Server error while creating folder: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get a single file by ID
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $file = auth()->user()->files()->findOrFail($id);
        return response()->json($file);
    }

    /**
     * Delete a file
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $file = auth()->user()->files()->findOrFail($id);
        $file->delete();

        return response()->json([
            'success' => true
        ]);
    }
}
