<?php

namespace App\Services\Loyalty;

use App\Models\LoyaltyPoints;
use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyTier;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Loyalty Service
 *
 * Manages loyalty points system including:
 * - Points earning on purchases
 * - Points redemption
 * - Tier management
 * - Birthday/Anniversary bonuses
 * - Points expiry
 */
class LoyaltyService
{
    /**
     * Default earn rate: 1 point per Rs.100 spent
     */
    private const DEFAULT_POINTS_PER_100 = 1;

    /**
     * Default redemption rate: 100 points = Rs.1
     */
    private const DEFAULT_POINTS_TO_RUPEE = 100;

    /**
     * Welcome bonus points
     */
    private const WELCOME_BONUS = 50;

    /**
     * Get or create loyalty account for user
     */
    public function getAccount(int $userId): LoyaltyPoints
    {
        return LoyaltyPoints::getOrCreateForUser($userId);
    }

    /**
     * Get loyalty dashboard data for user
     */
    public function getDashboard(int $userId): array
    {
        $account = $this->getAccount($userId);
        $tier = $account->tierConfig ?? LoyaltyTier::where('slug', 'bronze')->first();
        $nextTier = $tier?->getNextTier();

        $recentTransactions = LoyaltyPointTransaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $expiringPoints = $this->getExpiringPoints($userId, 30);

        return [
            'points_balance' => $account->points_balance,
            'points_value' => $this->getPointsValue($account->points_balance, $tier),
            'lifetime_points' => $account->lifetime_points,
            'redeemed_points' => $account->redeemed_points,
            'current_tier' => [
                'name' => $tier?->name ?? 'Bronze',
                'slug' => $tier?->slug ?? 'bronze',
                'badge_color' => $tier?->badge_color ?? '#CD7F32',
                'benefits' => $tier?->benefits ?? [],
                'multiplier' => $tier?->points_multiplier ?? 1.0,
            ],
            'next_tier' => $nextTier ? [
                'name' => $nextTier->name,
                'slug' => $nextTier->slug,
                'points_required' => $nextTier->min_points,
                'points_needed' => max(0, $nextTier->min_points - $account->lifetime_points),
                'progress_percent' => $tier->max_points
                    ? round((($account->lifetime_points - $tier->min_points) / ($tier->max_points - $tier->min_points)) * 100, 1)
                    : 100,
            ] : null,
            'recent_transactions' => $recentTransactions->map(fn($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'points' => $t->points,
                'balance_after' => $t->balance_after,
                'description' => $t->description,
                'date' => $t->created_at->format('d M Y'),
                'expires_at' => $t->expires_at?->format('d M Y'),
            ]),
            'expiring_soon' => [
                'points' => $expiringPoints,
                'expiry_date' => now()->addDays(30)->format('d M Y'),
            ],
            'all_tiers' => LoyaltyTier::getActiveTiers()->map(fn($t) => [
                'name' => $t->name,
                'slug' => $t->slug,
                'min_points' => $t->min_points,
                'badge_color' => $t->badge_color,
                'benefits' => $t->benefits,
            ]),
        ];
    }

    /**
     * Award points for order
     */
    public function awardPointsForOrder(Order $order): ?LoyaltyPointTransaction
    {
        if (!$order->customer_id || $order->payment_status !== 'paid') {
            return null;
        }

        $account = $this->getAccount($order->customer_id);
        $tier = $account->tierConfig;

        // Calculate points based on order total
        $basePoints = floor($order->order_total / 100) * self::DEFAULT_POINTS_PER_100;

        // Apply tier multiplier
        $multiplier = $tier?->points_multiplier ?? 1.0;
        $earnedPoints = (int) floor($basePoints * $multiplier);

        if ($earnedPoints <= 0) {
            return null;
        }

        return $account->addPoints(
            $earnedPoints,
            'earn',
            "Points earned on Order #{$order->custom_order_code}",
            $order->id,
            'order',
            $order->id
        );
    }

