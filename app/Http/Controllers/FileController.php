<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\File;
use Illuminate\Validation\Rule;

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
            'file_name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:files,id,user_id,' . $user->id . ',is_folder,true', // Parent must be a folder owned by the user
            'is_folder' => 'required|boolean',
            // File specific validations - only required if not a folder
            'file_path' => Rule::requiredIf(!$request->boolean('is_folder')) . '|string',
            'file_size' => Rule::requiredIf(!$request->boolean('is_folder')) . '|integer',
            'file_type' => Rule::requiredIf(!$request->boolean('is_folder')) . '|string',
            'mime_type' => Rule::requiredIf(!$request->boolean('is_folder')) . '|string',
        ]);

        try {
            $fileData = [
                'user_id' => $user->id,
                'file_name' => $validated['file_name'],
                'parent_id' => $validated['parent_id'] ?? null,
                'is_folder' => $validated['is_folder'],
            ];

            if (!$validated['is_folder']) {
                // For files, add file-specific attributes
                $fileData['file_path'] = $validated['file_path'];
                $fileData['file_size'] = $validated['file_size'];
                $fileData['file_type'] = $validated['file_type'];
                $fileData['mime_type'] = $validated['mime_type'];
            } else {
                // For folders, set specific values or nulls
                $fileData['file_path'] = $validated['parent_id'] ? (File::find($validated['parent_id'])->file_path . '/' . $validated['file_name']) : $validated['file_name'];
                $fileData['mime_type'] = 'inode/directory'; // Common practice for representing folders
                // file_size and file_type can be null for folders
            }
            
            Log::info('Creating file/folder record in DB', ['data' => $fileData, 'user_id' => $user->id]);
            $file = File::create($fileData);
            Log::info('File/Folder record created successfully', ['id' => $file->id, 'user_id' => $user->id, 'is_folder' => $file->is_folder]);

            // Only send to n8n if it's a file, not a folder
            if (!$file->is_folder) {
                $n8nWebhookUrl = 'http://localhost:5678/webhook-test/f106ab40-0651-4e2c-acc1-6591ab771828';
                try {
                    $response = Http::post($n8nWebhookUrl, $file->toArray());
                    if ($response->successful()) {
                        Log::info('File metadata successfully sent to n8n.', ['file_id' => $file->id]);
                    } else {
                        Log::error('Failed to send file metadata to n8n.', [
                            'file_id' => $file->id,
                            'status' => $response->status(),
                            'body' => $response->body()
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Exception while sending metadata to n8n.', [
                        'file_id' => $file->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return response()->json($file, 201);

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
                                    ->where('is_folder', true)
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
            
            // Pagination: 20 per page
            $items = $query->paginate(20); // Changed $files to $items
            
            Log::info('Files/Folders retrieved successfully', [
                'user_id' => $user->id,
                'count' => count($items->items())
            ]);
            
            return response()->json([
                'status' => 'success',
                'files' => $items->items(), // Keep 'files' key for frontend compatibility for now
                'total' => $items->total(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
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
            $parentFolderPath = '';
            if ($validated['parent_id']) {
                $parentFolder = File::find($validated['parent_id']);
                if ($parentFolder) { // Should always exist due to validation rule
                    $parentFolderPath = $parentFolder->file_path;
                }
            }

            $folderData = [
                'user_id' => $user->id,
                'file_name' => $validated['folder_name'],
                'parent_id' => $validated['parent_id'] ?? null,
                'is_folder' => true,
                'file_path' => $parentFolderPath ? ($parentFolderPath . '/' . $validated['folder_name']) : $validated['folder_name'], // Construct path
                'mime_type' => 'inode/directory',
                // file_size, file_type can be null for folders
            ];

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