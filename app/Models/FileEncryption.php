<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileEncryption extends Model
{
    use HasFactory;

    protected $table = 'file_encryption';

    protected $fillable = [
        'file_id',
        'encryption_algorithm',
        'key_id',
        'iv_base64',
        'encrypted_by_user_id',
        'access_level',
        'key_rotation_count',
        'last_key_rotation',
        'next_key_rotation',
        'decryption_count',
        'last_decrypted_at',
        'last_decrypted_by_user_id',
        'audit_trail',
    ];

    protected $casts = [
        'last_key_rotation' => 'datetime',
        'next_key_rotation' => 'datetime',
        'last_decrypted_at' => 'datetime',
        'audit_trail' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Encryption algorithms
    const ALGORITHM_AES_256 = 'AES-256';
    const ALGORITHM_AES_192 = 'AES-192';
    const ALGORITHM_AES_128 = 'AES-128';

    // Access levels
    const ACCESS_PUBLIC = 'public';
    const ACCESS_INTERNAL = 'internal';
    const ACCESS_CONFIDENTIAL = 'confidential';
    const ACCESS_RESTRICTED = 'restricted';
    const ACCESS_TOP_SECRET = 'top_secret';

    // Relationships
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function encryptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encrypted_by_user_id');
    }

    public function lastDecryptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_decrypted_by_user_id');
    }

    // Scopes
    public function scopeByAccessLevel($query, $level)
    {
        return $query->where('access_level', $level);
    }

    public function scopeHighSecurity($query)
    {
        return $query->whereIn('access_level', [
            self::ACCESS_RESTRICTED,
            self::ACCESS_TOP_SECRET
        ]);
    }

    public function scopeNeedsKeyRotation($query)
    {
        return $query->where('next_key_rotation', '<=', now());
    }

    public function scopeRecentlyAccessed($query, $hours = 24)
    {
        return $query->where('last_decrypted_at', '>=', now()->subHours($hours));
    }

    // Helper methods
    public function needsKeyRotation(): bool
    {
        return $this->next_key_rotation && $this->next_key_rotation->isPast();
    }

    public function isHighSecurity(): bool
    {
        return in_array($this->access_level, [
            self::ACCESS_RESTRICTED,
            self::ACCESS_TOP_SECRET
        ]);
    }

    public function logDecryption(User $user, array $context = []): void
    {
        // Update decryption tracking
        $this->update([
            'decryption_count' => $this->decryption_count + 1,
            'last_decrypted_at' => now(),
            'last_decrypted_by_user_id' => $user->id,
        ]);

        // Add to audit trail
        $auditEntry = [
            'action' => 'decrypted',
            'user_id' => $user->id,
            'user_name' => trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')),
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context' => $context,
        ];

        $auditTrail = $this->audit_trail ?? [];
        $auditTrail[] = $auditEntry;

        // Keep only last 100 audit entries
        if (count($auditTrail) > 100) {
            $auditTrail = array_slice($auditTrail, -100);
        }

        $this->update(['audit_trail' => $auditTrail]);
    }

    public function rotateKey(): bool
    {
        try {
            // Generate new key ID (would interface with key management system)
            $newKeyId = $this->generateNewKeyId();
            $newIv = $this->generateNewIv();

            $this->update([
                'key_id' => $newKeyId,
                'iv_base64' => $newIv,
                'key_rotation_count' => $this->key_rotation_count + 1,
                'last_key_rotation' => now(),
                'next_key_rotation' => $this->calculateNextRotation(),
            ]);

            // Log key rotation
            $this->logKeyRotation();

            return true;
        } catch (\Exception $e) {
            \Log::error('Key rotation failed', [
                'file_encryption_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getAccessLevelColor(): string
    {
        return match($this->access_level) {
            self::ACCESS_TOP_SECRET => 'red',
            self::ACCESS_RESTRICTED => 'orange',
            self::ACCESS_CONFIDENTIAL => 'yellow',
            self::ACCESS_INTERNAL => 'blue',
            self::ACCESS_PUBLIC => 'green',
            default => 'gray'
        };
    }

    public function getAccessLevelIcon(): string
    {
        return match($this->access_level) {
            self::ACCESS_TOP_SECRET => '🔴',
            self::ACCESS_RESTRICTED => '🟠',
            self::ACCESS_CONFIDENTIAL => '🟡',
            self::ACCESS_INTERNAL => '🔵',
            self::ACCESS_PUBLIC => '🟢',
            default => '⚪'
        };
    }

    public function getAccessLevelName(): string
    {
        return match($this->access_level) {
            self::ACCESS_TOP_SECRET => 'Top Secret',
            self::ACCESS_RESTRICTED => 'Restricted',
            self::ACCESS_CONFIDENTIAL => 'Confidential',
            self::ACCESS_INTERNAL => 'Internal',
            self::ACCESS_PUBLIC => 'Public',
            default => 'Unknown'
        };
    }

    public function getKeyRotationStatus(): array
    {
        if (!$this->next_key_rotation) {
            return [
                'status' => 'none',
                'message' => 'No rotation scheduled',
                'color' => 'gray'
            ];
        }

        $daysUntilRotation = now()->diffInDays($this->next_key_rotation, false);

        if ($daysUntilRotation < 0) {
            return [
                'status' => 'overdue',
                'message' => 'Rotation overdue',
                'color' => 'red',
                'days' => abs($daysUntilRotation)
            ];
        } elseif ($daysUntilRotation <= 7) {
            return [
                'status' => 'due_soon',
                'message' => 'Due in ' . $daysUntilRotation . ' days',
                'color' => 'yellow',
                'days' => $daysUntilRotation
            ];
        } else {
            return [
                'status' => 'scheduled',
                'message' => 'Due in ' . $daysUntilRotation . ' days',
                'color' => 'green',
                'days' => $daysUntilRotation
            ];
        }
    }

    public function getRecentAuditEntries(int $limit = 10): array
    {
        $auditTrail = $this->audit_trail ?? [];
        return array_slice(array_reverse($auditTrail), 0, $limit);
    }

    public static function createForFile(File $file, User $user, array $options = []): self
    {
        $accessLevel = $options['access_level'] ?? self::ACCESS_INTERNAL;
        $algorithm = $options['algorithm'] ?? self::ALGORITHM_AES_256;
        $keyRotationDays = $options['key_rotation_days'] ?? 90;

        return self::create([
            'file_id' => $file->id,
            'encryption_algorithm' => $algorithm,
            'key_id' => self::generateKeyId(),
            'iv_base64' => self::generateIv(),
            'encrypted_by_user_id' => $user->id,
            'access_level' => $accessLevel,
            'last_key_rotation' => now(),
            'next_key_rotation' => now()->addDays($keyRotationDays),
            'audit_trail' => [[
                'action' => 'encrypted',
                'user_id' => $user->id,
                'user_name' => trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')),
                'timestamp' => now()->toISOString(),
                'ip_address' => request()->ip(),
                'algorithm' => $algorithm,
                'access_level' => $accessLevel,
            ]],
        ]);
    }

    private function generateNewKeyId(): string
    {
        // In a real implementation, this would interface with a key management system
        return 'key_' . now()->timestamp . '_' . bin2hex(random_bytes(8));
    }

    private function generateNewIv(): string
    {
        // Generate a new initialization vector
        return base64_encode(random_bytes(16));
    }

    private static function generateKeyId(): string
    {
        return 'key_' . now()->timestamp . '_' . bin2hex(random_bytes(8));
    }

    private static function generateIv(): string
    {
        return base64_encode(random_bytes(16));
    }

    private function calculateNextRotation(): \DateTime
    {
        $rotationDays = match($this->access_level) {
            self::ACCESS_TOP_SECRET => 30,
            self::ACCESS_RESTRICTED => 60,
            self::ACCESS_CONFIDENTIAL => 90,
            default => 365
        };

        return now()->addDays($rotationDays);
    }

    private function logKeyRotation(): void
    {
        $authUser = auth()->user();
        $userName = $authUser ? trim(($authUser->firstname ?? '') . ' ' . ($authUser->lastname ?? '')) : 'System';
        $auditEntry = [
            'action' => 'key_rotated',
            'user_id' => auth()->id(),
            'user_name' => $userName,
            'timestamp' => now()->toISOString(),
            'rotation_count' => $this->key_rotation_count,
            'next_rotation' => $this->next_key_rotation->toISOString(),
        ];

        $auditTrail = $this->audit_trail ?? [];
        $auditTrail[] = $auditEntry;

        if (count($auditTrail) > 100) {
            $auditTrail = array_slice($auditTrail, -100);
        }

        $this->update(['audit_trail' => $auditTrail]);

        // Also log as a system activity
        \DB::table('system_activities')->insert([
            'user_id' => auth()->id(),
            'file_id' => $this->file_id,
            'activity_type' => 'security',
            'action' => 'key_rotated',
            'description' => "Encryption key rotated for file",
            'entity_type' => 'file_encryption',
            'entity_id' => $this->id,
            'risk_level' => 'low',
            'created_at' => now(),
        ]);
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed fields
        $array['access_level_color'] = $this->getAccessLevelColor();
        $array['access_level_icon'] = $this->getAccessLevelIcon();
        $array['access_level_name'] = $this->getAccessLevelName();
        $array['key_rotation_status'] = $this->getKeyRotationStatus();
        $array['needs_key_rotation'] = $this->needsKeyRotation();
        $array['is_high_security'] = $this->isHighSecurity();
        $array['recent_audit_entries'] = $this->getRecentAuditEntries(5);
        $array['encrypted_by_name'] = $this->encryptedBy ? trim(($this->encryptedBy->firstname ?? '') . ' ' . ($this->encryptedBy->lastname ?? '')) : null;
        $array['last_decrypted_by_name'] = $this->lastDecryptedBy ? trim(($this->lastDecryptedBy->firstname ?? '') . ' ' . ($this->lastDecryptedBy->lastname ?? '')) : null;
        $array['file_name'] = $this->file?->file_name;
        
        return $array;
    }
}
