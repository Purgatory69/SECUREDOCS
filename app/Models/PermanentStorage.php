<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermanentStorage extends Model
{
    use HasFactory;

    protected $table = 'permanent_storage';

    protected $fillable = [
        'user_id',
        'file_name',
        'file_size',
        'mime_type',
        'file_hash',
        'payment_id',
        'payment_status',
        'payment_amount_usd',
        'payment_amount_crypto',
        'payment_token',
        'payment_network',
        'wallet_address',
        'wallet_type',
        'transaction_hash',
        'storage_provider',
        'arweave_transaction_id',
        'arweave_url',
        'storage_status',
        'payment_confirmed_at',
        'uploaded_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'payment_amount_usd' => 'decimal:2',
        'payment_amount_crypto' => 'decimal:6',
        'payment_confirmed_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the permanent storage record
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->file_size;
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if payment is confirmed
     */
    public function isPaymentConfirmed(): bool
    {
        return $this->payment_status === 'confirmed' && !is_null($this->payment_confirmed_at);
    }

    /**
     * Check if file is uploaded to Arweave
     */
    public function isUploaded(): bool
    {
        return $this->storage_status === 'completed' && !is_null($this->arweave_transaction_id);
    }

    /**
     * Get the full Arweave URL
     */
    public function getArweaveUrlAttribute(): ?string
    {
        if ($this->arweave_transaction_id) {
            return "https://arweave.net/{$this->arweave_transaction_id}";
        }
        return null;
    }

    /**
     * Get backup gateway URLs
     */
    public function getGatewayUrls(): array
    {
        if (!$this->arweave_transaction_id) {
            return [];
        }

        return [
            'primary' => "https://arweave.net/{$this->arweave_transaction_id}",
            'backup' => "https://ar-io.net/{$this->arweave_transaction_id}",
            'ipfs_style' => "https://gateway.ar-io.dev/{$this->arweave_transaction_id}"
        ];
    }
}
