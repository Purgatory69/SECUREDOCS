<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriveActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_id',
        'target_user_id',
        'activity_type',
        'action',
        'description',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'details' => 'array',
        'ip_address' => 'string',
        'created_at' => 'datetime',
    ];

    public $timestamps = ['created_at'];
    const UPDATED_AT = null;

    // Activity types
    const TYPE_DRIVE = 'drive';
    const TYPE_MEMBER = 'member';
    const TYPE_FILE = 'file';
    const TYPE_PERMISSION = 'permission';
    const TYPE_SETTINGS = 'settings';

    // Common actions
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';
    const ACTION_SHARED = 'shared';
    const ACTION_JOINED = 'joined';
    const ACTION_LEFT = 'left';
    const ACTION_ADDED = 'added';
    const ACTION_REMOVED = 'removed';
    const ACTION_ROLE_CHANGED = 'role_changed';
    const ACTION_ARCHIVED = 'archived';
    const ACTION_RESTORED = 'restored';

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
    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
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

    // Helper methods
    public function getActivityIcon(): string
    {
        return match($this->activity_type) {
            self::TYPE_DRIVE => '🏢',
            self::TYPE_MEMBER => '👤',
            self::TYPE_FILE => '📄',
            self::TYPE_PERMISSION => '🔐',
            self::TYPE_SETTINGS => '⚙️',
            default => '📋',
        };
    }

    public function getActionIcon(): string
    {
        return match($this->action) {
            self::ACTION_CREATED => '✅',
            self::ACTION_UPDATED => '✏️',
            self::ACTION_DELETED => '🗑️',
            self::ACTION_SHARED => '🔗',
            self::ACTION_JOINED => '👋',
            self::ACTION_LEFT => '👋',
            self::ACTION_ADDED => '➕',
            self::ACTION_REMOVED => '➖',
            self::ACTION_ROLE_CHANGED => '🔄',
            self::ACTION_ARCHIVED => '📦',
            self::ACTION_RESTORED => '♻️',
            default => '📝',
        };
    }

    public function getTimeAgo(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getFormattedDescription(): string
    {
        // Add emoji prefix to description
        return $this->getActionIcon() . ' ' . $this->description;
    }

    public static function logDriveActivity(
        int $userId,
        string $activityType,
        string $action,
        string $description,
        array $details = [],
        ?int $fileId = null,
        ?int $targetUserId = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'file_id' => $fileId,
            'target_user_id' => $targetUserId,
            'activity_type' => $activityType,
            'action' => $action,
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
        $array['activity_icon'] = $this->getActivityIcon();
        $array['action_icon'] = $this->getActionIcon();
        $array['time_ago'] = $this->getTimeAgo();
        $array['formatted_description'] = $this->getFormattedDescription();
        $array['user_name'] = $this->user?->name;
        $array['target_user_name'] = $this->targetUser?->name;
        $array['file_name'] = $this->file?->file_name;
        
        return $array;
    }
}
