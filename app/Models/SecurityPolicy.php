<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'scope',
        'scope_id',
        'ip_whitelist',
        'ip_blacklist',
        'allowed_countries',
        'blocked_countries',
        'access_schedule',
        'timezone',
        'require_2fa',
        'require_device_approval',
        'max_concurrent_sessions',
        'session_timeout_minutes',
        'allow_download',
        'allow_copy',
        'allow_print',
        'allow_screenshot',
        'watermark_enabled',
        'watermark_text',
        'dlp_enabled',
        'dlp_patterns',
        'dlp_keywords',
        'dlp_action',
        'encryption_required',
        'encryption_algorithm',
        'key_rotation_days',
        'audit_level',
        'retention_days',
        'is_active',
        'enforced_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'ip_whitelist' => 'array',
        'ip_blacklist' => 'array',
        'allowed_countries' => 'array',
        'blocked_countries' => 'array',
        'access_schedule' => 'array',
        'dlp_patterns' => 'array',
        'dlp_keywords' => 'array',
        'require_2fa' => 'boolean',
        'require_device_approval' => 'boolean',
        'allow_download' => 'boolean',
        'allow_copy' => 'boolean',
        'allow_print' => 'boolean',
        'allow_screenshot' => 'boolean',
        'watermark_enabled' => 'boolean',
        'dlp_enabled' => 'boolean',
        'encryption_required' => 'boolean',
        'is_active' => 'boolean',
        'enforced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Policy types
    const TYPE_ACCESS_CONTROL = 'access_control';
    const TYPE_DLP = 'dlp';
    const TYPE_ENCRYPTION = 'encryption';
    const TYPE_AUDIT = 'audit';

    // Policy scopes
    const SCOPE_GLOBAL = 'global';
    const SCOPE_TEAM = 'team';
    const SCOPE_USER = 'user';
    const SCOPE_FOLDER = 'folder';
    const SCOPE_FILE = 'file';

    // DLP actions
    const DLP_ACTION_WARN = 'warn';
    const DLP_ACTION_BLOCK = 'block';
    const DLP_ACTION_QUARANTINE = 'quarantine';

    // Audit levels
    const AUDIT_NONE = 'none';
    const AUDIT_BASIC = 'basic';
    const AUDIT_STANDARD = 'standard';
    const AUDIT_DETAILED = 'detailed';

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function violations(): HasMany
    {
        return $this->hasMany(SecurityViolation::class, 'policy_id');
    }

    public function dlpScanResults(): HasMany
    {
        return $this->hasMany(DlpScanResult::class, 'policy_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByScope($query, $scope, $scopeId = null)
    {
        $query = $query->where('scope', $scope);
        
        if ($scopeId !== null) {
            $query->where('scope_id', $scopeId);
        }
        
        return $query;
    }

    public function scopeGlobal($query)
    {
        return $query->where('scope', self::SCOPE_GLOBAL);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('scope', self::SCOPE_GLOBAL)
              ->orWhere(function($subQ) use ($userId) {
                  $subQ->where('scope', self::SCOPE_USER)
                       ->where('scope_id', $userId);
              });
        });
    }

    public function scopeForFile($query, $fileId)
    {
        return $query->where(function($q) use ($fileId) {
            $q->where('scope', self::SCOPE_GLOBAL)
              ->orWhere(function($subQ) use ($fileId) {
                  $subQ->where('scope', self::SCOPE_FILE)
                       ->where('scope_id', $fileId);
              });
        });
    }

    // Helper methods
    public function isApplicableToUser(User $user, $context = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check scope
        switch ($this->scope) {
            case self::SCOPE_GLOBAL:
                break;
            case self::SCOPE_USER:
                if ($this->scope_id !== $user->id) {
                    return false;
                }
                break;
            case self::SCOPE_TEAM:
                if (!$user->belongsToTeam($this->scope_id)) {
                    return false;
                }
                break;
            case self::SCOPE_FILE:
                $fileId = $context['file_id'] ?? null;
                if ($this->scope_id !== $fileId) {
                    return false;
                }
                break;
            case self::SCOPE_FOLDER:
                $folderId = $context['folder_id'] ?? null;
                if ($this->scope_id !== $folderId) {
                    return false;
                }
                break;
            default:
                return false;
        }

        return true;
    }

    public function checkIpAccess(string $ipAddress): bool
    {
        // Check IP whitelist
        if (!empty($this->ip_whitelist)) {
            if (!$this->isIpInList($ipAddress, $this->ip_whitelist)) {
                return false;
            }
        }

        // Check IP blacklist
        if (!empty($this->ip_blacklist)) {
            if ($this->isIpInList($ipAddress, $this->ip_blacklist)) {
                return false;
            }
        }

        return true;
    }

    public function checkCountryAccess(string $countryCode): bool
    {
        // Check allowed countries
        if (!empty($this->allowed_countries)) {
            if (!in_array($countryCode, $this->allowed_countries)) {
                return false;
            }
        }

        // Check blocked countries
        if (!empty($this->blocked_countries)) {
            if (in_array($countryCode, $this->blocked_countries)) {
                return false;
            }
        }

        return true;
    }

    public function checkTimeAccess(\DateTime $dateTime = null): bool
    {
        if (empty($this->access_schedule)) {
            return true;
        }

        $dateTime = $dateTime ?? new \DateTime();
        $dayOfWeek = strtolower($dateTime->format('l')); // monday, tuesday, etc.
        $currentTime = $dateTime->format('H:i');

        $schedule = $this->access_schedule;
        
        if (!isset($schedule[$dayOfWeek])) {
            return false; // No access allowed on this day
        }

        $daySchedule = $schedule[$dayOfWeek];
        
        if ($daySchedule === false || $daySchedule === 'blocked') {
            return false;
        }

        if ($daySchedule === true || $daySchedule === 'allowed') {
            return true;
        }

        // Check time ranges
        if (is_array($daySchedule)) {
            foreach ($daySchedule as $timeRange) {
                if (isset($timeRange['start']) && isset($timeRange['end'])) {
                    if ($currentTime >= $timeRange['start'] && $currentTime <= $timeRange['end']) {
                        return true;
                    }
                }
            }
            return false;
        }

        return true;
    }

    public function checkDlpContent(string $content): array
    {
        if (!$this->dlp_enabled) {
            return ['matches' => [], 'risk_score' => 0];
        }

        $matches = [];
        $riskScore = 0;

        // Check patterns
        if (!empty($this->dlp_patterns)) {
            foreach ($this->dlp_patterns as $pattern) {
                if (preg_match($pattern, $content, $patternMatches)) {
                    $matches[] = [
                        'type' => 'pattern',
                        'pattern' => $pattern,
                        'matches' => $patternMatches,
                    ];
                    $riskScore += 20;
                }
            }
        }

        // Check keywords
        if (!empty($this->dlp_keywords)) {
            foreach ($this->dlp_keywords as $keyword) {
                if (stripos($content, $keyword) !== false) {
                    $matches[] = [
                        'type' => 'keyword',
                        'keyword' => $keyword,
                    ];
                    $riskScore += 10;
                }
            }
        }

        return [
            'matches' => $matches,
            'risk_score' => min($riskScore, 100),
            'action' => $this->dlp_action,
        ];
    }

    public function getFilePermissions(): array
    {
        return [
            'download' => $this->allow_download,
            'copy' => $this->allow_copy,
            'print' => $this->allow_print,
            'screenshot' => $this->allow_screenshot,
            'watermark' => $this->watermark_enabled,
        ];
    }

    public function getScopeDisplayName(): string
    {
        return match($this->scope) {
            self::SCOPE_GLOBAL => 'Global',
            self::SCOPE_TEAM => 'Team',
            self::SCOPE_USER => 'User',
            self::SCOPE_FOLDER => 'Folder',
            self::SCOPE_FILE => 'File',
            default => 'Unknown'
        };
    }

    public function enforce(): void
    {
        $this->update([
            'enforced_at' => now(),
            'is_active' => true,
        ]);
    }

    public function disable(): void
    {
        $this->update([
            'is_active' => false,
        ]);
    }

    private function isIpInList(string $ipAddress, array $ipList): bool
    {
        foreach ($ipList as $range) {
            if ($this->ipMatchesRange($ipAddress, $range)) {
                return true;
            }
        }
        return false;
    }

    private function ipMatchesRange(string $ip, string $range): bool
    {
        // Handle single IP
        if (filter_var($range, FILTER_VALIDATE_IP)) {
            return $ip === $range;
        }

        // Handle CIDR notation
        if (strpos($range, '/') !== false) {
            list($subnet, $mask) = explode('/', $range);
            
            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $subnet = ip2long($subnet);
                $ip = ip2long($ip);
                $mask = ~((1 << (32 - $mask)) - 1);
                
                return ($ip & $mask) === ($subnet & $mask);
            }
        }

        // Handle wildcard (basic implementation)
        if (strpos($range, '*') !== false) {
            $pattern = str_replace('*', '.*', preg_quote($range, '/'));
            return preg_match("/^{$pattern}$/", $ip);
        }

        return false;
    }

    public static function getDefaultGlobalPolicy(): array
    {
        return [
            'name' => 'Default Security Policy',
            'description' => 'Basic security requirements for all users',
            'scope' => self::SCOPE_GLOBAL,
            'require_2fa' => false,
            'max_concurrent_sessions' => 5,
            'session_timeout_minutes' => 480, // 8 hours
            'allow_download' => true,
            'allow_copy' => true,
            'allow_print' => true,
            'allow_screenshot' => true,
            'watermark_enabled' => false,
            'dlp_enabled' => false,
            'dlp_action' => self::DLP_ACTION_WARN,
            'encryption_required' => false,
            'audit_level' => self::AUDIT_STANDARD,
            'retention_days' => 365,
            'is_active' => true,
        ];
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed fields
        $array['scope_display_name'] = $this->getScopeDisplayName();
        $array['file_permissions'] = $this->getFilePermissions();
        $array['created_by_name'] = $this->createdBy?->name;
        $array['violations_count'] = $this->violations()->count();
        
        return $array;
    }
}
