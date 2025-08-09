<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class BlockchainConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'api_key_encrypted',
        'settings',
        'is_active'
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean'
    ];

    protected $hidden = [
        'api_key_encrypted'
    ];

    /**
     * Get the user that owns this blockchain configuration
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the decrypted API key
     */
    public function getApiKeyAttribute(): ?string
    {
        if (!$this->api_key_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_key_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set and encrypt the API key
     */
    public function setApiKeyAttribute(?string $value): void
    {
        if ($value) {
            $this->attributes['api_key_encrypted'] = Crypt::encryptString($value);
        } else {
            $this->attributes['api_key_encrypted'] = null;
        }
    }

    /**
     * Scope to get active configurations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get configurations by provider
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}
