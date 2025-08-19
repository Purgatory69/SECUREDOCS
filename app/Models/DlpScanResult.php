<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DlpScanResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'policy_id',
        'scan_type',
        'scan_status',
        'risk_score',
        'risk_level',
        'detected_patterns',
        'detected_keywords',
        'confidence_score',
        'ai_classification',
        'ai_confidence',
        'ai_suggestions',
        'action_taken',
        'quarantine_reason',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_status',
        'review_notes',
        'scan_duration_ms',
        'file_size_bytes',
    ];

    protected $casts = [
        'detected_patterns' => 'array',
        'detected_keywords' => 'array',
        'ai_suggestions' => 'array',
        'confidence_score' => 'decimal:2',
        'ai_confidence' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Scan types
    const SCAN_UPLOAD = 'upload';
    const SCAN_SCHEDULED = 'scheduled';
    const SCAN_MANUAL = 'manual';
    const SCAN_REAL_TIME = 'real_time';

    // Scan status
    const STATUS_PENDING = 'pending';
    const STATUS_SCANNING = 'scanning';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Risk levels
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';

    // Review status
    const REVIEW_PENDING = 'pending';
    const REVIEW_APPROVED = 'approved';
    const REVIEW_REJECTED = 'rejected';
    const REVIEW_REQUIRES_ACTION = 'requires_action';

    // Actions taken
    const ACTION_NONE = 'none';
    const ACTION_FLAGGED = 'flagged';
    const ACTION_QUARANTINED = 'quarantined';
    const ACTION_BLOCKED = 'blocked';
    const ACTION_ENCRYPTED = 'encrypted';

    // Relationships
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(SecurityPolicy::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('scan_status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('scan_status', self::STATUS_PENDING);
    }

    public function scopeByRiskLevel($query, $level)
    {
        return $query->where('risk_level', $level);
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    public function scopeNeedsReview($query)
    {
        return $query->where('review_status', self::REVIEW_PENDING)
            ->where('scan_status', self::STATUS_COMPLETED);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action_taken', $action);
    }

    public function scopeQuarantined($query)
    {
        return $query->where('action_taken', self::ACTION_QUARANTINED);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Helper methods
    public function isCompleted(): bool
    {
        return $this->scan_status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->scan_status === self::STATUS_PENDING;
    }

    public function isHighRisk(): bool
    {
        return in_array($this->risk_level, [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    public function needsReview(): bool
    {
        return $this->review_status === self::REVIEW_PENDING && $this->isCompleted();
    }

    public function isQuarantined(): bool
    {
        return $this->action_taken === self::ACTION_QUARANTINED;
    }

    public function approve(User $reviewer, string $notes = ''): bool
    {
        return $this->update([
            'review_status' => self::REVIEW_APPROVED,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    public function reject(User $reviewer, string $notes = ''): bool
    {
        return $this->update([
            'review_status' => self::REVIEW_REJECTED,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    public function requiresAction(User $reviewer, string $notes = ''): bool
    {
        return $this->update([
            'review_status' => self::REVIEW_REQUIRES_ACTION,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    public function quarantine(string $reason = ''): bool
    {
        $success = $this->update([
            'action_taken' => self::ACTION_QUARANTINED,
            'quarantine_reason' => $reason,
        ]);

        if ($success) {
            // Log security violation
            SecurityViolation::logDlpViolation(
                $this->file->user_id ?? 0,
                $this->file_id,
                [
                    'risk_score' => $this->risk_score,
                    'detected_patterns' => $this->detected_patterns,
                    'detected_keywords' => $this->detected_keywords,
                    'action' => self::ACTION_QUARANTINED,
                    'reason' => $reason,
                ],
                $this->policy_id
            );
        }

        return $success;
    }

    public function getRiskLevelColor(): string
    {
        return match($this->risk_level) {
            self::RISK_CRITICAL => 'red',
            self::RISK_HIGH => 'orange',
            self::RISK_MEDIUM => 'yellow',
            self::RISK_LOW => 'green',
            default => 'gray'
        };
    }

    public function getRiskLevelIcon(): string
    {
        return match($this->risk_level) {
            self::RISK_CRITICAL => '🚨',
            self::RISK_HIGH => '⚠️',
            self::RISK_MEDIUM => '🔔',
            self::RISK_LOW => '✅',
            default => '❓'
        };
    }

    public function getStatusColor(): string
    {
        return match($this->scan_status) {
            self::STATUS_COMPLETED => 'green',
            self::STATUS_SCANNING => 'blue',
            self::STATUS_PENDING => 'yellow',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray'
        };
    }

    public function getStatusIcon(): string
    {
        return match($this->scan_status) {
            self::STATUS_COMPLETED => '✅',
            self::STATUS_SCANNING => '🔄',
            self::STATUS_PENDING => '⏳',
            self::STATUS_FAILED => '❌',
            self::STATUS_CANCELLED => '⏹️',
            default => '❓'
        };
    }

    public function getActionColor(): string
    {
        return match($this->action_taken) {
            self::ACTION_BLOCKED => 'red',
            self::ACTION_QUARANTINED => 'orange',
            self::ACTION_ENCRYPTED => 'blue',
            self::ACTION_FLAGGED => 'yellow',
            self::ACTION_NONE => 'green',
            default => 'gray'
        };
    }

    public function getActionIcon(): string
    {
        return match($this->action_taken) {
            self::ACTION_BLOCKED => '🚫',
            self::ACTION_QUARANTINED => '🔒',
            self::ACTION_ENCRYPTED => '🔐',
            self::ACTION_FLAGGED => '🏴',
            self::ACTION_NONE => '✅',
            default => '❓'
        };
    }

    public function getReviewStatusColor(): string
    {
        return match($this->review_status) {
            self::REVIEW_APPROVED => 'green',
            self::REVIEW_REJECTED => 'red',
            self::REVIEW_REQUIRES_ACTION => 'orange',
            self::REVIEW_PENDING => 'yellow',
            default => 'gray'
        };
    }

    public function getScanDurationFormatted(): string
    {
        if (!$this->scan_duration_ms) {
            return 'N/A';
        }

        if ($this->scan_duration_ms < 1000) {
            return $this->scan_duration_ms . 'ms';
        }

        return round($this->scan_duration_ms / 1000, 2) . 's';
    }

    public function getFileSizeFormatted(): string
    {
        if (!$this->file_size_bytes) {
            return 'N/A';
        }

        return $this->formatBytes($this->file_size_bytes);
    }

    public function getThreatSummary(): array
    {
        $threats = [];

        if (!empty($this->detected_patterns)) {
            $threats[] = count($this->detected_patterns) . ' sensitive pattern(s)';
        }

        if (!empty($this->detected_keywords)) {
            $threats[] = count($this->detected_keywords) . ' flagged keyword(s)';
        }

        if ($this->ai_classification) {
            $threats[] = 'AI classified as: ' . $this->ai_classification;
        }

        return $threats;
    }

    public function getRecommendedActions(): array
    {
        $actions = [];

        if ($this->isHighRisk()) {
            $actions[] = 'Review file content immediately';
            $actions[] = 'Consider restricting access';
        }

        if ($this->risk_score >= 80) {
            $actions[] = 'Consider quarantining file';
            $actions[] = 'Notify security team';
        }

        if (!empty($this->ai_suggestions)) {
            $actions = array_merge($actions, $this->ai_suggestions);
        }

        if (empty($actions)) {
            $actions[] = 'No immediate action required';
        }

        return array_unique($actions);
    }

    public static function createScan(File $file, array $options = []): self
    {
        return self::create([
            'file_id' => $file->id,
            'policy_id' => $options['policy_id'] ?? null,
            'scan_type' => $options['scan_type'] ?? self::SCAN_UPLOAD,
            'scan_status' => self::STATUS_PENDING,
            'file_size_bytes' => $file->file_size ?? 0,
        ]);
    }

    public function markAsScanning(): bool
    {
        return $this->update([
            'scan_status' => self::STATUS_SCANNING,
        ]);
    }

    public function markAsCompleted(array $results): bool
    {
        $riskLevel = $this->calculateRiskLevel($results['risk_score'] ?? 0);
        
        return $this->update([
            'scan_status' => self::STATUS_COMPLETED,
            'risk_score' => $results['risk_score'] ?? 0,
            'risk_level' => $riskLevel,
            'detected_patterns' => $results['detected_patterns'] ?? [],
            'detected_keywords' => $results['detected_keywords'] ?? [],
            'confidence_score' => $results['confidence_score'] ?? 0,
            'ai_classification' => $results['ai_classification'] ?? null,
            'ai_confidence' => $results['ai_confidence'] ?? 0,
            'ai_suggestions' => $results['ai_suggestions'] ?? [],
            'scan_duration_ms' => $results['scan_duration_ms'] ?? null,
        ]);
    }

    public function markAsFailed(string $error = ''): bool
    {
        return $this->update([
            'scan_status' => self::STATUS_FAILED,
            'review_notes' => $error,
        ]);
    }

    private function calculateRiskLevel(int $score): string
    {
        return match(true) {
            $score >= 80 => self::RISK_CRITICAL,
            $score >= 60 => self::RISK_HIGH,
            $score >= 40 => self::RISK_MEDIUM,
            default => self::RISK_LOW
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' MB';
        } else {
            return round($bytes / 1073741824, 1) . ' GB';
        }
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed fields
        $array['risk_level_color'] = $this->getRiskLevelColor();
        $array['risk_level_icon'] = $this->getRiskLevelIcon();
        $array['status_color'] = $this->getStatusColor();
        $array['status_icon'] = $this->getStatusIcon();
        $array['action_color'] = $this->getActionColor();
        $array['action_icon'] = $this->getActionIcon();
        $array['review_status_color'] = $this->getReviewStatusColor();
        $array['scan_duration_formatted'] = $this->getScanDurationFormatted();
        $array['file_size_formatted'] = $this->getFileSizeFormatted();
        $array['threat_summary'] = $this->getThreatSummary();
        $array['recommended_actions'] = $this->getRecommendedActions();
        $array['file_name'] = $this->file?->file_name;
        $array['policy_name'] = $this->policy?->name;
        $array['reviewed_by_name'] = $this->reviewedBy?->name;
        
        return $array;
    }
}
