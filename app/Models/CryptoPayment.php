<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CryptoPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'wallet_address',
        'amount_usd',
        'amount_crypto',
        'token_symbol',
        'network',
        'chain_id',
        'status',
        'tx_hash',
        'actual_amount_received',
        'confirmed_at',
        'expires_at',
        'arweave_tx_id',
        'arweave_url',
        'upload_status',
        'cost_breakdown',
        'payment_metadata',
    ];

    protected $casts = [
        'amount_usd' => 'decimal:4',
        'amount_crypto' => 'decimal:8',
        'actual_amount_received' => 'decimal:8',
        'confirmed_at' => 'datetime',
        'expires_at' => 'datetime',
        'cost_breakdown' => 'array',
        'payment_metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the file associated with this payment
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Get the user who made this payment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the associated Arweave transaction
     */
    public function arweaveTransaction(): HasOne
    {
        return $this->hasOne(ArweaveTransaction::class, 'crypto_payment_id');
    }

    /**
     * Check if payment is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
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
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Get the blockchain explorer URL for this transaction
     */
    public function getExplorerUrlAttribute(): ?string
    {
        if (!$this->tx_hash) {
            return null;
        }

        $explorers = [
            'ethereum' => 'https://etherscan.io/tx/',
            'polygon' => 'https://polygonscan.com/tx/',
            'bsc' => 'https://bscscan.com/tx/',
            'ronin' => 'https://explorer.roninchain.com/tx/',
        ];

        $baseUrl = $explorers[$this->network] ?? null;
        return $baseUrl ? $baseUrl . $this->tx_hash : null;
    }

    /**
     * Get the token display name
     */
    public function getTokenDisplayNameAttribute(): string
    {
        $tokens = [
            'USDC' => 'USD Coin',
            'USDT' => 'Tether',
            'ETH' => 'Ethereum',
            'BNB' => 'Binance Coin',
            'RON' => 'Ronin',
            'AXS' => 'Axie Infinity Shard',
        ];

        return $tokens[$this->token_symbol] ?? $this->token_symbol;
    }

    /**
     * Get the network display name
     */
    public function getNetworkDisplayNameAttribute(): string
    {
        $networks = [
            'ethereum' => 'Ethereum',
            'polygon' => 'Polygon',
            'bsc' => 'Binance Smart Chain',
            'ronin' => 'Ronin',
        ];

        return $networks[$this->network] ?? ucfirst($this->network);
    }

    /**
     * Scope to get payments by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope to get completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get expired payments
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
                    ->where('expires_at', '<=', now());
    }

    /**
     * Scope to get payments by network
     */
    public function scopeByNetwork($query, string $network)
    {
        return $query->where('network', $network);
    }

    /**
     * Scope to get payments by token
     */
    public function scopeByToken($query, string $token)
    {
        return $query->where('token_symbol', $token);
    }

    /**
     * Get payments for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get recent payments (last 30 days)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }
}
