<?php

namespace App\Jobs;

use App\Models\LoyaltyPoints;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireLoyaltyPoints implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expiredCount = 0;
        $expiredPoints = 0;

        DB::transaction(function () use (&$expiredCount, &$expiredPoints) {
            $expiring = LoyaltyPoints::where('expires_at', '<=', now())
                ->where('status', 'active')
                ->where('balance', '>', 0)
                ->get();

            foreach ($expiring as $points) {
                $expiredPoints += $points->balance;
                $points->update([
                    'status' => 'expired',
                    'balance' => 0,
                ]);
                $expiredCount++;
            }
        });

        Log::info("Loyalty points expiry job completed", [
            'expired_records' => $expiredCount,
            'expired_points' => $expiredPoints,
        ]);
    }
}
