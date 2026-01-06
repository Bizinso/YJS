<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Notifications\RefundProcessed;
use App\Services\Refund\RefundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRefund implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;

    public function __construct(
        public Order $order,
        public float $amount,
        public string $reason
    ) {}

    public function handle(RefundService $refundService): void
    {
        $result = $refundService->processRefund($this->order, $this->amount, $this->reason);

        if ($result['success']) {
            // Notify customer
            $user = User::find($this->order->customer_id);
            if ($user) {
                $user->notify(new RefundProcessed($this->order, $this->amount, $this->reason));
            }

            Log::info("Refund processed successfully", [
                'order_id' => $this->order->id,
                'amount' => $this->amount,
                'refund_id' => $result['refund_id'],
            ]);
        } else {
            Log::error("Refund processing failed", [
                'order_id' => $this->order->id,
                'amount' => $this->amount,
                'error' => $result['message'],
            ]);

            throw new \Exception($result['message']);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Refund job failed", [
            'order_id' => $this->order->id,
            'amount' => $this->amount,
            'error' => $exception->getMessage(),
        ]);
    }
}
