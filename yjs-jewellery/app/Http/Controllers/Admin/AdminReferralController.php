<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Services\Referral\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Referral Controller
 *
 * Manages referral program including:
 * - Referral statistics
 * - Referral listing and details
 * - Referral management
 */
class AdminReferralController extends Controller
{
    public function __construct(
        private ReferralService $referralService
    ) {}

    /**
     * Get referral program statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->referralService->getAdminStatistics();

        return response()->json([
            'success' => true,
            ...$stats,
        ]);
    }

    /**
     * List all referrals.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->input('per_page', 20), 100);
        $status = $request->input('status');
        $referrerId = $request->input('referrer_id');

        $query = Referral::with([
            'referrer:id,first_name,last_name,email',
            'referee:id,first_name,last_name,email',
        ]);

        if ($status) {
            $query->where('status', $status);
        }

        if ($referrerId) {
            $query->where('referrer_id', $referrerId);
        }

        $referrals = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'referrals' => $referrals->items(),
            'pagination' => [
                'current_page' => $referrals->currentPage(),
                'last_page' => $referrals->lastPage(),
                'per_page' => $referrals->perPage(),
                'total' => $referrals->total(),
            ],
        ]);
    }

    /**
     * Get referral details.
     */
    public function show(Referral $referral): JsonResponse
    {
        $referral->load([
            'referrer:id,first_name,last_name,email,phone',
            'referee:id,first_name,last_name,email,phone',
            'refereeOrder',
        ]);

        return response()->json([
            'success' => true,
            'referral' => $referral,
        ]);
    }

    /**
     * Cancel a pending referral.
     */
    public function cancel(Referral $referral): JsonResponse
    {
        if ($referral->status !== 'pending') {
            return response()->json([
                'success' => false,
                'error' => 'Only pending referrals can be cancelled',
            ], 400);
        }

        $success = $this->referralService->cancelReferral($referral->id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel referral',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Referral cancelled successfully',
        ]);
    }

    /**
     * Expire all pending referrals that are past expiry.
     */
    public function expirePending(): JsonResponse
    {
        $expiredCount = $this->referralService->expirePendingReferrals();

        return response()->json([
            'success' => true,
            'message' => "Expired {$expiredCount} pending referrals",
            'expired_count' => $expiredCount,
        ]);
    }
}
