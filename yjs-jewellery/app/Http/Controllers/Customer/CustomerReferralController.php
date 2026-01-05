<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Referral\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer Referral Controller
 *
 * Handles customer-facing referral operations including:
 * - Getting referral code
 * - Viewing referral dashboard
 * - Applying referral codes
 */
class CustomerReferralController extends Controller
{
    public function __construct(
        private ReferralService $referralService
    ) {}

    /**
     * Get referral dashboard.
     *
     * @return JsonResponse
     */
    public function getDashboard(): JsonResponse
    {
        $userId = auth()->id();
        $dashboard = $this->referralService->getDashboard($userId);

        return response()->json([
            'success' => true,
            ...$dashboard,
        ]);
    }

    /**
     * Get referral code.
     *
     * @return JsonResponse
     */
    public function getReferralCode(): JsonResponse
    {
        $userId = auth()->id();
        $code = $this->referralService->getReferralCode($userId);

        return response()->json([
            'success' => true,
            'referral_code' => $code,
            'share_link' => config('app.url') . '/register?ref=' . $code,
        ]);
    }

    /**
     * Validate referral code.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $result = $this->referralService->validateReferralCode($validated['code']);

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            ...$result,
        ]);
    }

    /**
     * Apply referral code (during registration or first login).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function applyCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $userId = auth()->id();
        $result = $this->referralService->applyReferralToUser($userId, $validated['code']);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            ...$result,
        ]);
    }

    /**
     * Get referee discount if applicable.
     *
     * @return JsonResponse
     */
    public function getRefereeDiscount(): JsonResponse
    {
        $userId = auth()->id();
        $discount = $this->referralService->getRefereeDiscount($userId);

        if (!$discount) {
            return response()->json([
                'success' => true,
                'has_discount' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'has_discount' => true,
            ...$discount,
        ]);
    }

    /**
     * Calculate referral discount for order.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateDiscount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_total' => 'required|numeric|min:0',
        ]);

        $userId = auth()->id();
        $discount = $this->referralService->calculateReferralDiscount(
            $userId,
            $validated['order_total']
        );

        return response()->json([
            'success' => true,
            ...$discount,
        ]);
    }

    /**
     * Get share content for social media.
     *
     * @return JsonResponse
     */
    public function getShareContent(): JsonResponse
    {
        $userId = auth()->id();
        $user = auth()->user();
        $code = $this->referralService->getReferralCode($userId);
        $shareLink = config('app.url') . '/register?ref=' . $code;

        return response()->json([
            'success' => true,
            'code' => $code,
            'link' => $shareLink,
            'messages' => [
                'whatsapp' => "Hey! I've been shopping at YJS Jewellers and thought you'd love it too! Use my code {$code} to get 10% off your first order. Shop now: {$shareLink}",
                'email' => [
                    'subject' => "{$user->first_name} invited you to YJS Jewellers",
                    'body' => "Your friend {$user->first_name} thinks you'd love YJS Jewellers! Use code {$code} at checkout to get 10% off your first order. Start shopping: {$shareLink}",
                ],
                'twitter' => "I just discovered amazing jewellery at YJS Jewellers! Use my referral code {$code} for 10% off your first order: {$shareLink}",
                'facebook' => "Check out YJS Jewellers! Use my code {$code} and get 10% off your first order: {$shareLink}",
            ],
        ]);
    }
}
