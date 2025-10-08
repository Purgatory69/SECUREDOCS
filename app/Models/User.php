<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\WebAuthnCredential;
use App\Models\File;
use App\Models\Notification;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Laragear\WebAuthn\WebAuthnAuthentication;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property bool $is_approved
 * @property bool $is_premium
 * @property string|null $remember_token
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class User extends Authenticatable implements WebAuthnAuthenticatable
{
    use HasApiTokens;
    use WebAuthnAuthentication;
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
        'email_notifications_enabled',
        'login_notifications_enabled',
        'security_notifications_enabled',
        'activity_notifications_enabled',
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
        'email_notifications_enabled' => 'boolean',
        'login_notifications_enabled' => 'boolean',
        'security_notifications_enabled' => 'boolean',
        'activity_notifications_enabled' => 'boolean',
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
     * Get the WebAuthn user handle for the user.
     *
     * @return string
     */
    public function getWebAuthnIdentifier(): string
    {
        return (string) $this->getKey();
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
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    /**
     * Get the user's notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the WebAuthn credentials for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function webAuthnCredentials(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\Laragear\WebAuthn\Models\WebAuthnCredential::class, 'authenticatable');
    }

    /**
     * Get the user's payments
     */
    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\Payment::class);
    }

    /**
     * Get the user's subscriptions
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(\App\Models\Subscription::class);
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CustomVerifyEmail);
    }
}
