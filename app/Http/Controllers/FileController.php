<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\File;

class FileController extends Controller
{
    /**
     * Store file metadata in the database after Supabase upload
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Log the incoming request data
        \Log::info('File store request received', [
            'user_id' => auth()->id(),
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
            // Log before creating file
            \Log::info('Creating file record', ['validated_data' => $validated]);
            
            $file = auth()->user()->files()->create($validated);
            
            // Log success
            \Log::info('File record created successfully', ['file_id' => $file->id]);
            
            return response()->json($file, 201);
        } catch (\Exception $e) {
            \Log::error('File save error: '.$e->getMessage(), [
                'exception' => $e,
                'validated_data' => $validated ?? null
            ]);
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
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
            return response()->json([
                'status' => 'success',
                'files' => $files->items(),
                'total' => $files->total(),
                'current_page' => $files->currentPage(),
                'last_page' => $files->lastPage(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching files: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve files',
                'error' => $e->getMessage()
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
