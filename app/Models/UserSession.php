<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'location_country',
        'location_city',
        'location_timezone',
        'is_active',
        'last_activity_at',
        'login_method',
        'is_suspicious',
        'trusted_device',
        'expires_at',
        'logged_out_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_suspicious' => 'boolean',
        'trusted_device' => 'boolean',
        'last_activity_at' => 'datetime',
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    // Login methods
    const LOGIN_PASSWORD = 'password';
    const LOGIN_WEBAUTHN = 'webauthn';
    const LOGIN_OAUTH = 'oauth';
    const LOGIN_API = 'api';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    public function scopeTrusted($query)
    {
        return $query->where('trusted_device', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeRecentActivity($query, $hours = 1)
    {
        return $query->where('last_activity_at', '>=', now()->subHours($hours));
    }

    // Helper methods
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isStillActive(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function getDeviceType(): string
    {
        $userAgent = strtolower($this->user_agent ?? '');
        
        if (strpos($userAgent, 'mobile') !== false || strpos($userAgent, 'android') !== false || strpos($userAgent, 'iphone') !== false) {
            return 'mobile';
        } elseif (strpos($userAgent, 'tablet') !== false || strpos($userAgent, 'ipad') !== false) {
            return 'tablet';
        } elseif (strpos($userAgent, 'bot') !== false || strpos($userAgent, 'crawler') !== false) {
            return 'bot';
        }
        
        return 'desktop';
    }

    public function getBrowserName(): string
    {
        $userAgent = $this->user_agent ?? '';
        
        if (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'Opera') !== false) {
            return 'Opera';
        }
        
        return 'Unknown';
    }

    public function markAsLoggedOut(): void
    {
        $this->update([
            'is_active' => false,
            'logged_out_at' => now(),
        ]);
    }

    public function updateActivity(): void
    {
        $this->update([
            'last_activity_at' => now(),
        ]);
    }

    public function markAsSuspicious(string $reason = null): void
    {
        $this->update([
            'is_suspicious' => true,
        ]);

        // Log security event
        SecurityEvent::create([
            'user_id' => $this->user_id,
            'event_type' => 'suspicious_session',
            'severity' => 'warning',
            'description' => $reason ?? 'Session marked as suspicious',
            'details' => [
                'session_id' => $this->session_id,
                'ip_address' => $this->ip_address,
                'user_agent' => $this->user_agent,
            ],
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
        ]);
    }

    public static function createForUser(User $user, array $attributes = []): self
    {
        return self::create(array_merge([
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'last_activity_at' => now(),
            'expires_at' => now()->addDays(30), // 30-day session expiry
            'is_active' => true,
        ], $attributes));
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed fields
        $array['device_type'] = $this->getDeviceType();
        $array['browser_name'] = $this->getBrowserName();
        $array['is_expired'] = $this->isExpired();
        $array['is_still_active'] = $this->isStillActive();
        
        return $array;
    }
}
