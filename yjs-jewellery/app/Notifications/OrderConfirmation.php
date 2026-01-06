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
        $orderTotal = number_format($this->order->order_total, 2);

        return (new MailMessage)
            ->subject("Order Confirmed - #{$this->order->custom_order_code}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Thank you for your order. Your order has been confirmed.")
            ->line("Order Number: {$this->order->custom_order_code}")
            ->line("Order Total: ₹{$orderTotal}")
            ->action('View Order', url("/orders/{$this->order->id}"))
            ->line('We will notify you when your order ships.');
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
