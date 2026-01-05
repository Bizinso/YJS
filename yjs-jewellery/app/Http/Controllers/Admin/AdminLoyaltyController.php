<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoints;
use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyTier;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Loyalty Controller
 *
 * Manages loyalty program including:
 * - User points management
 * - Tier configuration
 * - Points expiry processing
 */
class AdminLoyaltyController extends Controller
{
    public function __construct(
        private LoyaltyService $loyaltyService
    ) {}

    /**
     * Get loyalty program statistics.
     */
    public function statistics(): JsonResponse
    {
        $totalUsers = LoyaltyPoints::count();
        $totalPoints = LoyaltyPoints::sum('points_balance');
        $totalLifetimePoints = LoyaltyPoints::sum('lifetime_points');
        $totalRedeemedPoints = LoyaltyPoints::sum('redeemed_points');

        $tierDistribution = LoyaltyPoints::selectRaw('tier, COUNT(*) as count')
            ->groupBy('tier')
            ->pluck('count', 'tier');

        $recentTransactions = LoyaltyPointTransaction::with('user:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $monthlyStats = LoyaltyPointTransaction::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
            ->selectRaw('SUM(CASE WHEN type = "earn" THEN points ELSE 0 END) as earned')
            ->selectRaw('SUM(CASE WHEN type = "redeem" THEN ABS(points) ELSE 0 END) as redeemed')
            ->groupBy('month')
            ->orderByDesc('month')
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_users' => $totalUsers,
                'total_points_in_circulation' => $totalPoints,
                'total_lifetime_points' => $totalLifetimePoints,
                'total_redeemed_points' => $totalRedeemedPoints,
                'redemption_rate' => $totalLifetimePoints > 0
                    ? round(($totalRedeemedPoints / $totalLifetimePoints) * 100, 2)
                    : 0,
            ],
            'tier_distribution' => $tierDistribution,
            'recent_transactions' => $recentTransactions,
            'monthly_stats' => $monthlyStats,
        ]);
    }

    /**
     * Get users with loyalty accounts.
     */
    public function users(Request $request): JsonResponse
    {
        $perPage = min($request->input('per_page', 20), 100);
        $tier = $request->input('tier');
        $sortBy = $request->input('sort_by', 'points_balance');
        $sortOrder = $request->input('sort_order', 'desc');

        $query = LoyaltyPoints::with('user:id,first_name,last_name,email');

        if ($tier) {
            $query->where('tier', $tier);
        }

        $query->orderBy($sortBy, $sortOrder);

        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Get user loyalty details.
     */
    public function userDetails(int $userId): JsonResponse
    {
        $account = LoyaltyPoints::with('user:id,first_name,last_name,email')
            ->where('user_id', $userId)
            ->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'error' => 'Loyalty account not found',
            ], 404);
        }

        $transactions = LoyaltyPointTransaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'account' => $account,
            'tier_config' => $account->tierConfig,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Manually adjust user points.
     */
    public function adjustPoints(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'points' => 'required|integer',
            'reason' => 'required|string|max:500',
        ]);

        $transaction = $this->loyaltyService->adjustPoints(
            $userId,
            $validated['points'],
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Points adjusted successfully',
            'transaction' => $transaction,
        ]);
    }

    /**
     * Get all tiers.
     */
    public function tiers(): JsonResponse
    {
        $tiers = LoyaltyTier::orderBy('order')->get();

        return response()->json([
            'success' => true,
            'tiers' => $tiers,
        ]);
    }

    /**
     * Create new tier.
     */
    public function createTier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'slug' => 'required|string|max:50|unique:loyalty_tiers,slug',
            'min_points' => 'required|integer|min:0',
            'max_points' => 'nullable|integer',
            'points_multiplier' => 'required|numeric|min:1|max:10',
            'benefits' => 'nullable|array',
            'badge_icon' => 'nullable|string',
            'badge_color' => 'nullable|string|max:20',
            'order' => 'nullable|integer|min:0',
        ]);

        $tier = LoyaltyTier::create([
            ...$validated,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tier created successfully',
            'tier' => $tier,
        ], 201);
    }

    /**
     * Update tier.
     */
    public function updateTier(Request $request, LoyaltyTier $tier): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'min_points' => 'sometimes|integer|min:0',
            'max_points' => 'nullable|integer',
            'points_multiplier' => 'sometimes|numeric|min:1|max:10',
            'benefits' => 'nullable|array',
            'badge_icon' => 'nullable|string',
            'badge_color' => 'nullable|string|max:20',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $tier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tier updated successfully',
            'tier' => $tier->fresh(),
        ]);
    }

    /**
     * Process expired points.
     */
    public function processExpiredPoints(): JsonResponse
    {
        $expiredCount = $this->loyaltyService->processExpiredPoints();

        return response()->json([
            'success' => true,
            'message' => "Processed {$expiredCount} expired points",
            'expired_points' => $expiredCount,
        ]);
    }
}
