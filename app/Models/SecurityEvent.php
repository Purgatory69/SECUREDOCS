<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'severity',
        'description',
        'details',
        'ip_address',
        'user_agent',
        'endpoint',
        'resolved',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'details' => 'array',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public $timestamps = ['created_at'];
    const UPDATED_AT = null;

    // Event types
    const TYPE_LOGIN_FAILED = 'login_failed';
    const TYPE_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    const TYPE_PERMISSION_DENIED = 'permission_denied';
    const TYPE_BRUTE_FORCE = 'brute_force';
    const TYPE_DATA_BREACH_ATTEMPT = 'data_breach_attempt';
    const TYPE_UNUSUAL_ACCESS = 'unusual_access';
    const TYPE_MALWARE_DETECTED = 'malware_detected';
    const TYPE_UNAUTHORIZED_SHARING = 'unauthorized_sharing';

    // Severity levels
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_ERROR = 'error';
    const SEVERITY_CRITICAL = 'critical';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeResolved($query)
    {
        return $query->where('resolved', true);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_ERROR, self::SEVERITY_CRITICAL]);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Helper methods
    public function getSeverityColor(): string
    {
        return match($this->severity) {
            self::SEVERITY_INFO => 'blue',
            self::SEVERITY_WARNING => 'yellow',
            self::SEVERITY_ERROR => 'orange',
            self::SEVERITY_CRITICAL => 'red',
            default => 'gray',
        };
    }

    public function getSeverityIcon(): string
    {
        return match($this->severity) {
            self::SEVERITY_INFO => 'ℹ️',
            self::SEVERITY_WARNING => '⚠️',
            self::SEVERITY_ERROR => '❌',
            self::SEVERITY_CRITICAL => '🚨',
            default => '❓',
        };
    }

    public function getEventTypeIcon(): string
    {
        return match($this->event_type) {
            self::TYPE_LOGIN_FAILED => '🔐',
            self::TYPE_SUSPICIOUS_ACTIVITY => '🕵️',
            self::TYPE_PERMISSION_DENIED => '🚫',
            self::TYPE_BRUTE_FORCE => '🔨',
            self::TYPE_DATA_BREACH_ATTEMPT => '💥',
            self::TYPE_UNUSUAL_ACCESS => '👁️',
            self::TYPE_MALWARE_DETECTED => '🦠',
            self::TYPE_UNAUTHORIZED_SHARING => '🔗',
            default => '⚠️',
        };
    }

    public function markAsResolved(User $resolvedBy, string $notes = null): void
    {
        $this->update([
            'resolved' => true,
            'resolved_by' => $resolvedBy->id,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        // Log the resolution activity
        SystemActivity::logSystemActivity(
            'security_event_resolved',
            "Security event #{$this->id} resolved by {$resolvedBy->name}",
            [
                'event_type' => $this->event_type,
                'severity' => $this->severity,
                'resolution_notes' => $notes,
            ],
            SystemActivity::RISK_LOW,
            $resolvedBy
        );
    }

    public function getTimeAgo(): string
    {
        return $this->created_at->diffForHumans();
    }

    // Static methods for creating specific event types
    public static function loginFailed(User $user = null, array $details = []): self
    {
        return self::create([
            'user_id' => $user?->id,
            'event_type' => self::TYPE_LOGIN_FAILED,
            'severity' => self::SEVERITY_WARNING,
            'description' => 'Failed login attempt',
            'details' => array_merge([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'attempted_email' => $details['email'] ?? null,
            ], $details),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function suspiciousActivity(User $user, string $description, array $details = []): self
    {
        return self::create([
            'user_id' => $user->id,
            'event_type' => self::TYPE_SUSPICIOUS_ACTIVITY,
            'severity' => self::SEVERITY_ERROR,
            'description' => $description,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function permissionDenied(User $user, string $resource, array $details = []): self
    {
        return self::create([
            'user_id' => $user->id,
            'event_type' => self::TYPE_PERMISSION_DENIED,
            'severity' => self::SEVERITY_WARNING,
            'description' => "Access denied to {$resource}",
            'details' => array_merge([
                'resource' => $resource,
                'requested_action' => $details['action'] ?? 'unknown',
            ], $details),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function bruteForceDetected(string $ipAddress, array $details = []): self
    {
        return self::create([
            'event_type' => self::TYPE_BRUTE_FORCE,
            'severity' => self::SEVERITY_CRITICAL,
            'description' => "Brute force attack detected from {$ipAddress}",
            'details' => array_merge([
                'attack_ip' => $ipAddress,
                'attempt_count' => $details['attempts'] ?? 0,
            ], $details),
            'ip_address' => $ipAddress,
        ]);
    }

    public static function unusualAccess(User $user, string $description, array $details = []): self
    {
        return self::create([
            'user_id' => $user->id,
            'event_type' => self::TYPE_UNUSUAL_ACCESS,
            'severity' => self::SEVERITY_WARNING,
            'description' => $description,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed fields
        $array['severity_color'] = $this->getSeverityColor();
        $array['severity_icon'] = $this->getSeverityIcon();
        $array['event_type_icon'] = $this->getEventTypeIcon();
        $array['time_ago'] = $this->getTimeAgo();
        
        return $array;
    }
}
