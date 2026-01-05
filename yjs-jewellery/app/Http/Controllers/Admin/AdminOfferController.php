<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\offers;
use App\Models\OfferUsage;
use App\Models\offerTypeMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin Offer Controller
 *
 * Handles offer management including CRUD operations,
 * activation/deactivation, and usage analytics.
 */
class AdminOfferController extends Controller
{
    /**
     * List all offers with filtering and pagination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = offers::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by discount type
        if ($request->has('discount_type')) {
            $query->where('discount_type', $request->discount_type);
        }

        // Filter by offer type
        if ($request->has('offer_type_id')) {
            $query->where('offer_type_id', $request->offer_type_id);
        }

        // Search by title or coupon code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('coupon_code', 'like', "%{$search}%");
            });
        }

        // Filter active offers (within valid date range)
        if ($request->boolean('active_only')) {
            $query->where('status', 'active')
                  ->where('valid_from', '<=', now())
                  ->where('valid_to', '>=', now());
        }

        // Filter expired offers
        if ($request->boolean('expired_only')) {
            $query->where('valid_to', '<', now());
        }

        $perPage = min($request->input('per_page', 15), 100);
        $offers = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $offers->items(),
            'pagination' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ],
        ]);
    }

    /**
     * Get offer details.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $offer = offers::find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }

        // Get usage statistics
        $usageStats = OfferUsage::where('offer_id', $id)
            ->where('reversed', false)
            ->selectRaw('COUNT(*) as total_uses, SUM(discount_amount) as total_discount, COUNT(DISTINCT customer_id) as unique_customers')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $offer,
            'usage_stats' => [
                'total_uses' => $usageStats->total_uses ?? 0,
                'total_discount_given' => round($usageStats->total_discount ?? 0, 2),
                'unique_customers' => $usageStats->unique_customers ?? 0,
            ],
        ]);
    }

    /**
     * Create a new offer.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'offer_type_id' => 'nullable|integer|exists:offer_type_masters,id',
            'discount_type' => 'required|in:flat,percent',
            'discount_amount' => 'required_if:discount_type,flat|nullable|numeric|min:0',
            'discount_percent' => 'required_if:discount_type,percent|nullable|numeric|min:0|max:100',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'apply_on' => 'nullable|in:products,categories,all',
            'apply_on_value' => 'nullable|array',
            'valid_from' => 'required|date',
            'valid_to' => 'required|date|after:valid_from',
            'status' => 'required|in:active,inactive',
            'coupon_code' => 'nullable|string|max:50|unique:offers,coupon_code',
            'details' => 'nullable|array',
            'details.min_cart_value' => 'nullable|numeric|min:0',
            'details.first_order_only' => 'nullable|boolean',
            'details.max_usage_global' => 'nullable|integer|min:1',
            'details.max_usage_per_user' => 'nullable|integer|min:1',
        ]);

        // Build details JSON
        $details = [
            'min_cart_value' => $validated['details']['min_cart_value'] ?? null,
            'first_order_only' => $validated['details']['first_order_only'] ?? false,
            'max_usage_global' => $validated['details']['max_usage_global'] ?? null,
            'max_usage_per_user' => $validated['details']['max_usage_per_user'] ?? null,
        ];

        $offer = offers::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'offer_type_id' => $validated['offer_type_id'] ?? null,
            'discount_type' => $validated['discount_type'],
            'discount_amount' => $validated['discount_type'] === 'flat' ? $validated['discount_amount'] : null,
            'discount_percent' => $validated['discount_type'] === 'percent' ? $validated['discount_percent'] : null,
            'max_discount_amount' => $validated['max_discount_amount'] ?? null,
            'apply_on' => $validated['apply_on'] ?? 'all',
            'apply_on_value' => $validated['apply_on_value'] ?? null,
            'valid_from' => $validated['valid_from'],
            'valid_to' => $validated['valid_to'],
            'status' => $validated['status'],
            'coupon_code' => $validated['coupon_code'] ?? null,
            'details' => $details,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Offer created successfully.',
            'data' => $offer,
        ], 201);
    }

    /**
     * Update an existing offer.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $offer = offers::find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'offer_type_id' => 'nullable|integer|exists:offer_type_masters,id',
            'discount_type' => 'sometimes|in:flat,percent',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'apply_on' => 'nullable|in:products,categories,all',
            'apply_on_value' => 'nullable|array',
            'valid_from' => 'sometimes|date',
            'valid_to' => 'sometimes|date|after:valid_from',
            'status' => 'sometimes|in:active,inactive,expired',
            'coupon_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('offers', 'coupon_code')->ignore($id),
            ],
            'details' => 'nullable|array',
            'details.min_cart_value' => 'nullable|numeric|min:0',
            'details.first_order_only' => 'nullable|boolean',
            'details.max_usage_global' => 'nullable|integer|min:1',
            'details.max_usage_per_user' => 'nullable|integer|min:1',
        ]);

        // Handle details update
        if (isset($validated['details'])) {
            $currentDetails = $offer->details ?? [];
            $validated['details'] = array_merge($currentDetails, $validated['details']);
        }

        // Handle discount type change
        if (isset($validated['discount_type'])) {
            if ($validated['discount_type'] === 'flat') {
                $validated['discount_percent'] = null;
            } else {
                $validated['discount_amount'] = null;
            }
        }

        $offer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Offer updated successfully.',
            'data' => $offer->fresh(),
        ]);
    }

    /**
     * Delete an offer.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $offer = offers::find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }

        // Check if offer has been used
        $usageCount = OfferUsage::where('offer_id', $id)->count();

        if ($usageCount > 0) {
            // Soft delete - mark as expired instead of deleting
            $offer->update(['status' => 'expired']);

            return response()->json([
                'success' => true,
                'message' => 'Offer has been used and cannot be deleted. Status changed to expired.',
                'usage_count' => $usageCount,
            ]);
        }

        $offer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Offer deleted successfully.',
        ]);
    }

    /**
     * Activate an offer.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function activate(int $id): JsonResponse
    {
        $offer = offers::find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }

        // Check if offer is expired
        if ($offer->valid_to < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot activate expired offer. Update validity dates first.',
            ], 400);
        }

        $offer->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Offer activated successfully.',
            'data' => $offer->fresh(),
        ]);
    }

    /**
     * Deactivate an offer.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deactivate(int $id): JsonResponse
    {
        $offer = offers::find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }

        $offer->update(['status' => 'inactive']);

        return response()->json([
            'success' => true,
            'message' => 'Offer deactivated successfully.',
            'data' => $offer->fresh(),
        ]);
    }

    /**
     * Get offer usage analytics.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function usage(Request $request, int $id): JsonResponse
    {
        $offer = offers::find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }

        $period = $request->input('period', 30);
        $startDate = now()->subDays($period)->startOfDay();

        // Overall statistics
        $overallStats = OfferUsage::where('offer_id', $id)
            ->where('reversed', false)
            ->selectRaw('COUNT(*) as total_uses, SUM(discount_amount) as total_discount, COUNT(DISTINCT customer_id) as unique_customers')
            ->first();

        // Period statistics
        $periodStats = OfferUsage::where('offer_id', $id)
            ->where('reversed', false)
            ->where('used_at', '>=', $startDate)
            ->selectRaw('COUNT(*) as uses, SUM(discount_amount) as discount')
            ->first();

        // Daily breakdown (for SQLite compatibility)
        $dailyUsage = OfferUsage::where('offer_id', $id)
            ->where('reversed', false)
            ->where('used_at', '>=', $startDate)
            ->selectRaw("date(used_at) as date, COUNT(*) as uses, SUM(discount_amount) as discount")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Recent usage
        $recentUsage = OfferUsage::with('customer:id,first_name,last_name,email')
            ->where('offer_id', $id)
            ->where('reversed', false)
            ->orderByDesc('used_at')
            ->limit(20)
            ->get(['id', 'customer_id', 'order_id', 'discount_amount', 'used_at']);

        return response()->json([
            'success' => true,
            'offer' => [
                'id' => $offer->id,
                'title' => $offer->title,
                'coupon_code' => $offer->coupon_code,
                'status' => $offer->status,
            ],
            'overall_stats' => [
                'total_uses' => $overallStats->total_uses ?? 0,
                'total_discount_given' => round($overallStats->total_discount ?? 0, 2),
                'unique_customers' => $overallStats->unique_customers ?? 0,
            ],
            'period_stats' => [
                'period_days' => $period,
                'uses' => $periodStats->uses ?? 0,
                'discount' => round($periodStats->discount ?? 0, 2),
            ],
            'daily_breakdown' => $dailyUsage,
            'recent_usage' => $recentUsage,
        ]);
    }

    /**
     * Get offer types for dropdown.
     *
     * @return JsonResponse
     */
    public function getOfferTypes(): JsonResponse
    {
        $offerTypes = offerTypeMaster::select('id', 'offer_type', 'offer_type_option', 'apply_to', 'apply_to_option')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $offerTypes,
        ]);
    }

    /**
     * Bulk update offer status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'offer_ids' => 'required|array',
            'offer_ids.*' => 'integer|exists:offers,id',
            'status' => 'required|in:active,inactive,expired',
        ]);

        $updated = offers::whereIn('id', $validated['offer_ids'])
            ->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} offers updated successfully.",
            'updated_count' => $updated,
        ]);
    }

    /**
     * Get all offers summary for dashboard.
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $totalOffers = offers::count();
        $activeOffers = offers::where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where('valid_to', '>=', now())
            ->count();
        $expiredOffers = offers::where('valid_to', '<', now())->count();
        $inactiveOffers = offers::where('status', 'inactive')->count();

        // Total discount given (last 30 days)
        $totalDiscountGiven = OfferUsage::where('reversed', false)
            ->where('used_at', '>=', now()->subDays(30))
            ->sum('discount_amount');

        // Most used offers
        $topOffers = OfferUsage::where('reversed', false)
            ->select('offer_id', DB::raw('COUNT(*) as usage_count'), DB::raw('SUM(discount_amount) as total_discount'))
            ->groupBy('offer_id')
            ->orderByDesc('usage_count')
            ->limit(5)
            ->with('offer:id,title,coupon_code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_offers' => $totalOffers,
                'active_offers' => $activeOffers,
                'expired_offers' => $expiredOffers,
                'inactive_offers' => $inactiveOffers,
                'discount_given_30d' => round($totalDiscountGiven, 2),
                'top_offers' => $topOffers,
            ],
        ]);
    }
}
