<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustedDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_name',
        'device_fingerprint',
        'device_type',
        'os_info',
        'browser_info',
        'trusted_at',
        'trusted_by_user_id',
        'trust_level',
        'access_restrictions',
        'last_used_at',
        'last_ip_address',
        'last_location_country',
        'last_location_city',
        'is_active',
        'revoked_at',
        'revoked_by_user_id',
        'revocation_reason',
        'auto_approved',
        'expires_at',
    ];

    protected $casts = [
        'access_restrictions' => 'array',
        'trusted_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
        'revoked_at' => 'datetime',
        'auto_approved' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Trust levels
    const TRUST_LIMITED = 'limited';
    const TRUST_STANDARD = 'standard';
    const TRUST_HIGH = 'high';

    // Device types
    const TYPE_DESKTOP = 'desktop';
    const TYPE_MOBILE = 'mobile';
    const TYPE_TABLET = 'tablet';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trusted_by_user_id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTrustLevel($query, $level)
    {
        return $query->where('trust_level', $level);
    }

    public function scopeRecentlyUsed($query, $hours = 24)
    {
        return $query->where('last_used_at', '>=', now()->subHours($hours));
    }

    // Helper methods
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isTrusted(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function updateLastUsed(?string $ipAddress = null, ?string $country = null, ?string $city = null): void
    {
        $this->update([
            'last_used_at' => now(),
            'last_ip_address' => $ipAddress ?? request()->ip(),
            'last_location_country' => $country,
            'last_location_city' => $city,
        ]);
    }

    public function revoke(User $revokedBy, string $reason = ''): bool
    {
        return $this->update([
            'is_active' => false,
            'revoked_at' => now(),
            'revoked_by_user_id' => $revokedBy->id,
            'revocation_reason' => $reason,
        ]);
    }

    public function trust(User $trustedBy, string $level = self::TRUST_STANDARD): bool
    {
        return $this->update([
            'is_active' => true,
            'trusted_at' => now(),
            'trusted_by_user_id' => $trustedBy->id,
            'trust_level' => $level,
            'revoked_at' => null,
            'revoked_by_user_id' => null,
            'revocation_reason' => null,
        ]);
    }

    public function extend(int $days = 30): bool
    {
        $newExpiration = $this->expires_at ? 
            $this->expires_at->addDays($days) : 
            now()->addDays($days);

        return $this->update(['expires_at' => $newExpiration]);
    }

    public function getPermissions(): array
    {
        $basePermissions = match($this->trust_level) {
            self::TRUST_HIGH => [
                'file_upload', 'file_download', 'file_share', 'file_delete',
                'folder_create', 'folder_delete', 'admin_access'
            ],
            self::TRUST_STANDARD => [
                'file_upload', 'file_download', 'file_share',
                'folder_create'
            ],
            self::TRUST_LIMITED => [
                'file_download'
            ],
            default => []
        };

        // Apply access restrictions
        if (!empty($this->access_restrictions)) {
            $restrictions = $this->access_restrictions;
            
            if (isset($restrictions['blocked_permissions'])) {
                $basePermissions = array_diff($basePermissions, $restrictions['blocked_permissions']);
            }
            
            if (isset($restrictions['additional_permissions'])) {
                $basePermissions = array_merge($basePermissions, $restrictions['additional_permissions']);
            }
        }

        return array_unique($basePermissions);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions());
    }

    public function getTrustLevelColor(): string
    {
        return match($this->trust_level) {
            self::TRUST_HIGH => 'green',
            self::TRUST_STANDARD => 'blue',
            self::TRUST_LIMITED => 'yellow',
            default => 'gray'
        };
    }

    public function getTrustLevelIcon(): string
    {
        return match($this->trust_level) {
            self::TRUST_HIGH => '🟢',
            self::TRUST_STANDARD => '🔵',
            self::TRUST_LIMITED => '🟡',
            default => '⚪'
        };
    }

    public function getDeviceTypeIcon(): string
    {
        return match($this->device_type) {
            self::TYPE_DESKTOP => '🖥️',
            self::TYPE_MOBILE => '📱',
            self::TYPE_TABLET => '📱',
            default => '💻'
        };
    }

    public function getStatusColor(): string
    {
        if (!$this->is_active) {
            return 'red';
        }
        
        if ($this->isExpired()) {
            return 'orange';
        }
        
        return 'green';
    }

    public function getLastUsedFormatted(): string
    {
        if (!$this->last_used_at) {
            return 'Never';
        }
        
        return $this->last_used_at->diffForHumans();
    }

    public function getLocationString(): string
    {
        $parts = array_filter([
            $this->last_location_city,
            $this->last_location_country,
        ]);

        return !empty($parts) ? implode(', ', $parts) : 'Unknown';
    }

    public static function generateFingerprint(array $deviceInfo): string
    {
        // Create a unique fingerprint based on device characteristics
        $components = [
            $deviceInfo['user_agent'] ?? '',
            $deviceInfo['screen_resolution'] ?? '',
            $deviceInfo['timezone'] ?? '',
            $deviceInfo['language'] ?? '',
            $deviceInfo['platform'] ?? '',
            $deviceInfo['plugins'] ?? '',
        ];

        return hash('sha256', implode('|', $components));
    }

    public static function createFromRequest(User $user, array $deviceInfo = []): self
    {
        $fingerprint = self::generateFingerprint($deviceInfo);
        
        // Check if device already exists
        $existingDevice = self::where('user_id', $user->id)
            ->where('device_fingerprint', $fingerprint)
            ->first();
            
        if ($existingDevice) {
            $existingDevice->updateLastUsed();
            return $existingDevice;
        }

        // Create new device
        return self::create([
            'user_id' => $user->id,
            'device_name' => $deviceInfo['device_name'] ?? self::generateDeviceName($deviceInfo),
            'device_fingerprint' => $fingerprint,
            'device_type' => self::detectDeviceType($deviceInfo),
            'os_info' => $deviceInfo['os_info'] ?? '',
            'browser_info' => $deviceInfo['browser_info'] ?? request()->userAgent(),
            'trusted_at' => now(),
            'trusted_by_user_id' => $user->id,
            'trust_level' => self::TRUST_STANDARD,
            'last_used_at' => now(),
            'last_ip_address' => request()->ip(),
            'auto_approved' => true,
            'expires_at' => now()->addDays(90), // 3 months default
        ]);
    }

    private static function generateDeviceName(array $deviceInfo): string
    {
        $userAgent = $deviceInfo['user_agent'] ?? request()->userAgent();
        
        // Extract browser and OS information
        $browser = 'Unknown Browser';
        $os = 'Unknown OS';
        
        if (stripos($userAgent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($userAgent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($userAgent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (stripos($userAgent, 'Edge') !== false) {
            $browser = 'Edge';
        }
        
        if (stripos($userAgent, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($userAgent, 'Macintosh') !== false) {
            $os = 'macOS';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            $os = 'Linux';
        } elseif (stripos($userAgent, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
            $os = 'iOS';
        }
        
        return "{$browser} on {$os}";
    }

    private static function detectDeviceType(array $deviceInfo): string
    {
        $userAgent = $deviceInfo['user_agent'] ?? request()->userAgent();
        
        if (stripos($userAgent, 'Mobile') !== false || stripos($userAgent, 'Android') !== false) {
            return self::TYPE_MOBILE;
        }
        
        if (stripos($userAgent, 'Tablet') !== false || stripos($userAgent, 'iPad') !== false) {
            return self::TYPE_TABLET;
        }
        
        return self::TYPE_DESKTOP;
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed fields
        $array['trust_level_color'] = $this->getTrustLevelColor();
        $array['trust_level_icon'] = $this->getTrustLevelIcon();
        $array['device_type_icon'] = $this->getDeviceTypeIcon();
        $array['status_color'] = $this->getStatusColor();
        $array['last_used_formatted'] = $this->getLastUsedFormatted();
        $array['location_string'] = $this->getLocationString();
        $array['permissions'] = $this->getPermissions();
        $array['is_expired'] = $this->isExpired();
        $array['is_trusted'] = $this->isTrusted();
        $array['trusted_by_name'] = $this->trustedBy?->name;
        $array['revoked_by_name'] = $this->revokedBy?->name;
        
        return $array;
    }
}
