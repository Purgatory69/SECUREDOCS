<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'policy_id',
        'file_id',
        'violation_type',
        'violation_category',
        'severity',
        'description',
        'details',
        'ip_address',
        'user_agent',
        'location_country',
        'location_city',
        'device_fingerprint',
        'status',
        'resolved_by_user_id',
        'resolved_at',
        'resolution_notes',
        'auto_action_taken',
    ];

    protected $casts = [
        'details' => 'array',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public $timestamps = ['created_at'];
    const UPDATED_AT = null;

    // Violation categories
    const CATEGORY_POLICY = 'policy';
    const CATEGORY_DLP = 'dlp';
    const CATEGORY_ACCESS = 'access';
    const CATEGORY_DEVICE = 'device';
    const CATEGORY_TIME = 'time';
    const CATEGORY_ENCRYPTION = 'encryption';
    const CATEGORY_SESSION = 'session';

    // Severity levels
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    // Status values
    const STATUS_OPEN = 'open';
    const STATUS_INVESTIGATING = 'investigating';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_FALSE_POSITIVE = 'false_positive';
    const STATUS_ACKNOWLEDGED = 'acknowledged';

    // Common violation types
    const TYPE_ACCESS_DENIED = 'access_denied';
    const TYPE_DLP_TRIGGER = 'dlp_trigger';
    const TYPE_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    const TYPE_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    const TYPE_POLICY_VIOLATION = 'policy_violation';
    const TYPE_DEVICE_NOT_TRUSTED = 'device_not_trusted';
    const TYPE_TIME_RESTRICTION = 'time_restriction';
    const TYPE_IP_RESTRICTION = 'ip_restriction';
    const TYPE_COUNTRY_RESTRICTION = 'country_restriction';
    const TYPE_ENCRYPTION_REQUIRED = 'encryption_required';
    const TYPE_SESSION_LIMIT_EXCEEDED = 'session_limit_exceeded';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(SecurityPolicy::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('violation_category', $category);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('violation_type', $type);
    }

    // Helper methods
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    public function isHighPriority(): bool
    {
        return in_array($this->severity, [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    public function resolve(User $resolvedBy, string $notes = ''): bool
    {
        return $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_by_user_id' => $resolvedBy->id,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function markAsFalsePositive(User $resolvedBy, string $notes = ''): bool
    {
        return $this->update([
            'status' => self::STATUS_FALSE_POSITIVE,
            'resolved_by_user_id' => $resolvedBy->id,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function acknowledge(User $acknowledgedBy): bool
    {
        return $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'resolved_by_user_id' => $acknowledgedBy->id,
            'resolved_at' => now(),
        ]);
    }

    public function startInvestigation(User $investigator): bool
    {
        return $this->update([
            'status' => self::STATUS_INVESTIGATING,
            'resolved_by_user_id' => $investigator->id,
        ]);
    }

    public function getSeverityColor(): string
    {
        return match($this->severity) {
            self::SEVERITY_CRITICAL => 'red',
            self::SEVERITY_HIGH => 'orange',
            self::SEVERITY_MEDIUM => 'yellow',
            self::SEVERITY_LOW => 'green',
            default => 'gray'
        };
    }

    public function getSeverityIcon(): string
    {
        return match($this->severity) {
            self::SEVERITY_CRITICAL => '🚨',
            self::SEVERITY_HIGH => '⚠️',
            self::SEVERITY_MEDIUM => '🔔',
            self::SEVERITY_LOW => 'ℹ️',
            default => '📋'
        };
    }

    public function getCategoryIcon(): string
    {
        return match($this->violation_category) {
            self::CATEGORY_POLICY => '📋',
            self::CATEGORY_DLP => '🔍',
            self::CATEGORY_ACCESS => '🚫',
            self::CATEGORY_DEVICE => '📱',
            self::CATEGORY_TIME => '🕐',
            self::CATEGORY_ENCRYPTION => '🔒',
            self::CATEGORY_SESSION => '👤',
            default => '🔔'
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => 'red',
            self::STATUS_INVESTIGATING => 'yellow',
            self::STATUS_RESOLVED => 'green',
            self::STATUS_FALSE_POSITIVE => 'blue',
            self::STATUS_ACKNOWLEDGED => 'gray',
            default => 'gray'
        };
    }

    public function getTimeAgo(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getFormattedDescription(): string
    {
        return $this->getSeverityIcon() . ' ' . $this->description;
    }

    public function getRiskScore(): int
    {
        $baseScore = match($this->severity) {
            self::SEVERITY_CRITICAL => 90,
            self::SEVERITY_HIGH => 70,
            self::SEVERITY_MEDIUM => 50,
            self::SEVERITY_LOW => 20,
            default => 10
        };

        // Adjust based on category
        $categoryMultiplier = match($this->violation_category) {
            self::CATEGORY_DLP => 1.2,
            self::CATEGORY_ACCESS => 1.1,
            self::CATEGORY_ENCRYPTION => 1.3,
            default => 1.0
        };

        return min(100, (int)($baseScore * $categoryMultiplier));
    }

    public function getLocationString(): string
    {
        $parts = array_filter([
            $this->location_city,
            $this->location_country,
        ]);

        return !empty($parts) ? implode(', ', $parts) : 'Unknown';
    }

    public static function createViolation(array $data): self
    {
        // Add context information
        $data['ip_address'] = $data['ip_address'] ?? request()->ip();
        $data['user_agent'] = $data['user_agent'] ?? request()->userAgent();
        
        return self::create($data);
    }

    public static function logAccessDenied(
        int $userId,
        ?int $fileId = null,
        string $reason = 'Access denied by security policy'
    ): self {
        return self::createViolation([
            'user_id' => $userId,
            'file_id' => $fileId,
            'violation_type' => self::TYPE_ACCESS_DENIED,
            'violation_category' => self::CATEGORY_ACCESS,
            'severity' => self::SEVERITY_MEDIUM,
            'description' => $reason,
            'details' => [
                'reason' => $reason,
                'file_id' => $fileId,
            ],
        ]);
    }

    public static function logDlpViolation(
        int $userId,
        int $fileId,
        array $dlpResults,
        ?int $policyId = null
    ): self {
        $severity = match(true) {
            $dlpResults['risk_score'] >= 80 => self::SEVERITY_CRITICAL,
            $dlpResults['risk_score'] >= 60 => self::SEVERITY_HIGH,
            $dlpResults['risk_score'] >= 40 => self::SEVERITY_MEDIUM,
            default => self::SEVERITY_LOW
        };

        return self::createViolation([
            'user_id' => $userId,
            'policy_id' => $policyId,
            'file_id' => $fileId,
            'violation_type' => self::TYPE_DLP_TRIGGER,
            'violation_category' => self::CATEGORY_DLP,
            'severity' => $severity,
            'description' => 'Sensitive content detected in file',
            'details' => $dlpResults,
            'auto_action_taken' => $dlpResults['action'] ?? null,
        ]);
    }

    public static function logSuspiciousActivity(
        int $userId,
        string $activity,
        array $context = []
    ): self {
        return self::createViolation([
            'user_id' => $userId,
            'violation_type' => self::TYPE_SUSPICIOUS_ACTIVITY,
            'violation_category' => self::CATEGORY_ACCESS,
            'severity' => self::SEVERITY_HIGH,
            'description' => "Suspicious activity detected: {$activity}",
            'details' => $context,
        ]);
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed fields
        $array['severity_color'] = $this->getSeverityColor();
        $array['severity_icon'] = $this->getSeverityIcon();
        $array['category_icon'] = $this->getCategoryIcon();
        $array['status_color'] = $this->getStatusColor();
        $array['time_ago'] = $this->getTimeAgo();
        $array['formatted_description'] = $this->getFormattedDescription();
        $array['risk_score'] = $this->getRiskScore();
        $array['location_string'] = $this->getLocationString();
        $array['user_name'] = $this->user?->name;
        $array['file_name'] = $this->file?->file_name;
        $array['policy_name'] = $this->policy?->name;
        $array['resolved_by_name'] = $this->resolvedBy?->name;
        
        return $array;
    }
}
