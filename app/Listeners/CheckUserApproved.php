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

            // Create user-friendly informational message
            $message = 'Welcome! Your account has been created successfully and is currently pending approval from our administrators. You will receive access once your account is approved. If you have any questions or need assistance, please contact our support team.';

            // Redirect to login with informational flash message
            abort(redirect('/login')->with('approval_pending', $message));
        }
    }
}
