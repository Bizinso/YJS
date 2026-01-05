<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Cart Controller
 *
 * Handles shopping cart operations including adding, updating,
 * removing items, and cart synchronization from local storage.
 *
 * @package App\Http\Controllers
 */
class CartController extends Controller
{
    /**
     * Get user's cart with calculated totals.
     *
     * Returns all cart items with product details, charges,
     * taxes, and aggregated cart totals.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $userId = auth()->id();

        $cartItems = Cart::with(['product.charges', 'product.taxCharges'])
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->get();

        $cartSubtotal = $cartItems->sum('product_base_price');
        $cartTotal = $cartItems->sum('cart_total');
        $totalCharges = $cartItems->sum('charges_total');
        $totalTaxes = $cartItems->sum('tax_total');
        $totalDiscount = $cartItems->sum('total_discount');

        return response()->json([
            'success' => true,
            'data' => $cartItems->values(),
            'summary' => [
                'subtotal' => round($cartSubtotal, 2),
                'total_charges' => round($totalCharges, 2),
                'total_taxes' => round($totalTaxes, 2),
                'total_discount' => round($totalDiscount, 2),
                'cart_total' => round($cartTotal, 2),
                'items_count' => $cartItems->count(),
            ],
        ]);
    }

    /**
     * Add product to cart or update quantity.
     *
     * Validates stock availability and calculates pricing
     * including charges and taxes.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'action' => 'nullable|in:add,buy_now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $productId = $request->product_id;
        $requestedQty = $request->quantity;

        $product = Product::with(['charges', 'taxCharges'])->find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        // Check if cart item already exists
        $cartItem = Cart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        // Stock validation
        $currentQtyInCart = $cartItem ? $cartItem->quantity : 0;
        $totalRequestedQty = $currentQtyInCart + $requestedQty;

        if ($totalRequestedQty > $product->available_stock) {
            $availableToAdd = $product->available_stock - $currentQtyInCart;
            return response()->json([
                'success' => false,
                'message' => $cartItem
                    ? "Only {$availableToAdd} more can be added. You already have {$currentQtyInCart} in cart."
                    : "Only {$product->available_stock} available in stock.",
                'available_stock' => $product->available_stock,
            ], 400);
        }

        // Calculate pricing
        $totalCharges = $this->calculateProductCharges($product);
        $totalTaxes = $product->taxCharges ? $product->taxCharges->sum('amount') : 0;
        $finalPricePerUnit = $product->base_price + $totalCharges + $totalTaxes;

        // Create or update cart item
        if ($cartItem) {
            $cartItem->quantity = $totalRequestedQty;
        } else {
            $cartItem = new Cart();
            $cartItem->user_id = $userId;
            $cartItem->product_id = $productId;
            $cartItem->quantity = $requestedQty;
        }

        $cartItem->product_base_price = $product->base_price;
        $cartItem->charges_total = $totalCharges;
        $cartItem->tax_total = $totalTaxes;
        $cartItem->final_price = $finalPricePerUnit;
        $cartItem->cart_total = $finalPricePerUnit * $cartItem->quantity;
        $cartItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully.',
            'data' => $cartItem,
            'cart_total' => $this->calculateCartTotal($userId),
        ]);
    }

    /**
     * Update cart item quantity.
     *
     * @param Request $request
     * @param int $id Cart item ID
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();

        $cartItem = Cart::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found.',
            ], 404);
        }

        $product = Product::with(['charges', 'taxCharges'])->find($cartItem->product_id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product no longer available.',
            ], 404);
        }

        $newQuantity = $request->quantity;

        // Stock validation
        if ($newQuantity > $product->available_stock) {
            return response()->json([
                'success' => false,
                'message' => "Only {$product->available_stock} available in stock.",
                'available_stock' => $product->available_stock,
            ], 400);
        }

        // Recalculate pricing
        $totalCharges = $this->calculateProductCharges($product);
        $totalTaxes = $product->taxCharges ? $product->taxCharges->sum('amount') : 0;
        $finalPricePerUnit = $product->base_price + $totalCharges + $totalTaxes;

        $cartItem->quantity = $newQuantity;
        $cartItem->product_base_price = $product->base_price;
        $cartItem->charges_total = $totalCharges;
        $cartItem->tax_total = $totalTaxes;
        $cartItem->final_price = $finalPricePerUnit;
        $cartItem->cart_total = $finalPricePerUnit * $newQuantity;
        $cartItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully.',
            'data' => $cartItem,
            'cart_total' => $this->calculateCartTotal($userId),
        ]);
    }

    /**
     * Remove item from cart.
     *
     * @param int $id Cart item ID
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $userId = auth()->id();

        $cartItem = Cart::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found.',
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
            'cart_total' => $this->calculateCartTotal($userId),
        ]);
    }

    /**
     * Clear all items from cart.
     *
     * @return JsonResponse
     */
    public function clear(): JsonResponse
    {
        $userId = auth()->id();

        $deleted = Cart::where('user_id', $userId)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deleted} item(s) removed from cart.",
            'items_removed' => $deleted,
        ]);
    }

    /**
     * Get cart item count.
     *
     * @return JsonResponse
     */
    public function cartCount(): JsonResponse
    {
        $userId = auth()->id();

        $count = Cart::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Sync cart items from local storage.
     *
     * Merges guest cart items after user login.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncFromLocalStorage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }

        $synced = 0;
        $errors = [];

        foreach ($request->items as $item) {
            try {
                $product = Product::with(['charges', 'taxCharges'])->find($item['product_id']);

                if (!$product) {
                    $errors[] = [
                        'product_id' => $item['product_id'],
                        'error' => 'Product not found',
                    ];
                    continue;
                }

                // Calculate pricing
                $totalCharges = $this->calculateProductCharges($product);
                $totalTaxes = $product->taxCharges ? $product->taxCharges->sum('amount') : 0;
                $finalPricePerUnit = $product->base_price + $totalCharges + $totalTaxes;

                // Check existing cart item
                $cartItem = Cart::where('user_id', $userId)
                    ->where('product_id', $item['product_id'])
                    ->first();

                // Calculate requested quantity
                $requestedQty = $item['quantity'];
                $currentQtyInCart = $cartItem ? $cartItem->quantity : 0;
                $totalRequestedQty = $currentQtyInCart + $requestedQty;

                // Stock validation - adjust to available stock if needed
                if ($totalRequestedQty > $product->available_stock) {
                    $requestedQty = max(0, $product->available_stock - $currentQtyInCart);
                    if ($requestedQty <= 0) {
                        $errors[] = [
                            'product_id' => $item['product_id'],
                            'error' => 'Insufficient stock',
                        ];
                        continue;
                    }
                    $totalRequestedQty = $currentQtyInCart + $requestedQty;
                }

                // Update or create cart item
                if ($cartItem) {
                    $cartItem->quantity = $totalRequestedQty;
                } else {
                    $cartItem = new Cart();
                    $cartItem->user_id = $userId;
                    $cartItem->product_id = $item['product_id'];
                    $cartItem->quantity = $requestedQty;
                }

                $cartItem->product_base_price = $product->base_price;
                $cartItem->charges_total = $totalCharges;
                $cartItem->tax_total = $totalTaxes;
                $cartItem->total_discount = 0;
                $cartItem->final_price = $finalPricePerUnit;
                $cartItem->cart_total = $finalPricePerUnit * $cartItem->quantity;
                $cartItem->save();

                $synced++;
            } catch (\Exception $e) {
                $errors[] = [
                    'product_id' => $item['product_id'] ?? null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Synced {$synced} item(s) to cart.",
            'synced' => $synced,
            'errors' => $errors,
            'cart_total' => $this->calculateCartTotal($userId),
        ]);
    }

    /**
     * Calculate product charges based on charge rules.
     *
     * @param Product $product
     * @return float
     */
    private function calculateProductCharges(Product $product): float
    {
        $basePrice = (float) $product->base_price;
        $totalCharges = 0;

        if (!$product->charges) {
            return 0;
        }

        foreach ($product->charges as $charge) {
            $chargeBase = $basePrice;

            // Metal Weight Cost calculation for jewellery
            if ($charge->primary_cost === 'Metal Weight Cost') {
                $metalBasePrice = DB::table('metal_types')
                    ->where('metal_name_id', $product->material_type_id)
                    ->where('purity_id', $product->purity_karat_id)
                    ->value('price_per_gram') ?? 0;

                $chargeBase = $metalBasePrice * (float) $product->metal_weight;
            }

            // Apply charge type (percentage or fixed)
            if ($charge->type === "Percentage (%)") {
                $totalCharges += ($chargeBase * $charge->value) / 100;
            } else {
                $totalCharges += (float) $charge->value;
            }
        }

        return round($totalCharges, 2);
    }

    /**
     * Calculate total cart value for user.
     *
     * @param int $userId
     * @return float
     */
    private function calculateCartTotal(int $userId): float
    {
        return round(Cart::where('user_id', $userId)->sum('cart_total'), 2);
    }
}
