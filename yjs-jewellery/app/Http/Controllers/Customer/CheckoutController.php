<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Services\Order\OrderService;
use App\Services\Shipping\ShiprocketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Checkout Controller
 *
 * Handles the checkout process for customers including
 * cart validation, address selection, order creation,
 * and serviceability checks.
 *
 * @package App\Http\Controllers\Customer
 */
class CheckoutController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private ShiprocketService $shiprocketService
    ) {}

    /**
     * Get checkout summary.
     *
     * Returns cart contents, addresses, and calculated totals
     * for the checkout page.
     *
     * @return JsonResponse
     */
    public function getSummary(): JsonResponse
    {
        $user = auth()->user();

        // Get cart items
        $cartItems = Cart::with(['product:id,name,slug,main_image,base_price,available_stock'])
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty.',
            ], 400);
        }

        // Check stock availability for all items
        $stockIssues = [];
        foreach ($cartItems as $item) {
            if (!$item->product) {
                $stockIssues[] = [
                    'cart_id' => $item->id,
                    'message' => 'Product no longer available',
                ];
            } elseif ($item->product->available_stock < $item->quantity) {
                $stockIssues[] = [
                    'cart_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'requested' => $item->quantity,
                    'available' => $item->product->available_stock,
                    'message' => "Only {$item->product->available_stock} available",
                ];
            }
        }

        // Get user addresses through customer relationship
        $customer = $user->customer;
        $addresses = $customer
            ? $customer->addresses()->orderBy('is_default', 'desc')->get()
            : collect();

        // Calculate totals
        $subtotal = $cartItems->sum('product_base_price');
        $totalCharges = $cartItems->sum('charges_total');
        $totalTaxes = $cartItems->sum('tax_total');
        $cartTotal = $cartItems->sum('cart_total');

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $cartItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product' => $item->product,
                        'quantity' => $item->quantity,
                        'base_price' => $item->product_base_price,
                        'charges' => $item->charges_total,
                        'taxes' => $item->tax_total,
                        'line_total' => $item->cart_total,
                    ];
                }),
                'totals' => [
                    'subtotal' => round($subtotal, 2),
                    'charges' => round($totalCharges, 2),
                    'taxes' => round($totalTaxes, 2),
                    'shipping' => 0, // Will be calculated after address selection
                    'discount' => 0, // Will be calculated after offer application
                    'total' => round($cartTotal, 2),
                ],
                'addresses' => $addresses,
                'default_address_id' => $addresses->firstWhere('is_default', true)?->id,
                'stock_issues' => $stockIssues,
                'can_checkout' => empty($stockIssues),
            ],
        ]);
    }

    /**
     * Check delivery serviceability for a pincode.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkServiceability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pincode' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid 6-digit pincode.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth()->user();

            // Get cart weight for accurate shipping estimate
            $cartItems = Cart::with('product')
                ->where('user_id', $user->id)
                ->get();

            $totalWeight = $cartItems->sum(function ($item) {
                return ($item->product->weight ?? 0.5) * $item->quantity;
            });

            // Check serviceability
            $result = $this->shiprocketService->checkServiceability(
                $request->pincode,
                $totalWeight > 0 ? $totalWeight : 0.5
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'serviceable' => $result['serviceable'] ?? false,
                    'cod_available' => $result['cod_available'] ?? false,
                    'estimated_delivery' => $result['etd'] ?? null,
                    'shipping_charges' => $result['shipping_charges'] ?? 0,
                    'courier_name' => $result['courier_name'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to check serviceability. Please try again.',
            ], 500);
        }
    }

    /**
     * Create order from cart.
     *
     * Validates cart, addresses, and creates order with
     * payment gateway integration.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'billing_address_id' => 'required|exists:customer_addresses,id',
            'shipping_address_id' => 'required|exists:customer_addresses,id',
            'offer_id' => 'nullable|exists:offers,id',
            'coupon_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'payment_method' => 'nullable|in:razorpay,cod',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();

        // Verify addresses belong to user through customer relationship
        $customer = $user->customer;
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer profile not found.',
            ], 400);
        }

        $billingAddress = $customer->addresses()
            ->where('id', $request->billing_address_id)
            ->first();

        $shippingAddress = $customer->addresses()
            ->where('id', $request->shipping_address_id)
            ->first();

        if (!$billingAddress || !$shippingAddress) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid address selected.',
            ], 400);
        }

        // Create order through service
        $result = $this->orderService->createFromCart(
            $user->id,
            $request->billing_address_id,
            $request->shipping_address_id,
            $request->offer_id,
            $request->coupon_code,
            $request->notes
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => [
                'order_id' => $result['order_id'],
                'order_code' => $result['order_code'],
                'order_total' => $result['order_total'],
                'discount_applied' => $result['discount_applied'],
                'payment' => $result['payment'],
            ],
        ]);
    }

    /**
     * Apply coupon code to cart.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a coupon code.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();

        // Get cart items
        $cartItems = Cart::with('product')
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty.',
            ], 400);
        }

        $cartTotal = $cartItems->sum('cart_total');
        $productIds = $cartItems->pluck('product_id')->toArray();
        $categoryIds = $cartItems->pluck('product.category_id')->filter()->unique()->toArray();

        // Validate coupon through OfferService
        $offerService = app(\App\Services\Offers\OfferService::class);
        $result = $offerService->validateCoupon(
            $request->coupon_code,
            $user->id,
            $cartTotal,
            $productIds,
            $categoryIds
        );

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Invalid coupon code.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Coupon applied successfully.',
            'data' => [
                'offer_id' => $result['offer_id'],
                'offer_title' => $result['offer_title'],
                'discount_amount' => $result['discount'],
                'new_total' => $cartTotal - $result['discount'],
            ],
        ]);
    }

    /**
     * Remove applied coupon.
     *
     * @return JsonResponse
     */
    public function removeCoupon(): JsonResponse
    {
        // Coupons are applied at order creation time, not stored in cart
        // This endpoint is for frontend state management
        return response()->json([
            'success' => true,
            'message' => 'Coupon removed.',
        ]);
    }

    /**
     * Validate cart items before checkout.
     *
     * Checks stock availability and product status.
     *
     * @return JsonResponse
     */
    public function validateCart(): JsonResponse
    {
        $user = auth()->user();

        $cartItems = Cart::with(['product'])
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty.',
                'can_checkout' => false,
            ], 400);
        }

        $issues = [];
        $validItems = [];

        foreach ($cartItems as $item) {
            if (!$item->product) {
                $issues[] = [
                    'cart_id' => $item->id,
                    'type' => 'unavailable',
                    'message' => 'Product no longer available',
                ];
                continue;
            }

            if ($item->product->status !== 'active') {
                $issues[] = [
                    'cart_id' => $item->id,
                    'product_id' => $item->product_id,
                    'type' => 'inactive',
                    'message' => "{$item->product->name} is no longer available",
                ];
                continue;
            }

            if ($item->product->available_stock < $item->quantity) {
                $issues[] = [
                    'cart_id' => $item->id,
                    'product_id' => $item->product_id,
                    'type' => 'stock',
                    'message' => "Only {$item->product->available_stock} of {$item->product->name} available",
                    'available' => $item->product->available_stock,
                    'requested' => $item->quantity,
                ];
                continue;
            }

            $validItems[] = $item->id;
        }

        return response()->json([
            'success' => empty($issues),
            'can_checkout' => empty($issues),
            'data' => [
                'valid_items' => count($validItems),
                'issues' => $issues,
            ],
            'message' => empty($issues)
                ? 'Cart is ready for checkout.'
                : 'Please resolve issues before checkout.',
        ]);
    }
}
