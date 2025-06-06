<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Lockout;
use App\Listeners\SendLockoutNotification;
use Illuminate\Auth\Events\Authenticated;
use App\Listeners\CheckUserApproved;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Lockout::class => [
            SendLockoutNotification::class,
        ],
        Failed::class => [
            FailedLoginListener::class,
        ],
        Authenticated::class => [
            CheckUserApproved::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
