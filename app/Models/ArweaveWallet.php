<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArweaveWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_address',
        'encrypted_jwk',
        'balance_ar',
        'balance_usd',
        'last_balance_check',
        'is_active',
    ];

    protected $casts = [
        'balance_ar' => 'decimal:12',
        'balance_usd' => 'decimal:4',
        'last_balance_check' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the wallet
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this wallet
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(ArweaveTransaction::class, 'wallet_address', 'wallet_address');
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance_ar, 6) . ' AR (~$' . number_format($this->balance_usd, 2) . ')';
    }

    /**
     * Check if wallet has sufficient balance
     */
    public function hasSufficientBalance(float $requiredAR): bool
    {
        return $this->balance_ar >= $requiredAR;
    }
}
