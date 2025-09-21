<?php

namespace App\Listeners;

use App\Services\DeviceDetectionService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class HandleUserLogin
{
    protected DeviceDetectionService $deviceDetectionService;

    /**
     * Create the event listener.
     */
    public function __construct(DeviceDetectionService $deviceDetectionService)
    {
        $this->deviceDetectionService = $deviceDetectionService;
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        try {
            $user = $event->user;
            
            // Ensure we have a User model instance
            if (!$user instanceof \App\Models\User) {
                Log::warning('Login event user is not an instance of App\Models\User', [
                    'user_class' => get_class($user),
                    'user_id' => $user->getAuthIdentifier()
                ]);
                return;
            }

            $sessionId = session()->getId();
            
            // Debug logging
            Log::info('HandleUserLogin processing', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'session_id_length' => strlen($sessionId ?? ''),
                'session_id_type' => gettype($sessionId)
            ]);

            // Handle device detection and notifications
            $result = $this->deviceDetectionService->handleLogin($user, $sessionId);

            // Log the result for debugging
            Log::info('User login processed', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'is_new_device' => $result['is_new_device'],
                'device_type' => $result['device_info']['device_type'] ?? 'unknown',
                'location' => $result['location_info']['city'] ?? 'unknown',
            ]);

        } catch (\Exception $e) {
            // Log error but don't break the login process
            Log::error('Failed to process user login', [
                'user_id' => $event->user->getAuthIdentifier(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
