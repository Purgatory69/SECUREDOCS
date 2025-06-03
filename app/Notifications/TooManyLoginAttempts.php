<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TooManyLoginAttempts extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Account Login Attempts Warning')
            ->line('There have been 5 failed login attempts on your account.')
            ->line('If this was not you, please reset your password or contact support.');
    }
}
