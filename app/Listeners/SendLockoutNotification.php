<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Lockout;
use App\Models\User;
use App\Notifications\TooManyLoginAttempts;

class SendLockoutNotification
{
    public function handle(Lockout $event)
    {
        $field = \Laravel\Fortify\Fortify::username();
        $email = $event->request->input($field);
        \Log::info('Lockout event triggered for: ' . $email);

        if ($email) {
            $user = \App\Models\User::where($field, $email)->first();
            if ($user) {
                \Log::info('User found for lockout: ' . $user->email);
                $user->notify(new \App\Notifications\TooManyLoginAttempts());
            } else {
                \Log::info('No user found for: ' . $email);
            }
        }
    }
}
