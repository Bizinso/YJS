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
        $total = number_format($this->inquiry->quoted_total ?? 0, 2);

        return (new MailMessage)
            ->subject("Quote Received - Inquiry #{$this->inquiry->inquiry_code}")
            ->greeting("Hello!")
            ->line("We have prepared a quote for your inquiry.")
            ->line("Inquiry Code: {$this->inquiry->inquiry_code}")
            ->line("Quoted Total: ₹{$total}")
            ->line("Valid Until: {$this->inquiry->quote_valid_until}")
            ->action('View Quote', url("/partner/inquiries/{$this->inquiry->id}"))
            ->line('Please review and accept the quote to proceed.');
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
