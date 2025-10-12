<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class SystemActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_id',
        'target_user_id',
        'activity_type',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'session_id',
        'location_country',
        'location_city',
        'device_type',
        'browser',
        'risk_level',
        'requires_audit',
        'is_suspicious',
    ];

    protected $casts = [
        'metadata' => 'array',
        'requires_audit' => 'boolean',
        'is_suspicious' => 'boolean',
        'created_at' => 'datetime',
    ];

    public $timestamps = ['created_at'];
    const UPDATED_AT = null;

    // Activity types
    const TYPE_FILE = 'file';
    const TYPE_SHARING = 'sharing';
    const TYPE_COMMENT = 'comment';
    const TYPE_SYSTEM = 'system';
    const TYPE_AUTH = 'auth';
    const TYPE_COLLABORATION = 'collaboration';

    // Common actions
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';
    const ACTION_ACCESSED = 'accessed';
    const ACTION_SHARED = 'shared';
    const ACTION_DOWNLOADED = 'downloaded';
    const ACTION_UPLOADED = 'uploaded';
    const ACTION_COMMENTED = 'commented';
    const ACTION_RESTORED = 'restored';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';

    // Risk levels
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByFile($query, $fileId)
    {
        return $query->where('file_id', $fileId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByRiskLevel($query, $riskLevel)
    {
        return $query->where('risk_level', $riskLevel);
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    public function scopeRequiringAudit($query)
    {
        return $query->where('requires_audit', true);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->where('created_at', '>=', now()->startOfWeek());
    }

    public function scopeThisMonth($query)
    {
        return $query->where('created_at', '>=', now()->startOfMonth());
    }

    // Static methods for logging activities
    public static function logFileActivity(
        string $action,
        File $file,
        string $description = null,
        array $metadata = [],
        string $riskLevel = self::RISK_LOW,
        User $user = null
    ): self {
        $user = $user ?: Auth::user();
        
        if (!$user) {
            throw new \Exception('Cannot log activity without authenticated user');
        }

        $description = $description ?: self::generateFileDescription($action, $file, $user);

        return self::create([
            'user_id' => $user->id,
            'file_id' => $file->id,
            'activity_type' => self::TYPE_FILE,
            'action' => $action,
            'entity_type' => $file->is_folder ? 'folder' : 'file',
            'entity_id' => $file->id,
            'description' => $description,
            'metadata' => array_merge([
                'file_name' => $file->file_name,
                'file_size' => $file->file_size,
                'is_folder' => $file->is_folder,
            ], $metadata),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'risk_level' => $riskLevel,
        ]);
    }

    public static function logSharingActivity(
        string $action,
        File $file,
        User $targetUser = null,
        string $description = null,
        array $metadata = [],
        string $riskLevel = self::RISK_LOW
    ): self {
        $user = Auth::user();
        
        if (!$user) {
            throw new \Exception('Cannot log activity without authenticated user');
        }

        $description = $description ?: self::generateSharingDescription($action, $file, $user, $targetUser);

        return self::create([
            'user_id' => $user->id,
            'file_id' => $file->id,
            'target_user_id' => $targetUser?->id,
            'activity_type' => self::TYPE_SHARING,
            'action' => $action,
            'entity_type' => 'share',
            'description' => $description,
            'metadata' => array_merge([
                'file_name' => $file->file_name,
                'target_user_name' => $targetUser?->name,
                'target_user_email' => $targetUser?->email,
            ], $metadata),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'risk_level' => $riskLevel,
        ]);
    }

    /* Commented out until FileComment model is created
    public static function logCommentActivity(
        string $action,
        $comment,
        string $description = null,
        array $metadata = [],
        string $riskLevel = self::RISK_LOW
    ): self {
        $user = Auth::user();
        
        if (!$user) {
            throw new \Exception('Cannot log activity without authenticated user');
        }

        $description = $description ?: self::generateCommentDescription($action, $comment, $user);

        return self::create([
            'user_id' => $user->id,
            'file_id' => $comment->file_id,
            'activity_type' => self::TYPE_COMMENT,
            'action' => $action,
            'entity_type' => 'comment',
            'entity_id' => $comment->id,
            'description' => $description,
            'metadata' => array_merge([
                'comment_type' => $comment->comment_type,
                'file_name' => $comment->file->file_name,
                'is_reply' => !is_null($comment->parent_comment_id),
            ], $metadata),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'risk_level' => $riskLevel,
        ]);
    }
    */

    public static function logAuthActivity(
        string $action,
        string $description = null,
        array $metadata = [],
        string $riskLevel = self::RISK_LOW,
        User $user = null
    ): self {
        $user = $user ?: Auth::user();
        
        // Ensure we have a valid user
        if (!$user || !$user->id) {
            \Log::warning('SystemActivity::logAuthActivity called without valid user', [
                'action' => $action,
                'user_type' => $user ? get_class($user) : 'null',
                'user_id' => $user?->id ?? 'null'
            ]);
            
            // Create a fallback activity without user reference
            return self::create([
                'user_id' => null,
                'activity_type' => self::TYPE_AUTH,
                'action' => $action,
                'entity_type' => 'system',
                'entity_id' => null,
                'description' => $description ?: "System auth activity: {$action}",
                'metadata' => $metadata,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'risk_level' => $riskLevel,
            ]);
        }
        
        $description = $description ?: self::generateAuthDescription($action, $user);

        return self::create([
            'user_id' => $user->id,
            'activity_type' => self::TYPE_AUTH,
            'action' => $action,
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'description' => $description,
            'metadata' => array_merge([
                'user_name' => $user->name,
                'user_email' => $user->email,
            ], $metadata),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'risk_level' => $riskLevel,
        ]);
    }

    public static function logSystemActivity(
        string $action,
        string $description,
        array $metadata = [],
        string $riskLevel = self::RISK_LOW,
        User $user = null
    ): self {
        $user = $user ?: Auth::user();

        return self::create([
            'user_id' => $user?->id,
            'activity_type' => self::TYPE_SYSTEM,
            'action' => $action,
            'entity_type' => 'system',
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'risk_level' => $riskLevel,
        ]);
    }

    // Helper methods for generating descriptions
    private static function generateFileDescription(string $action, File $file, User $user): string
    {
        $entityType = $file->is_folder ? 'folder' : 'file';
        
        return match($action) {
            self::ACTION_CREATED => "{$user->name} created {$entityType} '{$file->file_name}'",
            self::ACTION_UPDATED => "{$user->name} updated {$entityType} '{$file->file_name}'",
            self::ACTION_DELETED => "{$user->name} deleted {$entityType} '{$file->file_name}'",
            self::ACTION_ACCESSED => "{$user->name} accessed {$entityType} '{$file->file_name}'",
            self::ACTION_DOWNLOADED => "{$user->name} downloaded {$entityType} '{$file->file_name}'",
            self::ACTION_UPLOADED => "{$user->name} uploaded {$entityType} '{$file->file_name}'",
            self::ACTION_RESTORED => "{$user->name} restored {$entityType} '{$file->file_name}' from trash",
            default => "{$user->name} performed '{$action}' on {$entityType} '{$file->file_name}'",
        };
    }

    private static function generateSharingDescription(string $action, File $file, User $user, ?User $targetUser): string
    {
        $entityType = $file->is_folder ? 'folder' : 'file';
        
        if ($targetUser) {
            return match($action) {
                'shared' => "{$user->name} shared {$entityType} '{$file->file_name}' with {$targetUser->name}",
                'unshared' => "{$user->name} removed sharing of {$entityType} '{$file->file_name}' from {$targetUser->name}",
                'link_created' => "{$user->name} created a share link for {$entityType} '{$file->file_name}'",
                'link_removed' => "{$user->name} removed share link for {$entityType} '{$file->file_name}'",
                default => "{$user->name} performed '{$action}' sharing action on {$entityType} '{$file->file_name}'",
            };
        } else {
            return match($action) {
                'link_created' => "{$user->name} created a public share link for {$entityType} '{$file->file_name}'",
                'link_removed' => "{$user->name} removed public share link for {$entityType} '{$file->file_name}'",
                default => "{$user->name} performed '{$action}' sharing action on {$entityType} '{$file->file_name}'",
            };
        }
    }

    /* Commented out until FileComment model is created
    private static function generateCommentDescription(string $action, $comment, User $user): string
    {
        $isReply = !is_null($comment->parent_comment_id);
        
        return match($action) {
            self::ACTION_CREATED => $isReply 
                ? "{$user->name} replied to a comment on '{$comment->file->file_name}'"
                : "{$user->name} commented on '{$comment->file->file_name}'",
            self::ACTION_UPDATED => "{$user->name} edited a comment on '{$comment->file->file_name}'",
            self::ACTION_DELETED => "{$user->name} deleted a comment on '{$comment->file->file_name}'",
            'resolved' => "{$user->name} resolved a comment on '{$comment->file->file_name}'",
            'unresolved' => "{$user->name} reopened a comment on '{$comment->file->file_name}'",
            default => "{$user->name} performed '{$action}' on a comment for '{$comment->file->file_name}'",
        };
    }
    */

    private static function generateAuthDescription(string $action, ?User $user): string
    {
        $userName = $user?->name ?? 'Unknown user';
        
        return match($action) {
            self::ACTION_LOGIN => "{$userName} logged in",
            self::ACTION_LOGOUT => "{$userName} logged out",
            'login_failed' => "Failed login attempt for {$userName}",
            'password_changed' => "{$userName} changed their password",
            'profile_updated' => "{$userName} updated their profile",
            default => "{$userName} performed '{$action}' authentication action",
        };
    }

    // Helper methods
    public function getRiskLevelColor(): string
    {
        return match($this->risk_level) {
            self::RISK_LOW => 'green',
            self::RISK_MEDIUM => 'yellow',
            self::RISK_HIGH => 'orange',
            self::RISK_CRITICAL => 'red',
            default => 'gray',
        };
    }

    public function getRiskLevelIcon(): string
    {
        return match($this->risk_level) {
            self::RISK_LOW => '✅',
            self::RISK_MEDIUM => '⚠️',
            self::RISK_HIGH => '🔥',
            self::RISK_CRITICAL => '💥',
            default => '❓',
        };
    }

    public function getActivityTypeIcon(): string
    {
        return match($this->activity_type) {
            self::TYPE_FILE => '📁',
            self::TYPE_SHARING => '🔗',
            self::TYPE_COMMENT => '💬',
            self::TYPE_AUTH => '🔐',
            self::TYPE_SYSTEM => '⚙️',
            self::TYPE_COLLABORATION => '🤝',
            default => '📋',
        };
    }

    public function getTimeAgo(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed fields
        $array['risk_level_color'] = $this->getRiskLevelColor();
        $array['risk_level_icon'] = $this->getRiskLevelIcon();
        $array['activity_type_icon'] = $this->getActivityTypeIcon();
        $array['time_ago'] = $this->getTimeAgo();
        
        return $array;
    }
}
