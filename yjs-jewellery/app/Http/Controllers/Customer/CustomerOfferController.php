<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Services\Offers\AdvancedOfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer Offer Controller
 *
 * Handles customer-facing offer operations including:
 * - Getting applicable offers
 * - Validating coupon codes
 * - Viewing flash sales
 * - Viewing offer details
 */
class CustomerOfferController extends Controller
{
    public function __construct(
        private AdvancedOfferService $offerService
    ) {}

    /**
     * Get applicable offers for current cart.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getApplicableOffers(Request $request): JsonResponse
    {
        $userId = auth()->id();

        // Get cart items
        $cartItems = Cart::where('customer_id', $userId)
            ->with('product:id,category_id,base_price')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Cart is empty',
                'applicable' => [],
                'unavailable' => [],
            ]);
        }

        // Prepare cart data
        $cartTotal = $cartItems->sum(fn($item) => $item->product_base_price * $item->quantity);
        $productIds = $cartItems->pluck('product_id')->toArray();
        $categoryIds = $cartItems->pluck('product.category_id')->filter()->unique()->toArray();

        $cartItemsArray = $cartItems->map(fn($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'price' => $item->product_base_price,
        ])->toArray();

        $paymentMethod = $request->input('payment_method');
        $bank = $request->input('bank');

        $offers = $this->offerService->getApplicableOffers(
            $userId,
            $cartItemsArray,
            $cartTotal,
            $productIds,
            $categoryIds,
            $paymentMethod,
            $bank
        );

        return response()->json([
            'success' => true,
            ...$offers,
        ]);
    }

    /**
     * Validate coupon code.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string|max:50',
        ]);

        $userId = auth()->id();

        // Get cart items
        $cartItems = Cart::where('customer_id', $userId)
            ->with('product:id,category_id,base_price')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'valid' => false,
                'error' => 'Cart is empty',
                'error_code' => 'CART_EMPTY',
            ], 400);
        }

        $cartTotal = $cartItems->sum(fn($item) => $item->product_base_price * $item->quantity);
        $productIds = $cartItems->pluck('product_id')->toArray();
        $categoryIds = $cartItems->pluck('product.category_id')->filter()->unique()->toArray();

        $cartItemsArray = $cartItems->map(fn($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'price' => $item->product_base_price,
        ])->toArray();

        $result = $this->offerService->validateCoupon(
            $validated['coupon_code'],
            $userId,
            $cartItemsArray,
            $cartTotal,
            $productIds,
            $categoryIds
        );

        if (!$result['valid']) {
            return response()->json($result, 400);
        }

        return response()->json([
            'success' => true,
            ...$result,
            'cart_total' => $cartTotal,
            'new_total' => $cartTotal - $result['discount'],
        ]);
    }

    /**
     * Get flash sales.
     *
     * @return JsonResponse
     */
    public function getFlashSales(): JsonResponse
    {
        $flashSales = $this->offerService->getFlashSales();

        return response()->json([
            'success' => true,
            'flash_sales' => $flashSales,
            'count' => $flashSales->count(),
        ]);
    }

    /**
     * Get all active promotions for homepage.
     *
     * @return JsonResponse
     */
    public function getActivePromotions(): JsonResponse
    {
        $userId = auth()->id();
        $user = auth()->user();

        $flashSales = $this->offerService->getFlashSales();
        $birthdayOffers = $user ? $this->offerService->getBirthdayOffersForUser($user) : collect();

        // Get public offers (no coupon required)
        $publicOffers = \App\Models\Offers::active()
            ->whereNull('coupon_code')
            ->where('is_flash_sale', false)
            ->where('is_birthday_offer', false)
            ->orderBy('stack_priority', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($offer) => [
                'id' => $offer->id,
                'title' => $offer->title,
                'description' => $offer->description,
                'discount_type' => $offer->discount_type,
                'discount_amount' => $offer->discount_amount,
                'discount_percent' => $offer->discount_percent,
                'valid_until' => $offer->valid_to?->format('d M Y'),
                'badge_text' => $offer->badge_text,
                'badge_color' => $offer->badge_color,
                'banner_image' => $offer->banner_image,
                'apply_on' => $offer->apply_on,
                'apply_on_value' => $offer->apply_on_value,
            ]);

        return response()->json([
            'success' => true,
            'flash_sales' => $flashSales,
            'birthday_offers' => $birthdayOffers,
            'promotions' => $publicOffers,
        ]);
    }

