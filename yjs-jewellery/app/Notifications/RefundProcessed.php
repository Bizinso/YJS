<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundProcessed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public float $amount,
        public string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Refund Processed - #{$this->order->custom_order_code}")
            ->view('emails.orders.refund', [
                'order' => $this->order->load(['customer']),
                'amount' => $this->amount,
                'reason' => $this->reason,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'refund_processed',
            'order_id' => $this->order->id,
            'order_code' => $this->order->custom_order_code,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'message' => "Refund of ₹{$this->amount} processed for order #{$this->order->custom_order_code}.",
        ];
    }
}
