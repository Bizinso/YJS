<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRequest;
use App\Models\ExchangeRequestItem;
use App\Models\ReturnPolicySetting;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Customer Exchange Controller
 *
 * Handles customer-facing exchange request operations.
 */
class ExchangeController extends Controller
{
    /**
     * Get all exchange requests for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $exchanges = ExchangeRequest::forUser($user->id)
                ->with([
                    'order:id,custom_order_code,total_amount',
                    'items.originalProduct:id,name,main_image',
                    'items.newProduct:id,name,main_image',
                ])
                ->when($request->status, function ($query, $status) {
                    $query->where('status', $status);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 10);

            return response()->json([
                'success' => true,
                'data' => $exchanges,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch exchange requests',
            ], 500);
        }
    }

    /**
     * Get exchange policy and reasons
     */
    public function getPolicy(): JsonResponse
    {
        try {
            $policy = ReturnPolicySetting::getActive();

            if (!$policy) {
                $policy = [
                    'exchange_window_days' => 15,
                    'require_images' => true,
                    'require_reason' => true,
                    'exchange_reasons' => ReturnPolicySetting::getDefaultExchangeReasons(),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $policy,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch exchange policy',
            ], 500);
        }
    }

    /**
     * Check if an order is eligible for exchange
     */
    public function checkEligibility(int $orderId): JsonResponse
    {
        try {
            $user = Auth::user();
            $order = Order::where('id', $orderId)
                ->where('customer_id', $user->id)
                ->with('orderProducts.product')
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $policy = ReturnPolicySetting::getActive();
            $eligibility = [
                'eligible' => true,
                'reason' => null,
                'eligible_items' => [],
                'exchange_window_ends' => null,
            ];

            // Check order status
            if ($order->status !== 'delivered') {
                $eligibility['eligible'] = false;
                $eligibility['reason'] = 'Only delivered orders can be exchanged';
                return response()->json(['success' => true, 'data' => $eligibility]);
            }

            // Check exchange window
            if ($policy && $order->delivered_at) {
                $windowEnd = $order->delivered_at->copy()->addDays($policy->exchange_window_days);
                $eligibility['exchange_window_ends'] = $windowEnd->toDateTimeString();

                if (now() > $windowEnd) {
                    $eligibility['eligible'] = false;
                    $eligibility['reason'] = "Exchange window of {$policy->exchange_window_days} days has expired";
                    return response()->json(['success' => true, 'data' => $eligibility]);
                }
            }

            // Check existing exchange request
            $existingExchange = ExchangeRequest::where('order_id', $orderId)
                ->whereNotIn('status', [ExchangeRequest::STATUS_REJECTED, ExchangeRequest::STATUS_CLOSED])
                ->exists();

            if ($existingExchange) {
                $eligibility['eligible'] = false;
                $eligibility['reason'] = 'An exchange request already exists for this order';
                return response()->json(['success' => true, 'data' => $eligibility]);
            }

            // Check each item's eligibility
            foreach ($order->orderProducts as $item) {
                $eligibility['eligible_items'][] = [
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Unknown',
                    'quantity' => $item->quantity,
                    'eligible' => true,
                    'reason' => null,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $eligibility,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to check eligibility',
            ], 500);
        }
    }

