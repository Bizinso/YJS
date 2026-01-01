<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Services\Offers\OfferService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Enhanced Offers Controller
 * 
 * Uses EXISTING offers table fields: title, discount_type, discount_amount, etc.
 */
class OffersController extends Controller
{
    public function __construct(private OfferService $offerService) {}

    /**
     * Get applicable offers for current cart
     * GET /api/customer/offers/applicable
     */
    public function getApplicable(): JsonResponse
    {
        $userId = auth('customer')->id();
        
        // Get cart items
        $cartItems = Cart::with('product')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => true,
                'applicable' => [],
                'unavailable' => [],
                'message' => 'Add items to cart to see available offers',
            ]);
        }

        $cartTotal = $cartItems->sum('cart_total');
        $productIds = $cartItems->pluck('product_id')->toArray();
        $categoryIds = $cartItems->pluck('product.category_id')->filter()->unique()->toArray();

        $offers = $this->offerService->getApplicableOffers($userId, $cartTotal, $productIds, $categoryIds);
        
        return response()->json(array_merge(['success' => true], $offers));
    }

    /**
     * Apply offer to cart
     * POST /api/customer/offers/apply
     */
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'offer_id' => 'required|integer|exists:offers,id',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $userId = auth('customer')->id();
        
        // Get cart
        $cartItems = Cart::with('product')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'Cart is empty'], 400);
        }

        $cartTotal = $cartItems->sum('cart_total');
        $productIds = $cartItems->pluck('product_id')->toArray();
        $categoryIds = $cartItems->pluck('product.category_id')->filter()->unique()->toArray();

        $result = $this->offerService->applyOffer(
            $validated['offer_id'],
            $userId,
            $cartTotal,
            $productIds,
            $categoryIds,
            $validated['coupon_code'] ?? null
        );

        // Update cart with applied offer if successful
        if ($result['success']) {
            Cart::where('user_id', $userId)
                ->whereNull('deleted_at')
                ->update([
                    'applied_offers' => json_encode([
                        'offer_id' => $result['offer_id'],
                        'coupon_code' => $result['coupon_code'],
                        'discount_amount' => $result['discount_amount'],
                    ])
                ]);
        }

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Remove offer from cart
     * DELETE /api/customer/offers/remove
     */
    public function remove(): JsonResponse
    {
        $userId = auth('customer')->id();
        
        Cart::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->update(['applied_offers' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Offer removed',
        ]);
    }

    /**
     * Validate coupon code
     * POST /api/customer/offers/validate-coupon
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string|max:50',
        ]);

        $userId = auth('customer')->id();
        
        // Get cart
        $cartItems = Cart::with('product')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['valid' => false, 'error' => 'Cart is empty'], 400);
        }

        $cartTotal = $cartItems->sum('cart_total');
        $productIds = $cartItems->pluck('product_id')->toArray();
        $categoryIds = $cartItems->pluck('product.category_id')->filter()->unique()->toArray();

        $result = $this->offerService->validateCoupon(
            $validated['coupon_code'],
            $userId,
            $cartTotal,
            $productIds,
            $categoryIds
        );

        return response()->json($result, $result['valid'] ? 200 : 400);
    }
}
