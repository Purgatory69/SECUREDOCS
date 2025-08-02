<?php

namespace App\Models;

use Laragear\WebAuthn\Models\WebAuthnCredential as BaseWebAuthnCredential;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable; // Although not directly used, keeping for context if it's needed elsewhere.
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebAuthnCredential extends BaseWebAuthnCredential
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'webauthn_credentials';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'public_key',
        'secret_key',
        'response',
        'certificates',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'raw_id',
        'type',
        'transports',
        'attestation_format',
        'aaguid',
        'public_key',
        'counter',
        'user_handle',
        'authenticatable_type',
        'authenticatable_id',
        'alias',
        'disabled_at',
        'rp_id',
        'origin',
        'alias',
        'response',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'string',
        'raw_id' => 'string',
        'authenticatable_id' => 'string',
        'transports' => 'array',
        'response' => 'array',
        'certificates' => 'array',
        'disabled_at' => 'datetime',
        'counter' => 'integer',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Ensure ID is set and is a string
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }

            // Always set raw_id from id if not already set
            if (empty($model->raw_id)) {
                $model->raw_id = $model->id;
            }

            // Ensure required fields have default values if not explicitly set
            $model->type = $model->type ?? 'public-key';
            $model->response = $model->response ?? ['type' => 'public-key'];
            $model->rp_id = $model->rp_id ?? config('app.url');
            $model->origin = $model->origin ?? config('app.url');
            $model->attestation_format = $model->attestation_format ?? 'none';
            $model->counter = $model->counter ?? 0;
            $model->aaguid = $model->aaguid ?? '00000000-0000-0000-0000-000000000000';

            // Ensure user_handle is set for UUID-based user IDs
            if (empty($model->user_handle) && !empty($model->authenticatable_id)) {
                $model->user_handle = (string) $model->authenticatable_id;
            }

            // Set user_id from the authenticatable relationship
            if ($model->authenticatable) {
                $model->user_id = $model->authenticatable->id;
                Log::debug('Setting user_id from authenticatable:', ['user_id' => $model->user_id]);
            } else {
                Log::warning('No authenticatable found for WebAuthnCredential');
            }

            // Log the creation attempt for debugging
            Log::debug('Creating WebAuthn credential', [
                'id' => $model->id,
                'raw_id' => $model->raw_id,
                'user_handle' => $model->user_handle,
                'user_id' => $model->user_id,
                'authenticatable_id' => $model->authenticatable_id,
                'authenticatable_type' => $model->authenticatable_type,
                'disabled_at' => $model->disabled_at,
                'rp_id' => $model->rp_id,
                'origin' => $model->origin,
                'alias' => $model->alias,
                'response' => $model->response,
                'certificates' => $model->certificates,
                'counter' => $model->counter,
                'aaguid' => $model->aaguid,
                'public_key' => $model->public_key,
                'transports' => $model->transports,
                'attestation_format' => $model->attestation_format,
                'type' => $model->type,
            ]);
        });

        static::saving(function ($model) {
            // Ensure raw_id is set from id if not already set,
            // or if it becomes empty during an update.
            if (empty($model->raw_id) && !empty($model->id)) {
                $model->raw_id = $model->id;
            }

            // Enforce required fields at the saving stage
            $required = ['id', 'raw_id', 'public_key', 'counter', 'rp_id', 'origin'];
            foreach ($required as $field) {
                if (empty($model->$field)) {
                    Log::error("Missing required field {$field} in WebAuthnCredential", [
                        'fields' => $model->toArray(),
                        'attributes' => $model->getAttributes(),
                    ]);
                    throw new \RuntimeException("Field {$field} cannot be empty for WebAuthnCredential.");
                }
            }
        });
    }

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that should be set to null when empty.
     * This is generally handled by database schema or mutators,
     * but can be useful for explicit nulling before saving.
     *
     * @var array
     */
    protected $nullable = [
        'transports',
        'attestation_format',
        'aaguid',
        'alias',
    ];

    /**
     * The attributes that should be set to their default values.
     * These are applied when new instances are created.
     * Note: Some of these are also handled in the 'creating' boot method for robustness.
     *
     * @var array
     */
    protected $attributes = [
        'type' => 'public-key',
        'counter' => 0,
        'attestation_format' => 'none',
    ];

    /**
     * Set the raw_id attribute, ensuring it's always set from id if empty.
     * This mutator acts as a safeguard.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setRawIdAttribute($value): void
    {
        $this->attributes['raw_id'] = $value ?: ($this->attributes['id'] ?? null);
    }

    /**
     * Set the id attribute, ensuring raw_id is also set if it's currently empty.
     * This mutator acts as a safeguard.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setIdAttribute($value): void
    {
        $this->attributes['id'] = $value;
        if (empty($this->attributes['raw_id'])) {
            $this->attributes['raw_id'] = $value;
        }
    }

    /**
     * Get the user that owns the credential.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        // Assuming 'User' model exists and authenticatable_id is the foreign key.
        return $this->belongsTo(User::class, 'authenticatable_id');
    }

    /**
     * Get the user ID as a string.
     * This accessor provides a convenient way to get the user ID.
     *
     * @return string
     */
    public function getUserIdAttribute(): string
    {
        return (string) $this->authenticatable_id;
    }

    /**
     * Get the authenticatable model that owns the credential.
     * This uses Laravel's polymorphic relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function authenticatable(): MorphTo
    {
        return $this->morphTo('authenticatable', 'authenticatable_type', 'authenticatable_id');
    }

    /**
     * Set a given attribute on the model.
     * Overriding this to handle specific attribute mappings and ignore `user_id` direct setting.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value): mixed
    {
        // Map camelCase to snake_case for database columns
        if ($key === 'rawId') {
            $key = 'raw_id';
        }

        return parent::setAttribute($key, $value);
    }
}