    /**
     * Get available products for exchange
     */
    public function getExchangeOptions(int $productId): JsonResponse
    {
        try {
            $product = Product::find($productId);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            // Get similar products (same category) or variants
            $options = Product::where('status', 'active')
                ->where('id', '!=', $productId)
                ->where(function ($query) use ($product) {
                    $query->where('category_id', $product->category_id)
                        ->orWhere('sub_category_id', $product->sub_category_id)
                        ->orWhere('parent_id', $product->parent_id ?: $product->id)
                        ->orWhere('id', $product->parent_id);
                })
                ->where('available_stock', '>', 0)
                ->select(['id', 'name', 'sku', 'main_image', 'base_price', 'final_price', 'available_stock'])
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'original_product' => $product->only(['id', 'name', 'sku', 'main_image', 'base_price', 'final_price']),
                    'exchange_options' => $options,
                ],
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch exchange options',
            ], 500);
        }
    }

    /**
     * Create a new exchange request
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'reason_code' => 'required|string|max:50',
            'reason_description' => 'nullable|string|max:1000',
            'customer_notes' => 'nullable|string|max:1000',
            'images' => 'nullable|array',
            'shipping_address_id' => 'nullable|exists:customer_addresses,id',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_products,id',
            'items.*.original_quantity' => 'required|integer|min:1',
            'items.*.new_product_id' => 'required|exists:products,id',
            'items.*.new_quantity' => 'required|integer|min:1',
            'items.*.reason_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();

            // Verify order ownership
            $order = Order::where('id', $request->order_id)
                ->where('customer_id', $user->id)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Check eligibility
            if ($order->status !== 'delivered') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only delivered orders can be exchanged',
                ], 422);
            }

            DB::beginTransaction();

            // Create exchange request
            $exchangeRequest = ExchangeRequest::create([
                'order_id' => $request->order_id,
                'user_id' => $user->id,
                'reason_code' => $request->reason_code,
                'reason_description' => $request->reason_description,
                'customer_notes' => $request->customer_notes,
                'images' => $request->images,
                'shipping_address_id' => $request->shipping_address_id,
                'status' => ExchangeRequest::STATUS_PENDING,
            ]);

            // Create exchange items
            foreach ($request->items as $item) {
                $orderItem = $order->orderProducts()->find($item['order_item_id']);

                if (!$orderItem) {
                    throw new \Exception("Invalid order item: {$item['order_item_id']}");
                }

                $newProduct = Product::find($item['new_product_id']);

                // Calculate price difference
                $originalPrice = $orderItem->price ?? 0;
                $originalTotal = $originalPrice * $item['original_quantity'];
                $newPrice = $newProduct->final_price ?? $newProduct->base_price ?? 0;
                $newTotal = $newPrice * $item['new_quantity'];

                ExchangeRequestItem::create([
                    'exchange_request_id' => $exchangeRequest->id,
                    'original_order_item_id' => $item['order_item_id'],
                    'original_product_id' => $orderItem->product_id,
                    'original_quantity' => $item['original_quantity'],
                    'new_product_id' => $item['new_product_id'],
                    'new_quantity' => $item['new_quantity'],
                    'reason_code' => $item['reason_code'] ?? $request->reason_code,
                    'price_difference' => $newTotal - $originalTotal,
                ]);
            }

            // Calculate total price difference
            $exchangeRequest->calculatePriceDifference();

            // Log status
            $exchangeRequest->updateStatus(ExchangeRequest::STATUS_PENDING, 'Exchange request created by customer');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Exchange request created successfully',
                'data' => $exchangeRequest->load(['items.originalProduct:id,name', 'items.newProduct:id,name,final_price']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create exchange request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific exchange request
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $exchangeRequest = ExchangeRequest::forUser($user->id)
                ->with([
                    'order:id,custom_order_code,total_amount,status',
                    'items.originalProduct:id,name,main_image,sku',
                    'items.newProduct:id,name,main_image,sku,final_price',
                    'items.orderItem',
                    'shippingAddress',
                    'statusHistory.changedByUser:id,name',
                ])
                ->find($id);

            if (!$exchangeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $exchangeRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch exchange request',
            ], 500);
        }
    }

    /**
     * Cancel an exchange request
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $exchangeRequest = ExchangeRequest::forUser($user->id)->find($id);

            if (!$exchangeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request not found',
                ], 404);
            }

            if (!$exchangeRequest->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This exchange request cannot be cancelled',
                ], 422);
            }

            $exchangeRequest->updateStatus(ExchangeRequest::STATUS_CLOSED, 'Cancelled by customer', $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Exchange request cancelled successfully',
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel exchange request',
            ], 500);
        }
    }

    /**
     * Get exchange tracking information
     */
    public function tracking(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $exchangeRequest = ExchangeRequest::forUser($user->id)
                ->select([
                    'id', 'exchange_code', 'status', 'tracking_number',
                    'courier_name', 'shipped_at', 'delivered_at',
                    'price_difference', 'adjustment_type', 'adjustment_paid'
                ])
                ->with('statusHistory:id,request_id,from_status,to_status,notes,created_at')
                ->find($id);

            if (!$exchangeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $exchangeRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tracking information',
            ], 500);
        }
    }
}
