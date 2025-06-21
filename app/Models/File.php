<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'parent_id',
        'is_folder',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'mime_type',
    ];

    /**
     * Get the user that owns the file.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent folder of this file/folder.
     */
    public function parent()
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
}
