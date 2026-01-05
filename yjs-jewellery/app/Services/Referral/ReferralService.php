<?php

namespace App\Services\Referral;

use App\Models\Referral;
use App\Models\User;
use App\Models\Order;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

/**
 * Referral Service
 *
 * Manages referral program including:
 * - Referral code generation
 * - Referral tracking
 * - Reward distribution
 */
class ReferralService
{
    /**
     * Default referrer reward (points)
     */
    private const DEFAULT_REFERRER_REWARD = 200;

    /**
     * Default referee discount percentage
     */
    private const DEFAULT_REFEREE_DISCOUNT = 10;

    /**
     * Referral expiry days
     */
    private const REFERRAL_EXPIRY_DAYS = 30;

    public function __construct(
        private LoyaltyService $loyaltyService
    ) {}

    /**
     * Generate unique referral code for user
     */
    public function generateReferralCode(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        $code = $this->createUniqueCode($user);
        $user->referral_code = $code;
        $user->save();

        return $code;
    }

    /**
     * Create unique referral code
     */
    private function createUniqueCode(User $user): string
    {
        // Try to create a personalized code first
        $baseName = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $user->first_name ?? 'YJS'), 0, 4));
        $baseCode = $baseName . rand(100, 999);

        if (!User::where('referral_code', $baseCode)->exists()) {
            return $baseCode;
        }

        // Fallback to random code
        do {
            $code = 'YJS' . strtoupper(Str::random(6));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Get referral code for user
     */
    public function getReferralCode(int $userId): ?string
    {
        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        return $this->generateReferralCode($user);
    }

    /**
     * Validate referral code
     */
    public function validateReferralCode(string $code): array
    {
        $referrer = User::where('referral_code', strtoupper($code))->first();

        if (!$referrer) {
            return [
                'valid' => false,
                'error' => 'Invalid referral code',
            ];
        }

        return [
            'valid' => true,
            'referrer_id' => $referrer->id,
            'referrer_name' => $referrer->first_name,
            'referee_discount' => self::DEFAULT_REFEREE_DISCOUNT,
            'discount_display' => self::DEFAULT_REFEREE_DISCOUNT . '% off on first order',
        ];
    }

    /**
     * Apply referral code to new user
     */
    public function applyReferralToUser(int $newUserId, string $referralCode): array
    {
        $validation = $this->validateReferralCode($referralCode);

        if (!$validation['valid']) {
            return $validation;
        }

        $referrerId = $validation['referrer_id'];

        // Cannot refer yourself
        if ($referrerId === $newUserId) {
            return [
                'success' => false,
                'error' => 'Cannot use your own referral code',
            ];
        }

        // Check if user already referred
        $existingReferral = Referral::where('referee_id', $newUserId)->first();
        if ($existingReferral) {
            return [
                'success' => false,
                'error' => 'You have already been referred',
            ];
        }

        // Create referral record
        $referral = Referral::create([
            'referrer_id' => $referrerId,
            'referee_id' => $newUserId,
            'referral_code' => strtoupper($referralCode),
            'status' => 'pending',
            'referrer_reward' => self::DEFAULT_REFERRER_REWARD,
            'referee_discount' => self::DEFAULT_REFEREE_DISCOUNT,
            'expires_at' => now()->addDays(self::REFERRAL_EXPIRY_DAYS),
        ]);

        // Update user's referred_by
        User::where('id', $newUserId)->update(['referred_by' => $referrerId]);

        return [
            'success' => true,
            'referral_id' => $referral->id,
            'discount_percent' => self::DEFAULT_REFEREE_DISCOUNT,
            'message' => "You'll get {$referral->referee_discount}% off on your first order!",
        ];
    }

    /**
     * Get referee discount for first order
     */
    public function getRefereeDiscount(int $userId): ?array
    {
        $referral = Referral::where('referee_id', $userId)
            ->where('status', 'pending')
            ->active()
            ->first();

        if (!$referral) {
            return null;
        }

        return [
            'referral_id' => $referral->id,
            'discount_percent' => $referral->referee_discount,
            'referrer_name' => $referral->referrer->first_name ?? 'Friend',
            'expires_at' => $referral->expires_at?->format('d M Y'),
        ];
    }

    /**
     * Complete referral after first purchase
     */
    public function completeReferral(Order $order): ?array
    {
        if ($order->payment_status !== 'paid') {
            return null;
        }

        $referral = Referral::where('referee_id', $order->customer_id)
            ->where('status', 'pending')
            ->first();

        if (!$referral) {
            return null;
        }

        // Mark referral as completed
        $referral->complete($order->id);

        // Reward the referrer
        $this->rewardReferrer($referral);

        return [
            'referral_id' => $referral->id,
            'referrer_rewarded' => true,
            'reward_amount' => $referral->referrer_reward,
        ];
    }

    /**
     * Reward referrer with points
     */
    private function rewardReferrer(Referral $referral): void
    {
        if ($referral->referrer_rewarded) {
            return;
        }

        // Award loyalty points to referrer
        $this->loyaltyService->awardReferralBonus(
            $referral->referrer_id,
            $referral->referee_id
        );

        $referral->rewardReferrer();
    }

    /**
     * Calculate referral discount on order
     */
    public function calculateReferralDiscount(int $userId, float $orderTotal): array
    {
        $referral = Referral::where('referee_id', $userId)
            ->where('status', 'pending')
            ->active()
            ->first();

        if (!$referral) {
            return [
                'applicable' => false,
            ];
        }

        $discountPercent = $referral->referee_discount;
        $discountAmount = $orderTotal * ($discountPercent / 100);

        return [
            'applicable' => true,
            'referral_id' => $referral->id,
            'discount_percent' => $discountPercent,
            'discount_amount' => round($discountAmount, 2),
            'discount_display' => '₹' . number_format($discountAmount, 2),
        ];
    }

    /**
     * Get referral dashboard for user
     */
    public function getDashboard(int $userId): array
    {
        $user = User::find($userId);
        $referralCode = $this->getReferralCode($userId);

        $referrals = Referral::where('referrer_id', $userId)
            ->with('referee:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->get();

        $completedReferrals = $referrals->where('status', 'completed');
        $pendingReferrals = $referrals->where('status', 'pending');

        $totalRewards = $completedReferrals->sum('referrer_reward');

        return [
            'referral_code' => $referralCode,
            'share_link' => config('app.url') . '/register?ref=' . $referralCode,
            'share_message' => "Use my code {$referralCode} and get {self::DEFAULT_REFEREE_DISCOUNT}% off on your first order at YJS Jewellers!",
            'statistics' => [
                'total_referrals' => $referrals->count(),
                'completed' => $completedReferrals->count(),
                'pending' => $pendingReferrals->count(),
                'total_rewards' => $totalRewards,
                'total_rewards_display' => $totalRewards . ' points',
            ],
            'referrals' => $referrals->map(fn($r) => [
                'id' => $r->id,
                'referee_name' => $r->referee ? "{$r->referee->first_name}" : 'Unknown',
                'status' => $r->status,
                'reward' => $r->referrer_reward,
                'rewarded' => $r->referrer_rewarded,
                'created_at' => $r->created_at->format('d M Y'),
                'completed_at' => $r->completed_at?->format('d M Y'),
            ]),
            'rewards_structure' => [
                'referrer_gets' => self::DEFAULT_REFERRER_REWARD . ' loyalty points',
                'referee_gets' => self::DEFAULT_REFEREE_DISCOUNT . '% off first order',
            ],
        ];
    }

    /**
     * Get referral statistics for admin
     */
    public function getAdminStatistics(): array
    {
        $totalReferrals = Referral::count();
        $completedReferrals = Referral::where('status', 'completed')->count();
        $pendingReferrals = Referral::where('status', 'pending')->count();
        $expiredReferrals = Referral::where('status', 'expired')->count();

        $topReferrers = Referral::select('referrer_id')
            ->selectRaw('COUNT(*) as referral_count')
            ->selectRaw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count')
            ->with('referrer:id,first_name,last_name,email')
            ->groupBy('referrer_id')
            ->orderByDesc('referral_count')
            ->limit(10)
            ->get();

        $monthlyReferrals = Referral::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
            ->groupBy('month')
            ->orderByDesc('month')
            ->limit(12)
            ->get();

        return [
            'summary' => [
                'total' => $totalReferrals,
                'completed' => $completedReferrals,
                'pending' => $pendingReferrals,
                'expired' => $expiredReferrals,
                'conversion_rate' => $totalReferrals > 0
                    ? round(($completedReferrals / $totalReferrals) * 100, 1)
                    : 0,
            ],
            'top_referrers' => $topReferrers->map(fn($r) => [
                'user_id' => $r->referrer_id,
                'name' => $r->referrer ? "{$r->referrer->first_name} {$r->referrer->last_name}" : 'Unknown',
                'email' => $r->referrer?->email,
                'total_referrals' => $r->referral_count,
                'completed_referrals' => $r->completed_count,
            ]),
            'monthly_trend' => $monthlyReferrals,
        ];
    }

    /**
     * Expire pending referrals
     */
    public function expirePendingReferrals(): int
    {
        return Referral::where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    /**
     * Cancel referral
     */
    public function cancelReferral(int $referralId): bool
    {
        $referral = Referral::find($referralId);

        if (!$referral || $referral->status !== 'pending') {
            return false;
        }

        $referral->status = 'cancelled';
        $referral->save();

        // Remove referred_by from user
        User::where('id', $referral->referee_id)
            ->where('referred_by', $referral->referrer_id)
            ->update(['referred_by' => null]);

        return true;
    }
}
