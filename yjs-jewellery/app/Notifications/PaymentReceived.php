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
        $amount = number_format($this->amount, 2);

        return (new MailMessage)
            ->subject("Payment Received - #{$this->order->custom_order_code}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("We have received your payment.")
            ->line("Order Number: {$this->order->custom_order_code}")
            ->line("Amount Paid: ₹{$amount}")
            ->action('View Order', url("/orders/{$this->order->id}"))
            ->line('Thank you for your purchase!');
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
