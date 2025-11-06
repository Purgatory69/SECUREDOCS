<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class FileOtpSecurity extends Model
{
    use HasFactory;

    protected $table = 'file_otp_security';

    protected $fillable = [
        'user_id',
        'file_id',
        'permanent_storage_id',
        'is_otp_enabled',
        'otp_code',
        'otp_expires_at',
        'otp_attempts',
        'max_otp_attempts',
        'last_otp_sent_at',
        'last_successful_access_at',
        'total_access_count',
        'require_otp_for_download',
        'require_otp_for_preview',
        'require_otp_for_arweave_upload',
        'require_otp_for_ai_share',
        'otp_valid_duration_minutes',
    ];

    protected $casts = [
        'is_otp_enabled' => 'boolean',
        'require_otp_for_download' => 'boolean',
        'require_otp_for_preview' => 'boolean',
        'require_otp_for_arweave_upload' => 'boolean',
        'require_otp_for_ai_share' => 'boolean',
        'otp_attempts' => 'integer',
        'max_otp_attempts' => 'integer',
        'total_access_count' => 'integer',
        'otp_valid_duration_minutes' => 'integer',
        'otp_expires_at' => 'datetime',
        'last_otp_sent_at' => 'datetime',
        'last_successful_access_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns this OTP security record
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the regular file (if applicable)
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Get the permanent storage file (if applicable)
     */
    public function permanentStorage(): BelongsTo
    {
        return $this->belongsTo(PermanentStorage::class);
    }

    /**
     * Generate a new OTP code
     */
    public function generateOtp(): string
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $this->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes($this->otp_valid_duration_minutes),
            'otp_attempts' => 0,
            'last_otp_sent_at' => now(),
        ]);

        return $otp;
    }

    /**
     * Verify OTP code
     */
    public function verifyOtp(string $inputOtp): bool
    {
        // Check if OTP is expired
        if ($this->otp_expires_at && $this->otp_expires_at->isPast()) {
            return false;
        }

        // Check if max attempts exceeded
        if ($this->otp_attempts >= $this->max_otp_attempts) {
            return false;
        }

        // Increment attempts
        $this->increment('otp_attempts');

        // Check if OTP matches
        if ($this->otp_code === $inputOtp) {
            $this->update([
                'last_successful_access_at' => now(),
                'otp_code' => null, // Clear OTP after successful use
                'otp_expires_at' => null,
                'otp_attempts' => 0,
            ]);
            $this->increment('total_access_count');
            return true;
        }

        return false;
    }

    /**
     * Check if OTP is required for the given action
     */
    public function isOtpRequiredFor(string $action): bool
    {
        if (!$this->is_otp_enabled) {
            return false;
        }

        return match($action) {
            'download' => $this->require_otp_for_download,
            'preview' => $this->require_otp_for_preview,
            'arweave_upload' => $this->require_otp_for_arweave_upload,
            'ai_share' => $this->require_otp_for_ai_share,
            default => true
        };
    }

    /**
     * Check if OTP is valid and not expired
     */
    public function hasValidOtp(): bool
    {
        return $this->otp_code && 
               $this->otp_expires_at && 
               $this->otp_expires_at->isFuture() &&
               $this->otp_attempts < $this->max_otp_attempts;
    }

    /**
     * Get the file name (from either regular file or permanent storage)
     */
    public function getFileNameAttribute(): ?string
    {
        if ($this->file) {
            return $this->file->file_name;
        }
        if ($this->permanentStorage) {
            return $this->permanentStorage->file_name;
        }
        return null;
    }

    /**
     * Get remaining OTP attempts
     */
    public function getRemainingAttemptsAttribute(): int
    {
        return max(0, $this->max_otp_attempts - $this->otp_attempts);
    }

    /**
     * Check if OTP can be sent (rate limiting)
     */
    public function canSendOtp(): bool
    {
        if (!$this->last_otp_sent_at) {
            return true;
        }

        // Allow new OTP every 2 minutes
        return $this->last_otp_sent_at->diffInMinutes(now()) >= 2;
    }
}
