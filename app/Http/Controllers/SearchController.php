<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SearchController extends Controller
{
    /**
     * Perform advanced search on files
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        $query = $request->input('q', '');
        $filters = $request->only([
            'type', 'size_min', 'size_max', 'date_from', 'date_to', 
            'owner', 'shared', 'folder_id', 'sort_by', 'sort_order'
        ]);

        // Start with user's files (sharing removed)
        $filesQuery = $this->buildSearchQuery($user, $query, $filters);

        // Execute search with pagination
        $files = $filesQuery->paginate(20);

        // Add search suggestions if query is provided
        $suggestions = $query ? $this->getSearchSuggestions($user, $query) : [];


        return response()->json([
            'files' => $files->items(),
            'pagination' => [
                'current_page' => $files->currentPage(),
                'last_page' => $files->lastPage(),
                'per_page' => $files->perPage(),
                'total' => $files->total(),
            ],
            'suggestions' => $suggestions,
            'query' => $query,
            'filters' => $filters
        ]);
    }

    /**
     * Build the search query based on criteria
     */
    private function buildSearchQuery($user, $query, $filters)
    {
        $filesQuery = File::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at'); // Exclude soft-deleted files

        // Text search in file names and content (if indexed)
        if ($query) {
            $filesQuery->where(function($q) use ($query) {
                $q->whereRaw('LOWER(file_name) LIKE ?', ['%' . strtolower($query) . '%'])
                  ->orWhereRaw('LOWER(file_type) LIKE ?', ['%' . strtolower($query) . '%'])
                  ->orWhereRaw('LOWER(mime_type) LIKE ?', ['%' . strtolower($query) . '%']);
            });
        }

        // File type filter
        if (!empty($filters['type'])) {
            $type = $filters['type'];
            switch ($type) {
                case 'images':
                    $filesQuery->where('mime_type', 'LIKE', 'image/%');
                    break;
                case 'documents':
                    $filesQuery->whereIn('file_type', ['pdf', 'doc', 'docx', 'txt', 'rtf']);
                    break;
                case 'spreadsheets':
                    $filesQuery->whereIn('file_type', ['xls', 'xlsx', 'csv']);
                    break;
                case 'presentations':
                    $filesQuery->whereIn('file_type', ['ppt', 'pptx']);
                    break;
                case 'videos':
                    $filesQuery->where('mime_type', 'LIKE', 'video/%');
                    break;
                case 'audio':
                    $filesQuery->where('mime_type', 'LIKE', 'audio/%');
                    break;
                case 'folders':
                    $filesQuery->where('is_folder', true);
                    break;
                case 'files':
                    $filesQuery->where('is_folder', false);
                    break;
                default:
                    $filesQuery->where('file_type', $type);
            }
        }

        // File size filters
        if (!empty($filters['size_min'])) {
            $filesQuery->where('file_size', '>=', (int)$filters['size_min']);
        }
        if (!empty($filters['size_max'])) {
            $filesQuery->where('file_size', '<=', (int)$filters['size_max']);
        }

        // Date range filters
        if (!empty($filters['date_from'])) {
            $filesQuery->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }
        if (!empty($filters['date_to'])) {
            $filesQuery->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        // Folder filter
        if (!empty($filters['folder_id'])) {
            $filesQuery->where('parent_id', $filters['folder_id']);
        }

        // Shared files filter removed (feature deprecated)

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'updated_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        $validSortFields = ['file_name', 'file_size', 'created_at', 'updated_at', 'file_type'];
        if (in_array($sortBy, $validSortFields)) {
            $filesQuery->orderBy($sortBy, $sortOrder);
        } else {
            $filesQuery->orderBy('updated_at', 'desc');
        }

        // Include related data
        $filesQuery->with(['user:id,name,email']);

        return $filesQuery;
    }

    /**
     * Get search suggestions based on user's files and search history
     */
    private function getSearchSuggestions($user, $query)
    {
        $suggestions = [];

        // File type suggestions
        $fileTypes = File::where('user_id', $user->id)
            ->whereRaw('LOWER(file_type) LIKE ?', ['%' . strtolower($query) . '%'])
            ->distinct()
            ->pluck('file_type')
            ->take(3);

        foreach ($fileTypes as $type) {
            $suggestions[] = [
                'type' => 'filetype',
                'text' => "type:{$type}",
                'label' => "Files of type {$type}"
            ];
        }

        // Recent file name suggestions
        $recentFiles = File::where('user_id', $user->id)
            ->whereRaw('LOWER(file_name) LIKE ?', ['%' . strtolower($query) . '%'])
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->pluck('file_name');

        foreach ($recentFiles as $fileName) {
            $suggestions[] = [
                'type' => 'filename',
                'text' => $fileName,
                'label' => $fileName
            ];
        }

        return $suggestions;
    }


    /**
     * Get search filters and their counts
     */
    public function getSearchFilters(Request $request)
    {
        $user = Auth::user();
        
        $stats = [
            'total_files' => File::where('user_id', $user->id)->where('is_folder', false)->count(),
            'total_folders' => File::where('user_id', $user->id)->where('is_folder', true)->count(),
            'file_types' => File::where('user_id', $user->id)
                ->where('is_folder', false)
                ->groupBy('file_type')
                ->selectRaw('file_type, count(*) as count')
                ->orderBy('count', 'desc')
                ->get(),
            'size_stats' => [
                'total_size' => File::where('user_id', $user->id)->sum('file_size'),
                'avg_size' => File::where('user_id', $user->id)->where('is_folder', false)->avg('file_size'),
            ],
            'recent_activity' => File::where('user_id', $user->id)
                ->orderBy('updated_at', 'desc')
                ->take(5)
                ->get(['file_name', 'file_type', 'updated_at'])
        ];

        return response()->json($stats);
    }



}
