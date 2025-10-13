<?php

namespace App\Services;

use App\Models\User;
use App\Models\SystemActivity;
use App\Models\UserSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
class DeviceDetectionService
{
    public function __construct()
    {
        //
    }

    /**
     * Check if this is a new device login and handle notifications
     */
    public function handleLogin(User $user, string $sessionId): array
    {
        $deviceInfo = $this->getDeviceInfo();
        $locationInfo = $this->getLocationInfo();
        
        // Create device fingerprint
        $fingerprint = $this->generateDeviceFingerprint($deviceInfo);
        
        // Check if this is a known device
        $isNewDevice = $this->isNewDevice($user, $fingerprint);
        
        // Create user session record
        $session = $this->createUserSession($user, $sessionId, $deviceInfo, $locationInfo, $fingerprint, $isNewDevice);
        
        // Log the login activity
        $this->logLoginActivity($user, $deviceInfo, $locationInfo, $isNewDevice);
        
        // Send notification if it's a new device and user has notifications enabled
        if ($isNewDevice && $user->login_notifications_enabled) {
            $this->sendNewDeviceNotification($user, $deviceInfo, $locationInfo);
        }
        
        return [
            'is_new_device' => $isNewDevice,
            'device_info' => $deviceInfo,
            'location_info' => $locationInfo,
            'session' => $session
        ];
    }

    /**
     * Get comprehensive device information
     */
    protected function getDeviceInfo(): array
    {
        $userAgent = request()->userAgent() ?? '';
        
        return [
            'device_type' => $this->getDeviceType($userAgent),
            'browser' => $this->getBrowser($userAgent),
            'browser_version' => $this->getBrowserVersion($userAgent),
            'platform' => $this->getPlatform($userAgent),
            'platform_version' => '',
            'is_mobile' => $this->isMobile($userAgent),
            'is_tablet' => $this->isTablet($userAgent),
            'is_desktop' => $this->isDesktop($userAgent),
            'is_robot' => $this->isRobot($userAgent),
            'user_agent' => $userAgent,
            'languages' => request()->getLanguages(),
        ];
    }

    /**
     * Get device type classification
     */
    protected function getDeviceType(string $userAgent): string
    {
        if ($this->isMobile($userAgent)) {
            return 'mobile';
        } elseif ($this->isTablet($userAgent)) {
            return 'tablet';
        } elseif ($this->isDesktop($userAgent)) {
            return 'desktop';
        } elseif ($this->isRobot($userAgent)) {
            return 'bot';
        }
        
        return 'unknown';
    }

    /**
     * Simple user agent parsing methods
     */
    protected function isMobile(string $userAgent): bool
    {
        return preg_match('/Mobile|Android|iPhone|iPod|BlackBerry|Windows Phone/i', $userAgent);
    }

    protected function isTablet(string $userAgent): bool
    {
        return preg_match('/iPad|Tablet|Kindle|Silk/i', $userAgent);
    }

    protected function isDesktop(string $userAgent): bool
    {
        return !$this->isMobile($userAgent) && !$this->isTablet($userAgent) && !$this->isRobot($userAgent);
    }

    protected function isRobot(string $userAgent): bool
    {
        return preg_match('/bot|crawler|spider|scraper/i', $userAgent);
    }

    protected function getBrowser(string $userAgent): string
    {
        if (preg_match('/Firefox/i', $userAgent)) {
            return 'Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            return 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            return 'Safari';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            return 'Edge';
        } elseif (preg_match('/Opera/i', $userAgent)) {
            return 'Opera';
        }
        
        return 'Unknown';
    }

