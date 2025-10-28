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
            'owner', 'shared', 'folder_id', 'sort_by', 'sort_order',
            'match_type', 'case_sensitive', 'whole_word', 'view_context'
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
            ->where('user_id', $user->id);

        // Handle view context for tab-specific search
        $viewContext = $filters['view_context'] ?? 'main';
        switch ($viewContext) {
            case 'trash':
                $filesQuery->onlyTrashed();
                break;
            case 'shared':
                // For shared files, we'll use the shared_file_copies table
                $filesQuery->join('shared_file_copies', 'files.id', '=', 'shared_file_copies.copied_file_id')
                          ->where('shared_file_copies.copied_by_user_id', $user->id)
                          ->whereNull('deleted_at');
                break;
            case 'blockchain':
                $filesQuery->whereNull('deleted_at')
                          ->where(function($q) {
                              $q->where('is_arweave', DB::raw('true'))
                                ->orWhere('file_path', 'like', 'ipfs://%');
                          });
                break;
            case 'main':
            default:
                $filesQuery->whereNull('deleted_at')
                          ->where(function($q) {
                              $q->whereNull('file_path')
                                ->orWhere('file_path', 'not like', 'ipfs://%');
                          });
                break;
        }

        // Advanced text search with match options
        if ($query) {
            $matchType = $filters['match_type'] ?? 'contains';
            $caseSensitive = $filters['case_sensitive'] ?? 'insensitive';
            $wholeWord = $filters['whole_word'] ?? false;

            $filesQuery->where(function($q) use ($query, $matchType, $caseSensitive, $wholeWord) {
                if ($caseSensitive === 'sensitive') {
                    // Case-sensitive search
                    switch ($matchType) {
                        case 'exact':
                            $q->where('file_name', '=', $query);
                            break;
                        case 'starts_with':
                            $q->where('file_name', 'LIKE BINARY', $query . '%');
                            break;
                        case 'ends_with':
                            $q->where('file_name', 'LIKE BINARY', '%' . $query);
                            break;
                        case 'contains':
                        default:
                            if ($wholeWord) {
                                $q->where('file_name', 'REGEXP', '[[:<:]]' . preg_quote($query, '/') . '[[:>:]]');
                            } else {
                                $q->where('file_name', 'LIKE BINARY', '%' . $query . '%');
                            }
                            break;
                    }
                } else {
                    // Case-insensitive search (default)
                    switch ($matchType) {
                        case 'exact':
                            $q->whereRaw('LOWER(file_name) = ?', [strtolower($query)]);
                            break;
                        case 'starts_with':
                            $q->where('file_name', 'ILIKE', $query . '%');
                            break;
                        case 'ends_with':
                            $q->where('file_name', 'ILIKE', '%' . $query);
                            break;
                        case 'contains':
                        default:
                            if ($wholeWord) {
                                $q->whereRaw('file_name ~* ?', ['\\m' . preg_quote($query, '/') . '\\M']);
                            } else {
                                $q->where('file_name', 'ILIKE', '%' . $query . '%')
                                  ->orWhere('file_type', 'ILIKE', '%' . $query . '%')
                                  ->orWhere('mime_type', 'ILIKE', '%' . $query . '%');
                            }
                            break;
                    }
                }
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
                    $filesQuery->where('is_folder', DB::raw('true'));
                    break;
                case 'files':
                    $filesQuery->where('is_folder', DB::raw('false'));
                    break;
                default:
                    $filesQuery->where('file_type', $type);
            }
        }

        // File size filters (convert MB to bytes and cast varchar to numeric)
        if (!empty($filters['size_min'])) {
            $sizeMinBytes = (float)$filters['size_min'] * 1024 * 1024;
            $filesQuery->whereRaw('CAST(file_size AS BIGINT) >= ?', [$sizeMinBytes]);
        }
        if (!empty($filters['size_max'])) {
            $sizeMaxBytes = min((float)$filters['size_max'], 100) * 1024 * 1024; // Cap at 100MB
            $filesQuery->whereRaw('CAST(file_size AS BIGINT) <= ?', [$sizeMaxBytes]);
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
            'total_files' => File::where('user_id', $user->id)->where('is_folder', DB::raw('false'))->count(),
            'total_folders' => File::where('user_id', $user->id)->where('is_folder', DB::raw('true'))->count(),
            'file_types' => File::where('user_id', $user->id)
                ->where('is_folder', DB::raw('false'))
                ->groupBy('file_type')
                ->selectRaw('file_type, count(*) as count')
                ->orderBy('count', 'desc')
                ->get(),
            'size_stats' => [
                'total_size' => File::where('user_id', $user->id)
                    ->whereRaw('file_size IS NOT NULL AND file_size != \'\'')
                    ->selectRaw('SUM(CAST(file_size AS BIGINT))')
                    ->value('sum') ?? 0,
                'avg_size' => File::where('user_id', $user->id)
                    ->where('is_folder', DB::raw('false'))
                    ->whereRaw('file_size IS NOT NULL AND file_size != \'\'')
                    ->selectRaw('AVG(CAST(file_size AS BIGINT))')
                    ->value('avg') ?? 0,
            ],
            'recent_activity' => File::where('user_id', $user->id)
                ->orderBy('updated_at', 'desc')
                ->take(5)
                ->get(['file_name', 'file_type', 'updated_at'])
        ];

        return response()->json($stats);
    }



}
