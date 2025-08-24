<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'action',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    // Disable updated_at since we only track creation
    const UPDATED_AT = null;

    // Activity types
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';
    const ACTION_RESTORED = 'restored';
    const ACTION_SHARED = 'shared';
    const ACTION_RENAMED = 'renamed';
    const ACTION_MOVED = 'moved';
    const ACTION_VERSION_CREATED = 'version_created';
    const ACTION_VERSION_RESTORED = 'version_restored';
    const ACTION_DOWNLOADED = 'downloaded';
    const ACTION_PREVIEWED = 'previewed';

    // Relationships
    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForFile($query, $fileId)
    {
        return $query->where('file_id', $fileId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeWithUserInfo($query)
    {
        return $query->with(['user:id,name,email']);
    }

    // Helper methods
    public function getFormattedActionAttribute()
    {
        return match($this->action) {
            self::ACTION_CREATED => 'Created file',
            self::ACTION_UPDATED => 'Updated file',
            self::ACTION_DELETED => 'Deleted file',
            self::ACTION_RESTORED => 'Restored file',
            self::ACTION_SHARED => 'Shared file',
            self::ACTION_RENAMED => 'Renamed file',
            self::ACTION_MOVED => 'Moved file',
            self::ACTION_VERSION_CREATED => 'Created new version',
            self::ACTION_VERSION_RESTORED => 'Restored version',
            self::ACTION_DOWNLOADED => 'Downloaded file',
            self::ACTION_PREVIEWED => 'Previewed file',
            default => ucfirst($this->action)
        };
    }

    public function getActionIconAttribute()
    {
        return match($this->action) {
            self::ACTION_CREATED => '📄',
            self::ACTION_UPDATED => '✏️',
            self::ACTION_DELETED => '🗑️',
            self::ACTION_RESTORED => '♻️',
            self::ACTION_SHARED => '🔗',
            self::ACTION_RENAMED => '✍️',
            self::ACTION_MOVED => '📂',
            self::ACTION_VERSION_CREATED => '📋',
            self::ACTION_VERSION_RESTORED => '⏪',
            self::ACTION_DOWNLOADED => '⬇️',
            self::ACTION_PREVIEWED => '👁️',
            default => '📝'
        };
    }

    public function getTimeSinceAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getDetailDescriptionAttribute()
    {
        if (!$this->details || !is_array($this->details)) {
            return null;
        }

        $details = $this->details; // Already cast to array by Laravel
        
        return match($this->action) {
            self::ACTION_VERSION_CREATED => "Version {$details['version_number']} • " . $this->formatFileSize($details['file_size'] ?? 0),
            self::ACTION_SHARED => "Shared with {$details['shared_with_email']} as {$details['permission']}",
            self::ACTION_RENAMED => "From '{$details['old_name']}' to '{$details['new_name']}'",
            self::ACTION_MOVED => "From '{$details['old_path']}' to '{$details['new_path']}'",
            self::ACTION_VERSION_RESTORED => "Restored to version {$details['version_number']}",
            default => null
        };
    }

    /**
     * Log a file activity
     */
    public static function log($fileId, $userId, $action, $details = null, $ipAddress = null, $userAgent = null)
    {
        return static::create([
            'file_id' => $fileId,
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }

    /**
     * Get activity timeline for a file
     */
    public static function getTimelineForFile($fileId, $limit = 20)
    {
        return static::where('file_id', $fileId)
                    ->with(['user:id,name,email'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Get recent activities for a user
     */
    public static function getRecentForUser($userId, $limit = 50)
    {
        return static::where('user_id', $userId)
                    ->with(['file:id,file_name,is_folder'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Format file size in human readable format
     */
    private function formatFileSize($bytes)
    {
        if ($bytes == 0) return '0 Bytes';
        
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));
        
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}
