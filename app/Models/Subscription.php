<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_name',
        'status',
        'amount',
        'currency',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'auto_renew',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'auto_renew' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payments for this subscription
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at > now();
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->ends_at <= now();
    }

    /**
     * Check if subscription is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpirationAttribute(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return now()->diffInDays($this->ends_at);
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₱' . number_format((float) $this->amount, 2);
    }

    /**
     * Get plan display name
     */
    public function getPlanDisplayAttribute(): string
    {
        return match($this->plan_name) {
            'premium' => 'Premium',
            'basic' => 'Basic',
            default => ucfirst($this->plan_name)
        };
    }

    /**
     * Get billing cycle display
     */
    public function getBillingCycleDisplayAttribute(): string
    {
        return match($this->billing_cycle) {
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
            'lifetime' => 'Lifetime',
            default => ucfirst($this->billing_cycle)
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'text-green-600',
            'pending' => 'text-yellow-600',
            'cancelled' => 'text-red-600',
            'expired' => 'text-gray-600',
            default => 'text-gray-600'
        };
    }

    /**
     * Get status icon for UI
     */
    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            'active' => '✅',
            'pending' => '⏳',
            'cancelled' => '🚫',
            'expired' => '⏰',
            default => 'ℹ️'
        };
    }

    /**
     * Renew subscription
     */
    public function renew(): void
    {
        $newEndDate = match($this->billing_cycle) {
            'monthly' => $this->ends_at->addMonth(),
            'yearly' => $this->ends_at->addYear(),
            default => $this->ends_at->addMonth()
        };

        $this->update([
            'ends_at' => $newEndDate,
            'status' => 'active'
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'auto_renew' => false
        ]);
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('ends_at', '>', now());
    }

    /**
     * Scope for expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<=', now());
    }
}
