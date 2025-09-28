<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_id',
        'payment_method',
        'currency',
        'amount',
        'amount_usd',
        'service_fee_usd',
        'transaction_hash',
        'wallet_address',
        'status',
        'payment_data',
        'arweave_tx_id',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'amount_usd' => 'decimal:4',
        'service_fee_usd' => 'decimal:4',
        'payment_data' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the payment transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the file associated with the payment
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Get the Arweave transaction associated with this payment
     */
    public function arweaveTransaction(): BelongsTo
    {
        return $this->belongsTo(ArweaveTransaction::class, 'arweave_tx_id', 'tx_id');
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 8) . ' ' . $this->currency;
    }

    /**
     * Get formatted USD amount
     */
    public function getFormattedAmountUsdAttribute(): string
    {
        return '$' . number_format((float) $this->amount_usd, 2);
    }

    /**
     * Get formatted service fee
     */
    public function getFormattedServiceFeeAttribute(): string
    {
        return '$' . number_format((float) $this->service_fee_usd, 2);
    }

    /**
     * Get total amount including service fee
     */
    public function getTotalAmountUsdAttribute(): float
    {
        return $this->amount_usd + $this->service_fee_usd;
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return '$' . number_format($this->total_amount_usd, 2);
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'completed' => 'green',
            'failed' => 'red',
            'refunded' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Scope for completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
