<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedFileCopy extends Model
{
    use HasFactory;

    protected $table = 'shared_file_copies';

    protected $fillable = [
        'original_share_id',
        'copied_by_user_id',
        'copied_file_id',
    ];

    protected $casts = [
        'copied_at' => 'datetime',
    ];

    /**
     * Disable updated_at timestamp since we only track copied_at
     */
    public $timestamps = false;

    protected $dates = ['copied_at'];

    /**
     * Get the original public share
     */
    public function originalShare(): BelongsTo
    {
        return $this->belongsTo(PublicShare::class, 'original_share_id');
    }

    /**
     * Get the user who copied the file
     */
    public function copiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'copied_by_user_id');
    }

    /**
     * Get the copied file
     */
    public function copiedFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'copied_file_id');
    }

    /**
     * Create a new copy record
     */
    public static function createCopy(PublicShare $share, User $user, File $copiedFile): self
    {
        return self::create([
            'original_share_id' => $share->id,
            'copied_by_user_id' => $user->id,
            'copied_file_id' => $copiedFile->id,
            'copied_at' => now(),
        ]);
    }

    /**
     * Check if user has already copied this share
     */
    public static function hasUserCopied(PublicShare $share, User $user): bool
    {
        return self::where('original_share_id', $share->id)
                   ->where('copied_by_user_id', $user->id)
                   ->exists();
    }

    /**
     * Get all files shared with a user
     */
    public static function getSharedWithUser(User $user)
    {
        return self::with(['originalShare.file', 'originalShare.user', 'copiedFile'])
                   ->where('copied_by_user_id', $user->id)
                   ->orderBy('copied_at', 'desc')
                   ->get();
    }
}
