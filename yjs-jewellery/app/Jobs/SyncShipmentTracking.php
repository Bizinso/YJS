<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Shipping\ShiprocketService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncShipmentTracking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 300;

    public function __construct(
        public ?Order $order = null
    ) {}

    public function handle(ShiprocketService $shiprocket): void
    {
        if ($this->order) {
            // Sync single order
            $this->syncOrder($shiprocket, $this->order);
        } else {
            // Sync all in-transit orders
            $orders = Order::whereNotNull('awb_number')
                ->whereNotIn('order_status', ['delivered', 'cancelled', 'returned'])
                ->get();

            foreach ($orders as $order) {
                $this->syncOrder($shiprocket, $order);
            }
        }
    }

    protected function syncOrder(ShiprocketService $shiprocket, Order $order): void
    {
        try {
            $shiprocket->syncOrderTracking($order);
            Log::info("Shipment tracking synced", ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::warning("Failed to sync tracking", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Shipment tracking sync job failed", [
            'error' => $exception->getMessage(),
        ]);
    }
}