    /**
     * Get offer details.
     *
     * @param int $offerId
     * @return JsonResponse
     */
    public function getOfferDetails(int $offerId): JsonResponse
    {
        $offer = \App\Models\Offers::active()->find($offerId);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'error' => 'Offer not found or expired',
            ], 404);
        }

        // Increment view count
        $offer->incrementViews();

        // Get applicable products if specified
        $products = [];
        if ($offer->apply_on === 'products' && !empty($offer->apply_on_value)) {
            $products = Product::whereIn('id', $offer->apply_on_value)
                ->select('id', 'name', 'base_price', 'category_id')
                ->with('media:id,product_id,file_path')
                ->limit(20)
                ->get();
        }

        return response()->json([
            'success' => true,
            'offer' => [
                'id' => $offer->id,
                'title' => $offer->title,
                'description' => $offer->description,
                'discount_type' => $offer->discount_type,
                'discount_amount' => $offer->discount_amount,
                'discount_percent' => $offer->discount_percent,
                'max_discount_amount' => $offer->max_discount_amount,
                'valid_from' => $offer->valid_from?->format('d M Y'),
                'valid_to' => $offer->valid_to?->format('d M Y'),
                'is_flash_sale' => $offer->is_flash_sale,
                'flash_remaining' => $offer->remaining_flash_stock,
                'is_bogo' => $offer->is_bogo,
                'buy_quantity' => $offer->buy_quantity,
                'get_quantity' => $offer->get_quantity,
                'get_discount_percent' => $offer->get_discount_percent,
                'is_combo' => $offer->is_combo,
                'combo_price' => $offer->combo_price,
                'has_free_gift' => $offer->has_free_gift,
                'badge_text' => $offer->badge_text,
                'badge_color' => $offer->badge_color,
                'banner_image' => $offer->banner_image,
                'terms_conditions' => $offer->terms_conditions,
                'has_coupon' => !empty($offer->coupon_code),
                'coupon_hint' => $offer->coupon_code
                    ? substr($offer->coupon_code, 0, 3) . '***'
                    : null,
            ],
            'applicable_products' => $products,
        ]);
    }

    /**
     * Get products with active offers.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductsWithOffers(Request $request): JsonResponse
    {
        $categoryId = $request->input('category_id');
        $limit = min($request->input('limit', 20), 50);

        // Get all product-specific offers
        $offers = \App\Models\Offers::active()
            ->where('apply_on', 'products')
            ->whereNotNull('apply_on_value')
            ->get();

        $productIds = $offers->pluck('apply_on_value')
            ->flatten()
            ->unique()
            ->toArray();

        $query = Product::whereIn('id', $productIds)
            ->select('id', 'name', 'base_price', 'category_id');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->with('media:id,product_id,file_path')
            ->limit($limit)
            ->get();

        // Map offers to products
        $productsWithOffers = $products->map(function ($product) use ($offers) {
            $productOffers = $offers->filter(function ($offer) use ($product) {
                return in_array($product->id, $offer->apply_on_value ?? []);
            });

            $bestOffer = $productOffers->sortByDesc(function ($offer) use ($product) {
                if ($offer->discount_type === 'percent') {
                    return $product->base_price * ($offer->discount_percent / 100);
                }
                return $offer->discount_amount ?? 0;
            })->first();

            return [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'base_price' => $product->base_price,
                    'image' => $product->media->first()?->file_path,
                ],
                'best_offer' => $bestOffer ? [
                    'id' => $bestOffer->id,
                    'title' => $bestOffer->title,
                    'discount_type' => $bestOffer->discount_type,
                    'discount_percent' => $bestOffer->discount_percent,
                    'discount_amount' => $bestOffer->discount_amount,
                    'badge_text' => $bestOffer->badge_text ?? 'OFFER',
                    'badge_color' => $bestOffer->badge_color ?? '#ff4444',
                ] : null,
                'offer_count' => $productOffers->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'products' => $productsWithOffers,
            'total' => $productsWithOffers->count(),
        ]);
    }
}
