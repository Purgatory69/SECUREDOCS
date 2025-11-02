<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSession;
use App\Models\SystemActivity;
use App\Services\DeviceDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserSessionController extends Controller
{
    protected DeviceDetectionService $deviceDetectionService;

    public function __construct(DeviceDetectionService $deviceDetectionService)
    {
        $this->deviceDetectionService = $deviceDetectionService;
    }

    /**
     * Get user's active sessions
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            Log::info('Loading user sessions', [
                'user_id' => $user->id,
                'current_session_id' => session()->getId()
            ]);

            $sessions = $this->deviceDetectionService->getUserSessions($user, 20);

            Log::info('Sessions retrieved from database', [
                'user_id' => $user->id,
                'session_count' => $sessions->count(),
                'session_ids' => $sessions->pluck('session_id')->toArray()
            ]);

            $mappedSessions = $sessions->map(function ($session) {
                return [
                    'id' => $session->id,
                    'session_id' => $session->session_id,
                    'device_type' => $session->device_type ?? 'unknown',
                    'browser' => $session->browser ?? 'Unknown',
                    'platform' => $session->platform ?? 'Unknown',
                    'location' => ($session->location_city && $session->location_country) 
                        ? "{$session->location_city}, {$session->location_country}" 
                        : 'Unknown location',
                    'ip_address' => $session->ip_address,
                    'is_current' => $session->session_id === session()->getId(),
                    'is_active' => $session->is_active ?? false,
                    'is_suspicious' => $session->is_suspicious ?? false,
                    'trusted_device' => $session->trusted_device ?? false,
                    'last_activity' => $session->last_activity_at?->diffForHumans() ?? 'Unknown',
                    'created_at' => $session->created_at?->diffForHumans() ?? 'Unknown',
                ];
            });

            Log::info('Returning sessions to frontend', [
                'user_id' => $user->id,
                'mapped_session_count' => $mappedSessions->count()
            ]);

            return response()->json([
                'success' => true,
                'sessions' => $mappedSessions
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading user sessions', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terminate a specific session
     */
    public function terminate(Request $request, string $sessionId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Don't allow terminating current session via this endpoint
            if ($sessionId === session()->getId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot terminate current session. Please use logout instead.'
                ], 400);
            }

            $terminated = $this->deviceDetectionService->terminateSession($user, $sessionId);

            if ($terminated) {
                return response()->json([
                    'success' => true,
                    'message' => 'Session terminated successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Session not found or already terminated'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Session termination failed', [
                'session_id' => $sessionId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to terminate session'
            ], 500);
        }
    }

    /**
     * Terminate all other sessions (except current)
     */
    public function terminateAll(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $user = Auth::user();

        // Verify password for security
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.']
            ]);
        }

        $currentSessionId = session()->getId();
        
        // Get all active sessions except current
        $sessions = UserSession::where('user_id', $user->id)
            ->where('session_id', '!=', $currentSessionId)
            ->whereRaw('is_active = true')
            ->get();

        $terminatedCount = 0;
        foreach ($sessions as $session) {
            if ($this->deviceDetectionService->terminateSession($user, $session->session_id)) {
                $terminatedCount++;
            }
        }

        // Log this security action
        $userName = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
        SystemActivity::logAuthActivity(
            'sessions_terminated',
            "All other sessions terminated by {$userName}",
            ['terminated_count' => $terminatedCount],
            SystemActivity::RISK_MEDIUM,
            $user
        );

        return response()->json([
            'success' => true,
            'message' => "Successfully terminated {$terminatedCount} sessions",
            'terminated_count' => $terminatedCount
        ]);
    }

    /**
     * Mark device as trusted
     */
    public function trustDevice(Request $request, string $sessionId): JsonResponse
    {
        $user = Auth::user();
        
        $session = UserSession::where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        $session->update(['trusted_device' => true]);

        // Log this action
        $userName = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
        SystemActivity::logAuthActivity(
            'device_trusted',
            "Device marked as trusted by {$userName}",
            [
                'session_id' => $sessionId,
                'device_type' => $session->device_type,
                'location' => "{$session->location_city}, {$session->location_country}"
            ],
            SystemActivity::RISK_LOW,
            $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Device marked as trusted'
        ]);
    }

    /**
     * Get user's notification preferences
     */
    public function getNotificationPreferences(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'preferences' => [
                    'email_notifications_enabled' => $user->email_notifications_enabled ?? true,
                    'login_notifications_enabled' => $user->login_notifications_enabled ?? true,
                    'security_notifications_enabled' => $user->security_notifications_enabled ?? true,
                    'activity_notifications_enabled' => $user->activity_notifications_enabled ?? false,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading notification preferences', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load preferences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        try {
            Log::info('Notification preferences update request', [
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            $validated = $request->validate([
                'email_notifications_enabled' => 'boolean',
                'login_notifications_enabled' => 'boolean',
                'security_notifications_enabled' => 'boolean',
                'activity_notifications_enabled' => 'boolean',
            ]);

            Log::info('Validated notification data', ['validated' => $validated]);

            // Explicitly convert to proper boolean values for PostgreSQL using DB::raw
            $user = Auth::user();
            
            // Use individual field updates with DB::raw to ensure proper boolean casting
            foreach ($validated as $key => $value) {
                $booleanValue = $value ? 'true' : 'false';
                DB::statement("UPDATE users SET {$key} = {$booleanValue} WHERE id = ?", [$user->id]);
            }
            
            // Refresh the user model to get updated values
            $user->refresh();

            Log::info('Boolean converted data', ['validated' => $validated]);

            Log::info('User preferences updated successfully', [
                'user_id' => $user->id,
                'updated_data' => $validated
            ]);

            // Log preference changes
            $userName = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
            SystemActivity::logAuthActivity(
                'notification_preferences_updated',
                "Notification preferences updated by {$userName}",
                $validated,
                SystemActivity::RISK_LOW,
                $user
            );

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
                'preferences' => [
                    'email_notifications_enabled' => $user->email_notifications_enabled,
                    'login_notifications_enabled' => $user->login_notifications_enabled,
                    'security_notifications_enabled' => $user->security_notifications_enabled,
                    'activity_notifications_enabled' => $user->activity_notifications_enabled,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Notification preferences update failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent activity for the user
     */
    public function getRecentActivity(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $limit = $request->get('limit', 20);

            $activities = SystemActivity::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'activities' => $activities->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'type' => $activity->activity_type,
                        'action' => $activity->action,
                        'description' => $activity->description,
                        'risk_level' => $activity->risk_level,
                        'risk_level_color' => $this->getRiskLevelColor($activity->risk_level),
                        'risk_level_icon' => $this->getRiskLevelIcon($activity->risk_level),
                        'activity_type_icon' => $this->getActivityTypeIcon($activity->activity_type),
                        'ip_address' => $activity->ip_address,
                        'location' => ($activity->location_city && $activity->location_country) 
                            ? "{$activity->location_city}, {$activity->location_country}" 
                            : null,
                        'device_type' => $activity->device_type,
                        'browser' => $activity->browser,
                        'is_suspicious' => $activity->is_suspicious ?? false,
                        'created_at' => $activity->created_at?->toISOString(),
                        'time_ago' => $activity->created_at?->diffForHumans() ?? 'Unknown',
                    ];
                })
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading recent activity', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load activity: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getRiskLevelColor(string $riskLevel): string
    {
        return match($riskLevel) {
            'low' => 'text-green-600',
            'medium' => 'text-yellow-600',
            'high' => 'text-orange-600',
            'critical' => 'text-red-600',
            default => 'text-gray-600'
        };
    }

    private function getRiskLevelIcon(string $riskLevel): string
    {
        return match($riskLevel) {
            'low' => '✅',
            'medium' => '⚠️',
            'high' => '🔶',
            'critical' => '🚨',
            default => 'ℹ️'
        };
    }

    private function getActivityTypeIcon(string $activityType): string
    {
        return match($activityType) {
            'auth' => '🔐',
            'file' => '📄',
            'sharing' => '🔗',
            'system' => '⚙️',
            default => '📋'
        };
    }
}
