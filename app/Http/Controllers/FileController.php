<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Added
use Illuminate\Support\Facades\Http;  // Added
use Illuminate\Support\Facades\Log;   // Added
use App\Models\File; // Assuming you have this for ::create
// If Illuminate\Support\Facades\Storage was used by other methods, keep it.
// Otherwise, it can be removed if only store method used it and no longer does.

class FileController extends Controller
{
    /**
     * Store file metadata, then send it to an n8n webhook.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user(); // Get authenticated user

        Log::info('File store request received', [
            'user_id' => $user->id,
            'request_data' => $request->all()
        ]);
        
        $validated = $request->validate([
            'file_name' => 'required|string',
            'file_path' => 'required|string', 
            'file_size' => 'required|integer',
            'file_type' => 'required|string',
            'mime_type' => 'required|string'
        ]);

        try {
            // 1. Create the file record in the database first.
            Log::info('Creating file record in DB', ['validated_data' => $validated, 'user_id' => $user->id]);
            $file = $user->files()->create($validated); 
            Log::info('File record created successfully', ['file_id' => $file->id, 'user_id' => $user->id]);
            
            // 2. After successful creation, send metadata to n8n webhook.
            //$n8nWebhookUrl = 'https://securedocs.app.n8n.cloud/webhook/f106ab40-0651-4e2c-acc1-6591ab771828';
            $n8nWebhookUrl = 'https://securedocs.app.n8n.cloud/webhook-test/f106ab40-0651-4e2c-acc1-6591ab771828';
            
            try {
                $response = Http::post($n8nWebhookUrl, $file->toArray());

                if ($response->successful()) {
                    Log::info('File metadata successfully sent to n8n.', ['file_id' => $file->id]);
                } else {
                    // Log the error but don't fail the main request.
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
            
            // 3. Return the successful response to the client.
            return response()->json($file, 201);

        } catch (\Exception $e) {
            // This will now only catch errors from the database insertion itself.
            Log::error('File save error to DB: '.$e->getMessage(), [
                'user_id' => $user->id,
                'exception_details' => $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine(),
                'validated_data' => $validated ?? null
            ]);
            return response()->json(['error' => 'Server error while saving file metadata: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get all files for the authenticated user
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            // Log the request
            Log::info('File index request received', [ // Changed \Log to Log
                'user_id' => auth()->id(),
                'search_query' => $request->q ?? null
            ]);
            
            $query = auth()->user()->files();
            
            // Search by file name if 'q' is provided
            if ($request->has('q') && trim($request->q) !== '') {
                $q = $request->q;
                $query->where('file_name', 'ILIKE', "$q%");
            }
            
            // Sort by file_name, then most recent
            $query->orderBy('file_name')->orderByDesc('created_at');
            
            // Pagination: 20 per page
            $files = $query->paginate(20);
            
            // Log success
            Log::info('Files retrieved successfully', [ // Changed \Log to Log
                'user_id' => auth()->id(),
                'count' => count($files->items())
            ]);
            
            return response()->json([
                'status' => 'success',
                'files' => $files->items(),
                'total' => $files->total(),
                'current_page' => $files->currentPage(),
                'last_page' => $files->lastPage(),
            ]);
        } catch (\Exception $e) {
            // Detailed error logging
            Log::error('Error fetching files', [ // Changed \Log to Log
                'user_id' => auth()->id() ?? 'unauthenticated',
                'exception_details' => $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine(),
                // 'trace' => $e->getTraceAsString() // Removed for simplicity, can be re-added if needed
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve files',
                'error' => $e->getMessage() // Provide generic error to client for security
            ], 500);
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