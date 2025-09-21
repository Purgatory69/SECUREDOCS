<?php

namespace App\Http\Middleware;

use App\Services\DeviceDetectionService;
use App\Models\UserSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    protected DeviceDetectionService $deviceDetectionService;

    public function __construct(DeviceDetectionService $deviceDetectionService)
    {
        $this->deviceDetectionService = $deviceDetectionService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track for authenticated users
        if (Auth::check()) {
            $this->trackActivity($request);
        }

        return $response;
    }

    /**
     * Track user activity and update session
     */
    protected function trackActivity(Request $request): void
    {
        $user = Auth::user();
        $sessionId = session()->getId();

        try {
            // Update or create user session
            $userSession = UserSession::where('session_id', $sessionId)
                ->where('user_id', $user->id)
                ->first();

            if ($userSession) {
                // Update existing session
                $userSession->update([
                    'last_activity_at' => now(),
                    'ip_address' => $request->ip(),
                ]);
            } else {
                // This might be a new session that wasn't tracked during login
                // Handle it as a potential new device login
                $this->deviceDetectionService->handleLogin($user, $sessionId);
            }

        } catch (\Exception $e) {
            // Log error but don't break the request
            \Log::error('Failed to track user activity', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
