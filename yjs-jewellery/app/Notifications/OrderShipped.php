<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShipped extends Notification implements ShouldQueue
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
        $trackingUrl = $this->order->awb_number
            ? "https://shiprocket.co/tracking/{$this->order->awb_number}"
            : url("/orders/{$this->order->id}/tracking");

        return (new MailMessage)
            ->subject("Order Shipped - #{$this->order->custom_order_code}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Great news! Your order has been shipped.")
            ->line("Order Number: {$this->order->custom_order_code}")
            ->line("Courier: {$this->order->courier_name}")
            ->line("Tracking Number: {$this->order->awb_number}")
            ->action('Track Order', $trackingUrl)
            ->line('You can track your shipment using the button above.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_shipped',
            'order_id' => $this->order->id,
            'order_code' => $this->order->custom_order_code,
            'awb_number' => $this->order->awb_number,
            'courier' => $this->order->courier_name,
            'message' => "Your order #{$this->order->custom_order_code} has been shipped.",
        ];
    }
}
