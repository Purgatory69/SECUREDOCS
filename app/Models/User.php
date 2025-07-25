<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use LaravelWebauthn\Models\WebauthnKey;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_approved',
        'is_premium',
    ];

    /**
     * The possible user roles.
     *
     * @var array<string>
     */
    public const ROLES = [
        'user' => 'User',
        'record admin' => 'Record Admin',
        'admin' => 'Admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_approved' => 'boolean',
        'is_premium' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Check if the user has a specific role.
     *
     * @param  string  $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if the user is an admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if the user is a record admin.
     *
     * @return bool
     */
    public function isRecordAdmin(): bool
    {
        return $this->hasRole('record admin');
    }

    /**
     * Check if the user is a regular user.
     *
     * @return bool
     */
    public function isRegularUser(): bool
    {
        return $this->hasRole('user');
    }

    /**
     * Get the files for the user.
     */
    public function files()
    {
        return $this->hasMany(File::class);
    }

    /**
     * Get the WebAuthn keys for the user.
     */
    public function webauthnKeys()
    {
        return $this->hasMany(WebauthnKey::class);
    }
}
