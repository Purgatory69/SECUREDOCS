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
 * @property string|null $file_type
 * @property string|null $mime_type
 * @property string|null $file_path
 * @property int|null $file_size
 * @property int|null $parent_id
 * @property bool $is_folder
 * @property bool $is_arweave
 * @property string|null $arweave_url
 * @property bool $uploading
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
        'is_arweave',
        'arweave_url',
        'uploading',
        // Legacy fields that may still be used
        'is_permanent_stored',
        'is_vectorized',
        'vectorized_at',
        'is_permanent_storage',
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
        'is_arweave' => 'boolean',
        'uploading' => 'boolean',
        // Legacy casts that may still be needed
        'is_permanent_stored' => 'boolean',
        'is_vectorized' => 'boolean',
        'vectorized_at' => 'datetime',
        'is_permanent_storage' => 'boolean',
        'is_confidential' => 'boolean',
        'confidential_enabled_at' => 'datetime',
    ];
    
    /**
     * Mark file as uploading to Arweave
     */
    public function markAsUploading(): void
    {
        $this->update(['uploading' => true]);
    }

    /**
     * Mark file as successfully uploaded to Arweave
     */
    public function markAsArweaveStored(string $arweaveUrl): void
    {
        $this->update([
            'is_arweave' => true,
            'arweave_url' => $arweaveUrl,
            'uploading' => false
        ]);
    }

    /**
     * Mark file upload as failed
     */
    public function markUploadFailed(): void
    {
        $this->update(['uploading' => false]);
    }

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
     * Check if this file is stored on Arweave.
     */
    public function isStoredOnArweave(): bool
    {
        return $this->is_arweave && !empty($this->arweave_url);
    }

    /**
     * Scope to get only Arweave-stored files.
     */
    public function scopeArweaveStored($query)
    {
        return $query->where('is_arweave', true)
                    ->whereNotNull('arweave_url');
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
     * Scope to get files that can be uploaded to Arweave (not folders, not already on Arweave).
     */
    public function scopeCanBeArweaveStored($query)
    {
        return $query->where('is_folder', false)
                    ->where('is_arweave', false);
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
     * Check if file is permanently stored on Arweave
     */
    public function isPermanentlyStored(): bool
    {
        return $this->is_arweave && !empty($this->arweave_url);
    }

    /**
     * Get file processing status summary.
     */
    public function getProcessingStatus(): array
    {
        return [
            'is_arweave' => $this->is_arweave,
            'arweave_url' => $this->arweave_url,
            'uploading' => $this->uploading,
            'vectorized' => $this->isVectorized(),
            'vectorized_at' => $this->vectorized_at?->format('Y-m-d H:i:s'),
        ];
    }
}
