<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order Confirmed - #{$this->order->custom_order_code}")
            ->view('emails.orders.confirmation', [
                'order' => $this->order->load(['items.product', 'customer', 'shippingAddress']),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_confirmation',
            'order_id' => $this->order->id,
            'order_code' => $this->order->custom_order_code,
            'order_total' => $this->order->order_total,
            'message' => "Your order #{$this->order->custom_order_code} has been confirmed.",
        ];
    }
}
