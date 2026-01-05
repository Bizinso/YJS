<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer Loyalty Controller
 *
 * Handles customer-facing loyalty operations including:
 * - Viewing points balance
 * - Viewing tier status
 * - Points history
 * - Redeeming points
 */
class CustomerLoyaltyController extends Controller
{
    public function __construct(
        private LoyaltyService $loyaltyService
    ) {}

    /**
     * Get loyalty dashboard.
     *
     * @return JsonResponse
     */
    public function getDashboard(): JsonResponse
    {
        $userId = auth()->id();
        $dashboard = $this->loyaltyService->getDashboard($userId);

        return response()->json([
            'success' => true,
            ...$dashboard,
        ]);
    }

    /**
     * Get points balance.
     *
     * @return JsonResponse
     */
    public function getBalance(): JsonResponse
    {
        $userId = auth()->id();
        $account = $this->loyaltyService->getAccount($userId);

        return response()->json([
            'success' => true,
            'points_balance' => $account->points_balance,
            'points_value' => $account->points_value,
            'tier' => $account->tier,
        ]);
    }

    /**
     * Get points history.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getHistory(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $perPage = min($request->input('per_page', 20), 50);

        $history = $this->loyaltyService->getPointsHistory($userId, $perPage);

        return response()->json([
            'success' => true,
            ...$history,
        ]);
    }

    /**
     * Calculate potential points for an amount.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculatePoints(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $userId = auth()->id();
        $calculation = $this->loyaltyService->calculatePotentialPoints(
            $validated['amount'],
            $userId
        );

        return response()->json([
            'success' => true,
            ...$calculation,
        ]);
    }

    /**
     * Preview points redemption.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function previewRedemption(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:100',
        ]);

        $userId = auth()->id();
        $account = $this->loyaltyService->getAccount($userId);

        if ($validated['points'] > $account->points_balance) {
            return response()->json([
                'success' => false,
                'error' => 'Insufficient points balance',
                'available' => $account->points_balance,
            ], 400);
        }

        $rupeeValue = $this->loyaltyService->getPointsValue(
            $validated['points'],
            $account->tierConfig
        );

        return response()->json([
            'success' => true,
            'points_to_redeem' => $validated['points'],
            'rupee_value' => $rupeeValue,
            'rupee_value_display' => '₹' . number_format($rupeeValue, 2),
            'remaining_balance' => $account->points_balance - $validated['points'],
        ]);
    }

    /**
     * Get tier benefits.
     *
     * @return JsonResponse
     */
    public function getTierBenefits(): JsonResponse
    {
        $tiers = \App\Models\LoyaltyTier::getActiveTiers();

        return response()->json([
            'success' => true,
            'tiers' => $tiers->map(fn($tier) => [
                'name' => $tier->name,
                'slug' => $tier->slug,
                'min_points' => $tier->min_points,
                'max_points' => $tier->max_points,
                'points_multiplier' => $tier->points_multiplier,
                'badge_color' => $tier->badge_color,
                'benefits' => $tier->benefits,
            ]),
        ]);
    }

    /**
     * Get expiring points notification.
     *
     * @return JsonResponse
     */
    public function getExpiringPoints(): JsonResponse
    {
        $userId = auth()->id();
        $expiringIn30Days = $this->loyaltyService->getExpiringPoints($userId, 30);
        $expiringIn7Days = $this->loyaltyService->getExpiringPoints($userId, 7);

        return response()->json([
            'success' => true,
            'expiring_in_30_days' => $expiringIn30Days,
            'expiring_in_7_days' => $expiringIn7Days,
            'alert' => $expiringIn7Days > 0
                ? "{$expiringIn7Days} points expiring soon! Use them before they're gone."
                : null,
        ]);
    }

    /**
     * Get leaderboard.
     *
     * @return JsonResponse
     */
    public function getLeaderboard(): JsonResponse
    {
        $leaderboard = $this->loyaltyService->getLeaderboard(10);

        // Find current user's rank
        $userId = auth()->id();
        $account = $this->loyaltyService->getAccount($userId);
        $userRank = \App\Models\LoyaltyPoints::where('lifetime_points', '>', $account->lifetime_points)->count() + 1;

        return response()->json([
            'success' => true,
            'leaderboard' => $leaderboard,
            'your_rank' => $userRank,
            'your_points' => $account->lifetime_points,
        ]);
    }
}
