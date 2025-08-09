<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'mime_type',
        'file_path',
        'file_size',
        'parent_id',
        'is_folder',
        'blockchain_provider',
        'ipfs_hash',
        'blockchain_url',
        'is_blockchain_stored',
        'blockchain_metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_folder' => 'boolean',
        'is_blockchain_stored' => 'boolean',
        'blockchain_metadata' => 'array',
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
        return $this->hasOne(BlockchainUpload::class)->latestOfMany()->where('upload_status', 'success');
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
        return $query->where('is_blockchain_stored', true);
    }

    /**
     * Scope to get files by blockchain provider.
     */
    public function scopeBlockchainProvider($query, string $provider)
    {
        return $query->where('blockchain_provider', $provider);
    }
}
