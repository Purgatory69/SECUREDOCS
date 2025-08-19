<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriveInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'role',
        'invited_by_user_id',
        'invited_user_id',
        'message',
        'status',
        'expires_at',
        'invitation_token',
        'responded_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Invitation statuses
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';
    const STATUS_EXPIRED = 'expired';

    // Relationships

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where(function($q) {
            $q->where('status', self::STATUS_EXPIRED)
              ->orWhere('expires_at', '<=', now());
        });
    }

    public function scopeForEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    // Helper methods
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING && !$this->isExpired();
    }

    public function accept(?User $user = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $user = $user ?? User::where('email', $this->email)->first();
        
        if (!$user) {
            return false;
        }

        // Update invitation status
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'responded_at' => now(),
            'invited_user_id' => $user->id,
        ]);

        return true;
    }

    public function decline(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_DECLINED,
            'responded_at' => now(),
        ]);

        // Log activity (shared drive context removed)
        DriveActivity::logDriveActivity(
            $this->invited_user_id ?? 0,
            DriveActivity::TYPE_MEMBER,
            'invitation_declined',
            "{$this->email} declined invitation",
            [
                'invitation_id' => $this->id,
                'role' => $this->role,
            ]
        );

        return true;
    }

    public function markExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    public function getInvitationUrl(): string
    {
        return url('/drive-invitation/' . $this->invitation_token);
    }

    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            'owner' => 'Owner',
            'admin' => 'Admin',
            'member' => 'Member',
            'viewer' => 'Viewer',
            default => ucfirst((string) $this->role)
        };
    }

    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_EXPIRED => 'Expired',
            default => 'Unknown'
        };
    }

    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_ACCEPTED => 'green',
            self::STATUS_DECLINED => 'red',
            self::STATUS_EXPIRED => 'gray',
            default => 'gray'
        };
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed fields
        $array['is_expired'] = $this->isExpired();
        $array['is_pending'] = $this->isPending();
        $array['role_display_name'] = $this->getRoleDisplayName();
        $array['status_display_name'] = $this->getStatusDisplayName();
        $array['status_badge_color'] = $this->getStatusBadgeColor();
        $array['invitation_url'] = $this->getInvitationUrl();
        $array['invited_by_name'] = $this->invitedBy?->name;
        // drive_name removed to avoid dependency on SharedDrive model
        
        return $array;
    }
}