    /**
     * Redeem points for order discount
     */
    public function redeemPointsForOrder(int $userId, int $points, int $orderId): array
    {
        $account = $this->getAccount($userId);

        if ($points > $account->points_balance) {
            return [
                'success' => false,
                'error' => 'Insufficient points balance',
                'available' => $account->points_balance,
            ];
        }

        // Check minimum redemption
        $minRedemption = 100;
        if ($points < $minRedemption) {
            return [
                'success' => false,
                'error' => "Minimum {$minRedemption} points required for redemption",
            ];
        }

        $tier = $account->tierConfig;
        $rupeeValue = $this->getPointsValue($points, $tier);

        $transaction = $account->redeemPoints(
            $points,
            "Points redeemed on Order #{$orderId}",
            $orderId
        );

        if (!$transaction) {
            return [
                'success' => false,
                'error' => 'Failed to redeem points',
            ];
        }

        return [
            'success' => true,
            'points_redeemed' => $points,
            'rupee_value' => $rupeeValue,
            'new_balance' => $account->points_balance,
            'transaction_id' => $transaction->id,
        ];
    }

    /**
     * Reverse redemption (for order cancellation)
     */
    public function reverseRedemption(int $orderId): void
    {
        $transactions = LoyaltyPointTransaction::where('order_id', $orderId)
            ->where('type', 'redeem')
            ->get();

        foreach ($transactions as $transaction) {
            $account = LoyaltyPoints::where('user_id', $transaction->user_id)->first();
            if ($account) {
                $pointsToRestore = abs($transaction->points);
                $account->addPoints(
                    $pointsToRestore,
                    'adjustment',
                    "Points restored for cancelled Order #{$orderId}",
                    $orderId,
                    'order_cancellation',
                    $orderId
                );
            }
        }
    }

    /**
     * Get points value in rupees
     */
    public function getPointsValue(int $points, ?LoyaltyTier $tier = null): float
    {
        $rate = self::DEFAULT_POINTS_TO_RUPEE;

        // Better tiers get better conversion
        if ($tier) {
            $multiplier = $tier->points_multiplier ?? 1.0;
            $rate = self::DEFAULT_POINTS_TO_RUPEE / $multiplier;
        }

        return round($points / $rate, 2);
    }

    /**
     * Calculate points that would be earned for amount
     */
    public function calculatePotentialPoints(float $amount, int $userId): array
    {
        $account = $this->getAccount($userId);
        $tier = $account->tierConfig;

        $basePoints = floor($amount / 100) * self::DEFAULT_POINTS_PER_100;
        $multiplier = $tier?->points_multiplier ?? 1.0;
        $earnedPoints = (int) floor($basePoints * $multiplier);

        return [
            'base_points' => $basePoints,
            'multiplier' => $multiplier,
            'total_points' => $earnedPoints,
            'tier' => $tier?->name ?? 'Bronze',
        ];
    }

    /**
     * Award welcome bonus
     */
    public function awardWelcomeBonus(int $userId): ?LoyaltyPointTransaction
    {
        $account = $this->getAccount($userId);

        // Check if already received welcome bonus
        $hasBonus = LoyaltyPointTransaction::where('user_id', $userId)
            ->where('reference_type', 'welcome')
            ->exists();

        if ($hasBonus) {
            return null;
        }

        return $account->addPoints(
            self::WELCOME_BONUS,
            'bonus',
            'Welcome bonus points',
            null,
            'welcome',
            $userId
        );
    }

    /**
     * Award birthday bonus
     */
    public function awardBirthdayBonus(int $userId): ?LoyaltyPointTransaction
    {
        $user = User::find($userId);
        if (!$user || !$user->date_of_birth) {
            return null;
        }

        $account = $this->getAccount($userId);
        $tier = $account->tierConfig;

        // Check if already received birthday bonus this year
        $year = now()->year;
        $hasBonus = LoyaltyPointTransaction::where('user_id', $userId)
            ->where('reference_type', 'birthday')
            ->whereYear('created_at', $year)
            ->exists();

        if ($hasBonus) {
            return null;
        }

        $bonusPoints = $tier?->getBenefit('birthday_bonus', 100) ?? 100;

        return $account->addPoints(
            $bonusPoints,
            'bonus',
            "Birthday bonus - {$year}",
            null,
            'birthday',
            $userId
        );
    }

