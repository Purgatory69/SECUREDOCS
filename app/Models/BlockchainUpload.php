<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'provider',
        'ipfs_hash',
        'upload_status',
        'error_message',
        'upload_cost',
        'provider_response'
    ];

    protected $casts = [
        'upload_cost' => 'decimal:4',
        'provider_response' => 'array'
    ];

    /**
     * Get the file that this upload belongs to
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Scope to get successful uploads
     */
    public function scopeSuccessful($query)
    {
        return $query->where('upload_status', 'success');
    }

    /**
     * Scope to get failed uploads
     */
    public function scopeFailed($query)
    {
        return $query->where('upload_status', 'failed');
    }

    /**
     * Scope to get pending uploads
     */
    public function scopePending($query)
    {
        return $query->where('upload_status', 'pending');
    }

    /**
     * Scope to get uploads by provider
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Check if this upload was successful
     */
    public function isSuccessful(): bool
    {
        return $this->upload_status === 'success';
    }

    /**
     * Check if this upload failed
     */
    public function isFailed(): bool
    {
        return $this->upload_status === 'failed';
    }

    /**
     * Check if this upload is pending
     */
    public function isPending(): bool
    {
        return $this->upload_status === 'pending';
    }

    /**
     * Mark upload as successful
     */
    public function markAsSuccessful(string $ipfsHash, array $response = null): void
    {
        $this->update([
            'upload_status' => 'success',
            'ipfs_hash' => $ipfsHash,
            'provider_response' => $response,
            'error_message' => null
        ]);
    }

    /**
     * Mark upload as failed
     */
    public function markAsFailed(string $errorMessage, array $response = null): void
    {
        $this->update([
            'upload_status' => 'failed',
            'error_message' => $errorMessage,
            'provider_response' => $response
        ]);
    }
}
