<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $user_id
 * @property string $file_name
 * @property string|null $mime_type
 * @property string|null $file_path
 * @property int|null $file_size
 * @property int|null $parent_id
 * @property bool $is_folder
 * @property string|null $blockchain_provider
 * @property string|null $ipfs_hash
 * @property string|null $blockchain_url
 * @property bool $is_blockchain_stored
 * @property array|null $blockchain_metadata
 * @property bool $is_vectorized
 * @property \Carbon\Carbon|null $vectorized_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class File extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'file_name',
        'file_type',
        'mime_type',
        'file_path',
        'file_size',
        'parent_id',
        'is_folder',
        'is_blockchain_stored',
        'is_permanent_stored',
        'is_vectorized',
        'vectorized_at',
        'is_permanent_storage',
        'permanent_storage_enabled_at',
        'permanent_storage_enabled_by',
        'is_confidential',
        'confidential_enabled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_folder' => 'boolean',
        'is_blockchain_stored' => 'boolean',
        'is_permanent_stored' => 'boolean',
        'is_vectorized' => 'boolean',
        'vectorized_at' => 'datetime',
        'is_permanent_storage' => 'boolean',
        'permanent_storage_enabled_at' => 'datetime',
        'is_confidential' => 'boolean',
        'confidential_enabled_at' => 'datetime',
    ];
    
    /**
     * Get the user that owns the file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent folder of this file/folder.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(File::class, 'parent_id');
    }

    /**
     * Get the children (files/folders) of this folder.
     */
    public function children()
    {
        return $this->hasMany(File::class, 'parent_id');
    }

    /**
     * Get the blockchain upload records for this file.
     */
    public function blockchainUploads()
    {
        return $this->hasMany(BlockchainUpload::class);
    }

    /**
     * Get the latest successful blockchain upload.
     */
    public function latestBlockchainUpload()
    {
        return $this->hasOne(BlockchainUpload::class)->latest();
    }

    /**
     * Check if this file is stored on blockchain.
     */
    public function isStoredOnBlockchain(): bool
    {
        return $this->is_blockchain_stored && !empty($this->ipfs_hash);
    }

    /**
     * Get the IPFS gateway URL for this file.
     */
    public function getIpfsGatewayUrl(): ?string
    {
        if (!$this->ipfs_hash) {
            return null;
        }

        // Use Pinata's gateway by default, fallback to public gateway
        $gateway = config('blockchain.pinata.gateway_url', 'https://gateway.pinata.cloud');
        return "{$gateway}/ipfs/{$this->ipfs_hash}";
    }

    /**
     * Get blockchain verification URL.
     */
    public function getBlockchainVerificationUrl(): ?string
    {
        if (!$this->ipfs_hash) {
            return null;
        }

        return "https://ipfs.io/ipfs/{$this->ipfs_hash}";
    }

    /**
     * Scope to get only blockchain-stored files.
     */
    public function scopeBlockchainStored($query)
    {
        return $query->whereRaw('is_blockchain_stored IS TRUE')
                    ->where(function($q) {
                        $q->whereNotNull('ipfs_hash')
                          ->orWhereNotNull('arweave_tx_id');
                    });
    }

    /**
     * Scope to get files by blockchain provider.
     */
    public function scopeBlockchainProvider($query, string $provider)
    {
        return $query->where('blockchain_provider', $provider);
    }

    /**
     * Check if this file is vectorized.
     */
    public function isVectorized(): bool
    {
        return $this->is_vectorized && !is_null($this->vectorized_at);
    }

    /**
     * Scope to get only vectorized files.
     */
    public function scopeVectorized($query)
    {
        return $query->whereRaw('is_vectorized IS TRUE')
                    ->whereNotNull('vectorized_at');
    }

    /**
     * Scope to get files that can be vectorized (not folders, not already vectorized).
     */
    public function scopeCanBeVectorized($query)
    {
        return $query->whereRaw('is_folder IS FALSE')
                    ->whereRaw('is_vectorized IS FALSE');
    }

    /**
     * Scope to get files that can be uploaded to blockchain (not folders, not already on blockchain).
     */
    public function scopeCanBeBlockchainStored($query)
    {
        return $query->whereRaw('is_folder IS FALSE')
                    ->whereRaw('is_blockchain_stored IS FALSE');
    }

    /**
     * Mark file as vectorized.
     */
    public function markAsVectorized(array $metadata = []): bool
    {
        return $this->update([
            'is_vectorized' => true,
            'vectorized_at' => now(),
        ]);
    }

    /**
     * Mark file as not vectorized (for removal).
     */
    public function markAsNotVectorized(): bool
    {
        $result = $this->update([
            'is_vectorized' => DB::raw('FALSE'),
            'vectorized_at' => null,
        ]);
        
        Log::info('markAsNotVectorized called', [
            'file_id' => $this->id,
            'update_result' => $result,
            'is_vectorized_before' => $this->getOriginal('is_vectorized'),
            'is_vectorized_after' => $this->fresh()->is_vectorized ?? 'null'
        ]);
        
        return $result;
    }

    /**
     * Get crypto payments for this file
     */
    public function cryptoPayments(): HasMany
    {
        return $this->hasMany(CryptoPayment::class);
    }

    /**
     * Get Arweave transactions for this file
     */
    public function arweaveTransactions(): HasMany
    {
        return $this->hasMany(ArweaveTransaction::class);
    }

    /**
     * Get the latest completed crypto payment
     */
    public function latestCryptoPayment()
    {
        return $this->cryptoPayments()->where('status', 'completed')->latest()->first();
    }

    /**
     * Get the latest Arweave transaction
     */
    public function latestArweaveTransaction()
    {
        return $this->arweaveTransactions()->latest()->first();
    }

    /**
     * Check if file is permanently stored on Arweave
     */
    public function isPermanentlyStored(): bool
    {
        return $this->is_permanent_arweave && !empty($this->arweave_tx_id);
    }

    /**
     * Get file processing status summary.
     */
    public function getProcessingStatus(): array
    {
        return [
            'permanent_stored' => $this->is_permanent_stored,
            'permanent_arweave' => $this->is_permanent_arweave,
            'vectorized' => $this->isVectorized(),
            'storage_provider' => $this->storage_provider,
            'arweave_tx_id' => $this->arweave_tx_id,
            'arweave_url' => $this->arweave_url,
            'arweave_cost_usd' => $this->arweave_cost_usd,
            'vectorized_at' => $this->vectorized_at?->format('Y-m-d H:i:s'),
            'permanent_storage_enabled_at' => $this->permanent_storage_enabled_at?->format('Y-m-d H:i:s'),
        ];
    }
}
