<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderDelivered extends Notification implements ShouldQueue
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
            ->subject("Order Delivered - #{$this->order->custom_order_code}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your order has been delivered successfully!")
            ->line("Order Number: {$this->order->custom_order_code}")
            ->line("Delivered On: {$this->order->delivery_date}")
            ->action('Write a Review', url("/orders/{$this->order->id}/review"))
            ->line('We hope you love your purchase! Please leave a review.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_delivered',
            'order_id' => $this->order->id,
            'order_code' => $this->order->custom_order_code,
            'delivery_date' => $this->order->delivery_date,
            'message' => "Your order #{$this->order->custom_order_code} has been delivered.",
        ];
    }
}
