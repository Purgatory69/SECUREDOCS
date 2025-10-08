<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArweaveTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'crypto_payment_id',
        'tx_id',
        'arweave_tx_id', // Client-side field
        'arweave_url',   // Client-side field
        'wallet_address',
        'tx_type',
        'status',
        'data_size',
        'file_name',     // Client-side field
        'file_size',     // Client-side field
        'mime_type',     // Client-side field
        'fee_ar',
        'fee_usd',
        'upload_cost',   // Client-side field
        'bundlr_balance', // Client-side field
        'block_height',
        'block_hash',
        'confirmations',
        'tx_metadata',
        'bundler_response',
        'submitted_at',
        'confirmed_at',
        'uploaded_at',   // Client-side field
    ];

    protected $casts = [
        'data_size' => 'integer',
        'file_size' => 'integer', // Client-side
        'fee_ar' => 'decimal:12',
        'fee_usd' => 'decimal:4',
        'upload_cost' => 'decimal:8', // Client-side
        'bundlr_balance' => 'decimal:8', // Client-side
        'block_height' => 'integer',
        'confirmations' => 'integer',
        'tx_metadata' => 'array',
        'bundler_response' => 'array',
        'submitted_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'uploaded_at' => 'datetime', // Client-side
    ];

    /**
     * Get the file associated with this transaction
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Get the user who initiated this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the associated crypto payment
     */
    public function cryptoPayment(): BelongsTo
    {
        return $this->belongsTo(CryptoPayment::class);
    }

    /**
     * Check if transaction is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed' && $this->block_height !== null;
    }

    /**
     * Get Arweave explorer URL
     */
    public function getExplorerUrlAttribute(): string
    {
        return "https://viewblock.io/arweave/tx/{$this->tx_id}";
    }

    /**
     * Get gateway URL for accessing the data
     */
    public function getGatewayUrlAttribute(): string
    {
        return "https://arweave.net/{$this->tx_id}";
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->data_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'confirmed' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            default => 'gray'
        };
    }
}