    protected function getBrowserVersion(string $userAgent): string
    {
        if (preg_match('/Firefox\/([0-9.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        } elseif (preg_match('/Chrome\/([0-9.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        } elseif (preg_match('/Safari\/([0-9.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        } elseif (preg_match('/Edge\/([0-9.]+)/i', $userAgent, $matches)) {
            return $matches[1];
        }
        
        return '';
    }

    protected function getPlatform(string $userAgent): string
    {
        if (preg_match('/Windows/i', $userAgent)) {
            return 'Windows';
        } elseif (preg_match('/Mac OS X|macOS/i', $userAgent)) {
            return 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            return 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            return 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $userAgent)) {
            return 'iOS';
        }
        
        return 'Unknown';
    }

    /**
     * Get location information from IP
     */
    protected function getLocationInfo(): array
    {
        $ip = request()->ip();
        
        // Skip location lookup for local IPs
        if ($this->isLocalIP($ip)) {
            return [
                'ip_address' => $ip,
                'country' => '--', // Use -- for local/unknown (2 chars)
                'city' => 'Local',
                'timezone' => config('app.timezone'),
                'is_local' => true
            ];
        }

        // Try to get location from cache first
        $cacheKey = "location_info_{$ip}";
        $locationInfo = Cache::get($cacheKey);

        if (!$locationInfo) {
            $locationInfo = $this->fetchLocationInfo($ip);
            // Cache for 24 hours
            Cache::put($cacheKey, $locationInfo, 86400);
        }

        return $locationInfo;
    }

    /**
     * Fetch location info from IP geolocation service
     */
    protected function fetchLocationInfo(string $ip): array
    {
        try {
            // Using ipapi.co (free tier: 1000 requests/day)
            $response = Http::timeout(5)
                ->withOptions(['verify' => false]) // Disable SSL verification for development
                ->get("https://ipapi.co/{$ip}/json/");
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'ip_address' => $ip,
                    'country' => $data['country_code'] ?? '--', // Use 2-letter country code
                    'city' => $data['city'] ?? 'Unknown',
                    'timezone' => $data['timezone'] ?? config('app.timezone'),
                    'is_local' => false,
                    'raw_data' => $data
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch location info', [
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);
        }

        // Fallback
        return [
            'ip_address' => $ip,
            'country' => '--', // Use -- for unknown (2 chars)
            'city' => 'Unknown',
            'timezone' => config('app.timezone'),
            'is_local' => false
        ];
    }

    /**
     * Check if IP is local/private
     */
    protected function isLocalIP(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', 'localhost']) || 
               filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Generate device fingerprint
     */
    protected function generateDeviceFingerprint(array $deviceInfo): string
    {
        $components = [
            $deviceInfo['browser'],
            $deviceInfo['platform'],
            $deviceInfo['device_type'],
            request()->ip(), // Include IP for additional uniqueness
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Check if this is a new device for the user
     */
    protected function isNewDevice(User $user, string $fingerprint): bool
    {
        return !UserSession::where('user_id', $user->id)
            ->where('device_fingerprint', $fingerprint)
            ->where('created_at', '>', now()->subDays(30)) // Consider device "known" for 30 days
            ->exists();
    }

    /**
     * Create user session record
     */
    protected function createUserSession(User $user, string $sessionId, array $deviceInfo, array $locationInfo, string $fingerprint, bool $isNewDevice): UserSession
    {
        // Validate session ID is not empty or invalid
        if (empty($sessionId) || $sessionId === 'session') {
            Log::warning('Invalid session ID detected', [
                'session_id' => $sessionId,
                'user_id' => $user->id
            ]);
            $sessionId = session()->getId() ?: 'fallback_' . uniqid();
        }

        // Debug: Log the boolean values being inserted
        $booleanValues = [
            'is_mobile' => (bool) $deviceInfo['is_mobile'],
            'is_tablet' => (bool) $deviceInfo['is_tablet'], 
            'is_desktop' => (bool) $deviceInfo['is_desktop'],
            'is_suspicious' => (bool) $this->detectSuspiciousActivity($user, $deviceInfo, $locationInfo),
            'trusted_device' => (bool) !$isNewDevice,
        ];
        
        Log::debug('UserSession boolean values', [
            'original_values' => [
                'is_mobile' => $deviceInfo['is_mobile'],
                'is_tablet' => $deviceInfo['is_tablet'],
                'is_desktop' => $deviceInfo['is_desktop'],
            ],
            'cast_values' => $booleanValues,
            'types' => [
                'is_mobile' => gettype($booleanValues['is_mobile']),
                'is_tablet' => gettype($booleanValues['is_tablet']),
                'is_desktop' => gettype($booleanValues['is_desktop']),
            ]
        ]);

        return UserSession::updateOrCreate(
            [
                'session_id' => $sessionId, // Find by session_id
            ],
            [
                'user_id' => $user->id,
                'ip_address' => $locationInfo['ip_address'],
                'user_agent' => $deviceInfo['user_agent'],
                'device_fingerprint' => $fingerprint,
                'location_country' => $locationInfo['country'],
                'location_city' => $locationInfo['city'],
                'location_timezone' => $locationInfo['timezone'],
                'device_type' => $deviceInfo['device_type'],
                'browser' => $deviceInfo['browser'],
                'platform' => $deviceInfo['platform'],
                'is_mobile' => DB::raw($booleanValues['is_mobile'] ? 'true' : 'false'),
                'is_tablet' => DB::raw($booleanValues['is_tablet'] ? 'true' : 'false'),
                'is_desktop' => DB::raw($booleanValues['is_desktop'] ? 'true' : 'false'),
                'login_method' => 'web', // Can be extended for other methods
                'is_suspicious' => DB::raw($booleanValues['is_suspicious'] ? 'true' : 'false'),
                'trusted_device' => DB::raw($booleanValues['trusted_device'] ? 'true' : 'false'),
                'expires_at' => now()->addDays(30), // Session expires in 30 days
            ]
        );
    }

    /**
     * Log login activity
     */
    protected function logLoginActivity(User $user, array $deviceInfo, array $locationInfo, bool $isNewDevice): void
    {
        // Check for suspicious activity (5+ failed attempts from this IP in last hour)
        $recentFailedAttempts = SystemActivity::where('ip_address', $locationInfo['ip_address'])
            ->where('activity_type', 'auth')
            ->where('action', 'failed_login')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $isSuspicious = $recentFailedAttempts >= 5;
        
        // Determine risk level based on new device and suspicious activity
        $riskLevel = SystemActivity::RISK_LOW;
        if ($isSuspicious) {
            $riskLevel = SystemActivity::RISK_HIGH;
        } elseif ($isNewDevice) {
            $riskLevel = SystemActivity::RISK_MEDIUM;
        }
        
        $description = $isNewDevice 
            ? "New device login: {$user->name} logged in from {$deviceInfo['device_type']} ({$deviceInfo['browser']}) in {$locationInfo['city']}, {$locationInfo['country']}"
            : "Login: {$user->name} logged in from known device";

        // Add suspicious flag to description if detected
        if ($isSuspicious) {
            $description = "🚨 SUSPICIOUS LOGIN: " . $description . " (5+ failed attempts from this IP in past hour)";
        }

        SystemActivity::logAuthActivity(
            SystemActivity::ACTION_LOGIN,
            $description,
            [
                'is_new_device' => $isNewDevice,
                'is_suspicious' => $isSuspicious,
                'failed_attempts_count' => $recentFailedAttempts,
                'device_info' => $deviceInfo,
                'location_info' => $locationInfo,
                'session_id' => session()->getId(),
            ],
            $riskLevel,
            $user
        );
    }

    /**
     * Detect suspicious activity
     */
    protected function detectSuspiciousActivity(User $user, array $deviceInfo, array $locationInfo): bool
    {
        // Check for failed attempts from this IP in the last hour
        $recentFailedAttempts = SystemActivity::where('ip_address', $locationInfo['ip_address'])
            ->where('activity_type', 'auth')
            ->where('action', 'failed_login')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        // Flag as suspicious if 5+ failed attempts in the last hour
        $isSuspicious = $recentFailedAttempts >= 5;

        // Check for multiple rapid logins from different locations
        $recentLogins = UserSession::where('user_id', $user->id)
            ->where('created_at', '>', now()->subHours(1))
            ->get();

        if ($recentLogins->count() > 3) {
            return true;
        }

        // Check for logins from very different locations within short time
        $lastLogin = UserSession::where('user_id', $user->id)
            ->where('created_at', '>', now()->subHours(6))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastLogin && 
            $lastLogin->location_country !== $locationInfo['country'] && 
            !$locationInfo['is_local']) {
            return true;
        }

        // Check for bot/automated access
        if ($deviceInfo['is_robot']) {
            return true;
        }

        return false;
    }

    /**
     * Send new device notification
     */
    protected function sendNewDeviceNotification(User $user, array $deviceInfo, array $locationInfo): void
    {
        try {
            // Create in-app notification
            $user->notifications()->create([
                'type' => 'new_device_login',
                'title' => 'New Device Login',
                'message' => "New login detected from {$deviceInfo['device_type']} in {$locationInfo['city']}, {$locationInfo['country']}",
                'data' => [
                    'device_info' => $deviceInfo,
                    'location_info' => $locationInfo,
                    'login_time' => now()->toISOString(),
                ]
            ]);

            // Send email notification if enabled
            if ($user->email_notifications_enabled) {
                \Mail::to($user->email)->send(new \App\Mail\NewDeviceLoginNotification($user, $deviceInfo, $locationInfo));
            }

        } catch (\Exception $e) {
            Log::error('Failed to send new device notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get user's recent sessions
     */
    public function getUserSessions(User $user, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return UserSession::where('user_id', $user->id)
            ->orderBy('last_activity_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Terminate session
     */
    public function terminateSession(User $user, string $sessionId): bool
    {
        $session = UserSession::where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->first();

        if ($session) {
            $session->update([
                'is_active' => DB::raw('false'),
                'logged_out_at' => now()
            ]);

            // Log the logout
            SystemActivity::logAuthActivity(
                SystemActivity::ACTION_LOGOUT,
                "Session terminated: {$user->name} logged out",
                ['session_id' => $sessionId],
                SystemActivity::RISK_LOW,
                $user
            );

            return true;
        }

        return false;
    }
}
