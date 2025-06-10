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
     * Store file metadata in the database after Supabase upload
     * and send to n8n if user is premium.
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

        // --- n8n Webhook Logic for Premium Users ---
        if ($user && $user->is_premium) {
            Log::info('Premium user detected. Attempting to process file for n8n.', [
                'user_id' => $user->id,
                'file_name' => $validated['file_name'],
                'supabase_path' => $validated['file_path']
            ]);

            $webhookUrl = 'https://securedocs.app.n8n.cloud/webhook/5ee6b883-8b4f-4914-8253-b68c5cc77d4a'; // Your n8n webhook URL

            try {
                // --- Construct the full Supabase URL ---
                $supabaseBaseUrl = rtrim(env('SUPABASE_URL'), '/');
                $supabaseBucket = env('SUPABASE_BUCKET_PUBLIC');
                $filePath = $validated['file_path'];

                if (!$supabaseBaseUrl || !$supabaseBucket) {
                    Log::error('Supabase URL or Bucket not configured in .env for n8n processing.', [
                        'user_id' => $user->id,
                        'file_name' => $validated['file_name']
                    ]);
                    // Skip n8n processing if config is missing
                    throw new \Exception('Supabase environment variables not set.'); 
                }

                $fullSupabaseUrl = "{$supabaseBaseUrl}/storage/v1/object/public/{$supabaseBucket}/{$filePath}";
                Log::info('Constructed Supabase download URL for n8n', ['url' => $fullSupabaseUrl, 'user_id' => $user->id]);
                // --- End of URL construction ---

                // 1. Download the file from Supabase using the full public URL
                $fileContentsResponse = Http::timeout(60)->get($fullSupabaseUrl);

                if ($fileContentsResponse->successful()) {
                    $fileContents = $fileContentsResponse->body();
                    
                    // 2. Send the downloaded file content to n8n
                    $n8nResponse = Http::timeout(30)
                        ->attach(
                            'data', // Field name n8n expects
                            $fileContents,
                            $validated['file_name'] // Use the original file name
                        )->post($webhookUrl);

                    if ($n8nResponse->successful()) {
                        Log::info('File successfully sent to n8n webhook.', [
                            'user_id' => $user->id, 
                            'file_name' => $validated['file_name']
                        ]);
                    } else {
                        Log::error('Failed to send file to n8n webhook.', [
                            'user_id' => $user->id,
                            'file_name' => $validated['file_name'],
                            'n8n_status' => $n8nResponse->status(),
                            'n8n_body' => $n8nResponse->body()
                        ]);
                    }
                } else {
                    Log::error('Failed to download file from Supabase for n8n processing.', [
                        'user_id' => $user->id,
                        'file_name' => $validated['file_name'],
                        'supabase_path' => $validated['file_path'],
                        'supabase_status' => $fileContentsResponse->status()
                    ]);
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('ConnectionException during n8n/Supabase processing.', [
                    'user_id' => $user->id,
                    'file_name' => $validated['file_name'],
                    'error' => $e->getMessage()
                ]);
            } catch (\Exception $e) {
                Log::error('General Exception during n8n/Supabase processing.', [
                    'user_id' => $user->id,
                    'file_name' => $validated['file_name'],
                    'error' => $e->getMessage()
                ]);
            }
        }
        // --- End of n8n Webhook Logic ---

        try {
            Log::info('Creating file record in DB', ['validated_data' => $validated, 'user_id' => $user->id]);
            
            $file = $user->files()->create($validated); 
            
            Log::info('File record created successfully', ['file_id' => $file->id, 'user_id' => $user->id]);
            
            return response()->json($file, 201);
        } catch (\Exception $e) {
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
                $query->where('file_name', 'ILIKE', "%$q%");
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