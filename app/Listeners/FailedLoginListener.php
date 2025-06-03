<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;
use App\Notifications\TooManyLoginAttempts;

class FailedLoginListener
{
    public function handle(Failed $event)
    {
        $user = $event->user;
        $email = $event->credentials['email'] ?? null;

        // If no user was found, try to find by email
        if (!$user && $email) {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
            $key = 'login:' . $user->email . '|' . request()->ip();
            
            // Increment failed attempts
            $attempts = RateLimiter::attempts($key) + 1;
            
            // If this is the 5th failed attempt, send notification
            if ($attempts >= 5) {
                $user->notify(new TooManyLoginAttempts());
            }
        }
    }
}