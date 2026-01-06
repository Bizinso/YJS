<?php

namespace App\Notifications;

use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PartnerApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Partner $partner
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Partner Account Approved - YJS Jewellers')
            ->greeting("Hello {$this->partner->company_name}!")
            ->line('Great news! Your partner account has been approved.')
            ->line('You can now access our B2B portal and place bulk orders.')
            ->action('Access Partner Portal', url('/partner/dashboard'))
            ->line('Thank you for partnering with YJS Jewellers!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'partner_approved',
            'partner_id' => $this->partner->id,
            'message' => 'Your partner account has been approved. Welcome to YJS Jewellers B2B!',
        ];
    }
}
