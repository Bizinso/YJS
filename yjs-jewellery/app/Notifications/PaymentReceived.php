<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public float $amount
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment Received - #{$this->order->custom_order_code}")
            ->view('emails.orders.payment', [
                'order' => $this->order->load(['customer']),
                'amount' => $this->amount,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_received',
            'order_id' => $this->order->id,
            'order_code' => $this->order->custom_order_code,
            'amount' => $this->amount,
            'message' => "Payment of ₹{$this->amount} received for order #{$this->order->custom_order_code}.",
        ];
    }
}
