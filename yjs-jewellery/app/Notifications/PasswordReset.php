<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordReset extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $otp
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Password Reset OTP')
            ->greeting("Hello {$notifiable->name}!")
            ->line('You have requested to reset your password.')
            ->line("Your OTP is: **{$this->otp}**")
            ->line('This OTP is valid for 10 minutes.')
            ->line('If you did not request this, please ignore this email.');
    }
}