    /**
     * Award referral bonus
     */
    public function awardReferralBonus(int $userId, int $referredUserId): ?LoyaltyPointTransaction
    {
        $account = $this->getAccount($userId);

        // Standard referral bonus
        $bonusPoints = 200;

        return $account->addPoints(
            $bonusPoints,
            'bonus',
            "Referral bonus for inviting a friend",
            null,
            'referral',
            $referredUserId
        );
    }

    /**
     * Award review bonus
     */
    public function awardReviewBonus(int $userId, int $reviewId, bool $hasPhoto = false): ?LoyaltyPointTransaction
    {
        $account = $this->getAccount($userId);

        $bonusPoints = $hasPhoto ? 50 : 25; // More points for photo reviews

        return $account->addPoints(
            $bonusPoints,
            'bonus',
            $hasPhoto ? 'Photo review bonus' : 'Review bonus',
            null,
            'review',
            $reviewId
        );
    }

    /**
     * Get expiring points in next N days
     */
    public function getExpiringPoints(int $userId, int $days = 30): int
    {
        return LoyaltyPointTransaction::where('user_id', $userId)
            ->where('type', 'earn')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now())
            ->sum('points');
    }

    /**
     * Process expired points
     */
    public function processExpiredPoints(): int
    {
        $expiredTransactions = LoyaltyPointTransaction::where('type', 'earn')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereRaw('points > 0')
            ->get()
            ->groupBy('user_id');

        $totalExpired = 0;

        foreach ($expiredTransactions as $userId => $transactions) {
            $pointsToExpire = $transactions->sum('points');
            $account = LoyaltyPoints::where('user_id', $userId)->first();

            if ($account && $pointsToExpire > 0) {
                $actualExpired = min($pointsToExpire, $account->points_balance);
                $account->points_balance -= $actualExpired;
                $account->save();

                LoyaltyPointTransaction::create([
                    'user_id' => $userId,
                    'type' => 'expire',
                    'points' => -$actualExpired,
                    'balance_after' => $account->points_balance,
                    'description' => 'Points expired',
                    'reference_type' => 'expiry',
                ]);

                $totalExpired += $actualExpired;
            }

            // Mark original transactions as processed
            LoyaltyPointTransaction::whereIn('id', $transactions->pluck('id'))
                ->update(['points' => 0]);
        }

        return $totalExpired;
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard(int $limit = 10): Collection
    {
        return LoyaltyPoints::with('user:id,first_name,last_name')
            ->orderBy('lifetime_points', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($account, $index) => [
                'rank' => $index + 1,
                'user_name' => $account->user ? "{$account->user->first_name} {$account->user->last_name}" : 'Unknown',
                'lifetime_points' => $account->lifetime_points,
                'tier' => $account->tier,
            ]);
    }

    /**
     * Get points history for user
     */
    public function getPointsHistory(int $userId, int $perPage = 20): array
    {
        $transactions = LoyaltyPointTransaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'transactions' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ];
    }

    /**
     * Admin: Manually adjust points
     */
    public function adjustPoints(int $userId, int $points, string $reason): LoyaltyPointTransaction
    {
        $account = $this->getAccount($userId);

        if ($points > 0) {
            return $account->addPoints($points, 'adjustment', $reason);
        } else {
            $account->points_balance += $points; // Negative
            $account->save();

            return LoyaltyPointTransaction::create([
                'user_id' => $userId,
                'type' => 'adjustment',
                'points' => $points,
                'balance_after' => $account->points_balance,
                'description' => $reason,
                'reference_type' => 'admin_adjustment',
            ]);
        }
    }
}
