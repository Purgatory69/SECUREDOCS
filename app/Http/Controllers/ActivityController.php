<?php

namespace App\Http\Controllers;

use App\Models\SystemActivity;
use App\Models\UserSession;
use App\Models\SecurityEvent;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ActivityController extends Controller
{
    /**
     * Get user's recent activities
     */
    public function getUserActivities(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1',
                'activity_type' => 'sometimes|string',
                'action' => 'sometimes|string',
                'risk_level' => 'sometimes|string|in:low,medium,high,critical',
                'date_from' => 'sometimes|date',
                'date_to' => 'sometimes|date|after_or_equal:date_from',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $query = SystemActivity::where('user_id', Auth::id())
                ->with(['file', 'targetUser'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('activity_type')) {
                $query->byType($request->activity_type);
            }

            if ($request->has('action')) {
                $query->byAction($request->action);
            }

            if ($request->has('risk_level')) {
                $query->byRiskLevel($request->risk_level);
            }

            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
            }

            $limit = $request->get('limit', 20);
            $activities = $query->paginate($limit);

            return response()->json([
                'activities' => $activities->items(),
                'pagination' => [
                    'current_page' => $activities->currentPage(),
                    'last_page' => $activities->lastPage(),
                    'per_page' => $activities->perPage(),
                    'total' => $activities->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch activities'], 500);
        }
    }

    /**
     * Get file activity history
     */
    public function getFileActivities(Request $request, $fileId): JsonResponse
    {
        try {
            $file = File::findOrFail($fileId);

            // Check if user has access to this file
            if (!$this->userCanAccessFile($file, Auth::user())) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'limit' => 'sometimes|integer|min:1|max:100',
                'activity_type' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $query = SystemActivity::where('file_id', $fileId)
                ->with(['user', 'targetUser'])
                ->orderBy('created_at', 'desc');

            if ($request->has('activity_type')) {
                $query->byType($request->activity_type);
            }

            $limit = $request->get('limit', 50);
            $activities = $query->limit($limit)->get();

            return response()->json([
                'activities' => $activities,
                'file' => [
                    'id' => $file->id,
                    'name' => $file->file_name,
                    'is_folder' => $file->is_folder,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch file activities'], 500);
        }
    }

    /**
     * Get activity dashboard statistics
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            
            // Recent activity counts
            $stats = [
                'today' => SystemActivity::byUser($userId)->today()->count(),
                'this_week' => SystemActivity::byUser($userId)->thisWeek()->count(),
                'this_month' => SystemActivity::byUser($userId)->thisMonth()->count(),
                'total' => SystemActivity::byUser($userId)->count(),
            ];

            // Activity by type (last 30 days)
            $activityByType = SystemActivity::byUser($userId)
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('activity_type, COUNT(*) as count')
                ->groupBy('activity_type')
                ->pluck('count', 'activity_type')
                ->toArray();

            // Risk level distribution
            $riskDistribution = SystemActivity::byUser($userId)
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('risk_level, COUNT(*) as count')
                ->groupBy('risk_level')
                ->pluck('count', 'risk_level')
                ->toArray();

            // Recent security events
            $securityEvents = SecurityEvent::where('user_id', $userId)
                ->recent(168) // Last 7 days
                ->count();

            // Active sessions
            $activeSessions = UserSession::where('user_id', $userId)
                ->active()
                ->count();

            return response()->json([
                'activity_stats' => $stats,
                'activity_by_type' => $activityByType,
                'risk_distribution' => $riskDistribution,
                'security_events_count' => $securityEvents,
                'active_sessions' => $activeSessions,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch dashboard stats'], 500);
        }
    }

    /**
     * Get activity timeline for visualization
     */
    public function getActivityTimeline(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'days' => 'sometimes|integer|min:1|max:365',
                'group_by' => 'sometimes|string|in:hour,day,week',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $days = $request->get('days', 30);
            $groupBy = $request->get('group_by', 'day');
            
            $dateFormat = match($groupBy) {
                'hour' => '%Y-%m-%d %H:00:00',
                'day' => '%Y-%m-%d',
                'week' => '%Y-%u', // Year-week
                default => '%Y-%m-%d'
            };

            $activities = SystemActivity::byUser(Auth::id())
                ->where('created_at', '>=', now()->subDays($days))
                ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period")
                ->selectRaw('activity_type, COUNT(*) as count')
                ->groupBy('period', 'activity_type')
                ->orderBy('period')
                ->get()
                ->groupBy('period');

            $timeline = [];
            foreach ($activities as $period => $periodActivities) {
                $timeline[] = [
                    'period' => $period,
                    'activities' => $periodActivities->pluck('count', 'activity_type')->toArray(),
                    'total' => $periodActivities->sum('count'),
                ];
            }

            return response()->json(['timeline' => $timeline]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch activity timeline'], 500);
        }
    }

    /**
     * Get user's active sessions
     */
    public function getUserSessions(Request $request): JsonResponse
    {
        try {
            $sessions = UserSession::where('user_id', Auth::id())
                ->orderBy('last_activity_at', 'desc')
                ->get();

            return response()->json(['sessions' => $sessions]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch sessions'], 500);
        }
    }

    /**
     * Revoke a user session
     */
    public function revokeSession(Request $request, $sessionId): JsonResponse
    {
        try {
            $session = UserSession::where('id', $sessionId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $session->markAsLoggedOut();

            // Log the session revocation
            SystemActivity::logAuthActivity(
                'session_revoked',
                "Session {$session->session_id} was revoked",
                [
                    'revoked_session_id' => $session->session_id,
                    'revoked_from_ip' => $session->ip_address,
                ]
            );

            return response()->json(['message' => 'Session revoked successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to revoke session'], 500);
        }
    }

    /**
     * Get security events for user
     */
    public function getSecurityEvents(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => 'sometimes|integer|min:1|max:100',
                'severity' => 'sometimes|string|in:info,warning,error,critical',
                'resolved' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $query = SecurityEvent::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc');

            if ($request->has('severity')) {
                $query->bySeverity($request->severity);
            }

            if ($request->has('resolved')) {
                if ($request->boolean('resolved')) {
                    $query->resolved();
                } else {
                    $query->unresolved();
                }
            }

            $limit = $request->get('limit', 20);
            $events = $query->limit($limit)->get();

            return response()->json(['security_events' => $events]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch security events'], 500);
        }
    }

    /**
     * Export user activities (for compliance/audit purposes)
     */
    public function exportActivities(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'format' => 'required|string|in:json,csv',
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'include_metadata' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $activities = SystemActivity::byUser(Auth::id())
                ->whereBetween('created_at', [
                    $request->date_from,
                    $request->date_to . ' 23:59:59'
                ])
                ->with(['file', 'targetUser'])
                ->orderBy('created_at', 'desc')
                ->get();

            $exportData = $activities->map(function ($activity) use ($request) {
                $data = [
                    'timestamp' => $activity->created_at->toISOString(),
                    'activity_type' => $activity->activity_type,
                    'action' => $activity->action,
                    'description' => $activity->description,
                    'entity_type' => $activity->entity_type,
                    'file_name' => $activity->file?->file_name,
                    'target_user' => $activity->targetUser?->name,
                    'risk_level' => $activity->risk_level,
                    'ip_address' => $activity->ip_address,
                ];

                if ($request->boolean('include_metadata')) {
                    $data['metadata'] = $activity->metadata;
                    $data['user_agent'] = $activity->user_agent;
                }

                return $data;
            });

            if ($request->input('format') === 'csv') {
                // Convert to CSV format
                $firstRow = $exportData->first();
                if (!$firstRow) {
                    return response()->json(['error' => 'No data to export'], 404);
                }
                
                $csvHeaders = array_keys($firstRow);
                $csvContent = implode(',', $csvHeaders) . "\n";
                
                foreach ($exportData as $row) {
                    $csvContent .= implode(',', array_map(function($value) {
                        return is_array($value) ? json_encode($value) : $value;
                    }, $row)) . "\n";
                }

                return response($csvContent, 200, [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="activities_export.csv"',
                ]);
            }

            return response()->json([
                'export' => $exportData,
                'meta' => [
                    'total_records' => $exportData->count(),
                    'date_range' => [
                        'from' => $request->date_from,
                        'to' => $request->date_to,
                    ],
                    'exported_at' => now()->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to export activities'], 500);
        }
    }

    /**
     * Get system-wide activity analytics (admin only)
     */
    public function getSystemAnalytics(Request $request): JsonResponse
    {
        // This would require admin middleware
        try {
            $validator = Validator::make($request->all(), [
                'days' => 'sometimes|integer|min:1|max:365',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $days = $request->get('days', 30);
            $since = now()->subDays($days);

            $analytics = [
                'total_activities' => SystemActivity::where('created_at', '>=', $since)->count(),
                'active_users' => SystemActivity::where('created_at', '>=', $since)
                    ->distinct('user_id')->count('user_id'),
                'high_risk_activities' => SystemActivity::highRisk()
                    ->where('created_at', '>=', $since)->count(),
                'security_events' => SecurityEvent::where('created_at', '>=', $since)->count(),
                'suspicious_activities' => SystemActivity::suspicious()
                    ->where('created_at', '>=', $since)->count(),
            ];

            // Top active users
            $topUsers = SystemActivity::where('created_at', '>=', $since)
                ->selectRaw('user_id, COUNT(*) as activity_count')
                ->with('user:id,name,email')
                ->groupBy('user_id')
                ->orderBy('activity_count', 'desc')
                ->limit(10)
                ->get();

            // Activity trends by type
            $activityTrends = SystemActivity::where('created_at', '>=', $since)
                ->selectRaw('activity_type, DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('activity_type', 'date')
                ->orderBy('date')
                ->get()
                ->groupBy('activity_type');

            return response()->json([
                'analytics' => $analytics,
                'top_users' => $topUsers,
                'activity_trends' => $activityTrends,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch system analytics'], 500);
        }
    }

    // Private helper methods
    private function userCanAccessFile(File $file, $user): bool
    {
        if (!$user) {
            return false;
        }

        // File owner has access
        if ($file->user_id === $user->id) {
            return true;
        }

        // Sharing feature removed: only owner can access
        return false;
    }
}
