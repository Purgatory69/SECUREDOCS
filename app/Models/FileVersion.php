<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'version_number',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'mime_type',
        'checksum',
        'upload_source',
        'version_comment',
        'is_current',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version_number' => 'integer',
        'is_current' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeForFile($query, $fileId)
    {
        return $query->where('file_id', $fileId);
    }

    public function scopeByVersion($query, $versionNumber)
    {
        return $query->where('version_number', $versionNumber);
    }

    public function scopeOrderedByVersion($query, $direction = 'desc')
    {
        return $query->orderBy('version_number', $direction);
    }

    // Helper methods
    public function getFormattedSizeAttribute()
    {
        return $this->formatFileSize($this->file_size);
    }

    public function getVersionLabelAttribute()
    {
        return "v{$this->version_number}";
    }

    public function getIsLatestAttribute()
    {
        $latestVersion = static::where('file_id', $this->file_id)
            ->max('version_number');
        
        return $this->version_number == $latestVersion;
    }

    public function getTimeSinceCreatedAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Generate a secure download URL for this version
     */
    public function getDownloadUrl()
    {
        // You might want to implement signed URLs for security
        return route('files.version.download', [
            'file' => $this->file_id,
            'version' => $this->version_number
        ]);
    }

    /**
     * Check if this version can be restored
     */
    public function canRestore()
    {
        return !$this->is_current && $this->file->exists();
    }

    /**
     * Get the storage path for this version
     */
    public function getStoragePath()
    {
        return $this->file_path;
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

    /**
     * Create a new version from uploaded file
     */
    public static function createFromUpload($file, $uploadedFile, $userId, $comment = null)
    {
        $latestVersion = static::where('file_id', $file->id)->max('version_number') ?? 0;
        $nextVersion = $latestVersion + 1;

        // Mark previous current version as not current
        static::where('file_id', $file->id)
              ->where('is_current', true)
              ->update(['is_current' => false]);

        return static::create([
            'file_id' => $file->id,
            'user_id' => $userId,
            'version_number' => $nextVersion,
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $file->file_path, // Will be updated after storage
            'file_size' => $uploadedFile->getSize(),
            'file_type' => $uploadedFile->getClientOriginalExtension(),
            'mime_type' => $uploadedFile->getMimeType(),
            'checksum' => hash_file('sha256', $uploadedFile->getPathname()),
            'upload_source' => 'web',
            'version_comment' => $comment,
            'is_current' => true,
        ]);
    }

    /**
     * Restore this version as the current version
     */
    public function restore()
    {
        if (!$this->canRestore()) {
            throw new \Exception('This version cannot be restored');
        }

        // Mark all versions as not current
        static::where('file_id', $this->file_id)
              ->update(['is_current' => false]);

        // Mark this version as current
        $this->update(['is_current' => true]);

        // Update the main file record
        $this->file->update([
            'file_path' => $this->file_path,
            'file_size' => $this->file_size,
            'file_type' => $this->file_type,
            'mime_type' => $this->mime_type,
            'updated_at' => now(),
        ]);

        return true;
    }
}
