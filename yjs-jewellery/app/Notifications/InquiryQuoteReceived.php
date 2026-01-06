<?php

namespace App\Notifications;

use App\Models\PartnerInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InquiryQuoteReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PartnerInquiry $inquiry
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Quote Received - Inquiry #{$this->inquiry->inquiry_code}")
            ->view('emails.partner.quote', [
                'inquiry' => $this->inquiry->load(['items.product']),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inquiry_quote_received',
            'inquiry_id' => $this->inquiry->id,
            'inquiry_code' => $this->inquiry->inquiry_code,
            'quoted_total' => $this->inquiry->quoted_total,
            'message' => "Quote received for inquiry #{$this->inquiry->inquiry_code}.",
        ];
    }
}
