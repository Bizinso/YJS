<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderConfirmation;
use App\Notifications\OrderShipped;
use App\Notifications\OrderDelivered;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Order $order,
        public string $notificationType
    ) {}

    public function handle(): void
    {
        $user = User::find($this->order->customer_id);

        if (!$user) {
            Log::warning("User not found for order notification", [
                'order_id' => $this->order->id,
                'customer_id' => $this->order->customer_id,
            ]);
            return;
        }

        $notification = match ($this->notificationType) {
            'confirmed' => new OrderConfirmation($this->order),
            'shipped' => new OrderShipped($this->order),
            'delivered' => new OrderDelivered($this->order),
            default => null,
        };

        if ($notification) {
            $user->notify($notification);
            Log::info("Order notification sent", [
                'order_id' => $this->order->id,
                'type' => $this->notificationType,
                'user_id' => $user->id,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to send order notification", [
            'order_id' => $this->order->id,
            'type' => $this->notificationType,
            'error' => $exception->getMessage(),
        ]);
    }
}
