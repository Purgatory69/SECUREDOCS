<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PublicShare extends Model
{
    use HasFactory;

    protected $table = 'public_shares';

    protected $fillable = [
        'user_id',
        'file_id',
        'share_token',
        'share_type',
        'is_one_time',
        'download_count',
        'max_downloads',
        'expires_at',
        'password_protected',
        'password_hash',
    ];

    protected $casts = [
        'is_one_time' => 'boolean',
        'password_protected' => 'boolean',
        'download_count' => 'integer',
        'max_downloads' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who created this share
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shared file
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Get all copies made from this share
     */
    public function copies(): HasMany
    {
        return $this->hasMany(SharedFileCopy::class, 'original_share_id');
    }

    /**
     * Generate a unique share token
     */
    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('share_token', $token)->exists());

        return $token;
    }

    /**
     * Create a new public share
     */
    public static function createShare(File $file, array $options = []): self
    {
        // Use DB::raw for PostgreSQL boolean compatibility
        $isOneTime = ($options['is_one_time'] ?? false) ? 'true' : 'false';
        $passwordProtected = ($options['password_protected'] ?? false) ? 'true' : 'false';
        
        return self::create([
            'user_id' => $file->user_id,
            'file_id' => $file->id,
            'share_token' => self::generateUniqueToken(),
            'share_type' => $file->is_folder ? 'folder' : 'file',
            'is_one_time' => \DB::raw($isOneTime),
            'max_downloads' => $options['max_downloads'] ?? null,
            'expires_at' => $options['expires_at'] ?? null,
            'password_protected' => \DB::raw($passwordProtected),
            'password_hash' => isset($options['password']) ? Hash::make($options['password']) : null,
        ]);
    }

    /**
     * Check if share has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if share is still valid
     */
    public function isValid(): bool
    {
        // Check if expired
        if ($this->isExpired()) {
            return false;
        }

        // Check if one-time link already used
        if ($this->is_one_time && $this->download_count > 0) {
            return false;
        }

        // Check if max downloads reached
        if ($this->max_downloads && $this->download_count >= $this->max_downloads) {
            return false;
        }

        return true;
    }

    /**
     * Check if password is correct
     */
    public function checkPassword(string $password): bool
    {
        if (!$this->password_protected || !$this->password_hash) {
            return true; // No password required
        }

        return Hash::check($password, $this->password_hash);
    }

    /**
     * Increment download count
     */
    public function incrementDownload(): void
    {
        $this->increment('download_count');
    }

    /**
     * Get the public URL for this share
     */
    public function getPublicUrl(): string
    {
        return url("/s/{$this->share_token}");
    }

    /**
     * Check if share has password protection
     */
    public function hasPassword(): bool
    {
        return $this->password_protected && !empty($this->password_hash);
    }

    /**
     * Get share status for display
     */
    public function getStatusAttribute(): string
    {
        if (!$this->isValid()) {
            if ($this->expires_at && $this->expires_at->isPast()) {
                return 'expired';
            }
            if ($this->is_one_time && $this->download_count > 0) {
                return 'used';
            }
            if ($this->max_downloads && $this->download_count >= $this->max_downloads) {
                return 'limit_reached';
            }
        }

        return 'active';
    }

    /**
     * Scope for active shares only
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        })->where(function ($q) {
            $q->where('is_one_time', false)
              ->orWhere('download_count', 0);
        })->where(function ($q) {
            $q->whereNull('max_downloads')
              ->orWhereRaw('download_count < max_downloads');
        });
    }
}
