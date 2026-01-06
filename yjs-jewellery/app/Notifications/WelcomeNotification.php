<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to YJS Jewellers!')
            ->greeting("Hello {$notifiable->name}!")
            ->line('Welcome to YJS Jewellers. We are delighted to have you with us.')
            ->line('Discover our exquisite collection of fine jewellery crafted with precision and love.')
            ->action('Start Shopping', url('/'))
            ->line('Thank you for choosing YJS Jewellers!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'message' => 'Welcome to YJS Jewellers! Start exploring our collection.',
        ];
    }
}
