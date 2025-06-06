<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserApproved
{
    /**
     * The guard implementation.
     *
     * @var \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected $guard;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create the event listener.
     *
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(StatefulGuard $guard, Request $request)
    {
        $this->guard = $guard;
        $this->request = $request;
    }

    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\Authenticated  $event
     * @return void
     */
    public function handle(Authenticated $event)
    {
        if ($event->user && !$event->user->is_approved) {
            $this->guard->logout();

            $this->request->session()->invalidate();

            $this->request->session()->regenerateToken();

            // Redirect to login with an error message
            // Using a general error key that Laravel's default login view might pick up,
            // or you can customize your login view to display this specific session error.
            abort(redirect('/login')->withErrors(['email' => 'Your account is pending approval from an administrator. Please wait or contact support.']));
        }
    }
}
