<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\ReturnPolicySetting;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Customer Return Controller
 *
 * Handles customer-facing return request operations.
 */
class ReturnController extends Controller
{
    /**
     * Get all return requests for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $returns = ReturnRequest::forUser($user->id)
                ->with(['order:id,order_number,total_amount', 'items.product:id,name,main_image'])
                ->when($request->status, function ($query, $status) {
                    $query->where('status', $status);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 10);

            return response()->json([
                'success' => true,
                'data' => $returns,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch return requests',
            ], 500);
        }
    }

    /**
     * Get return policy and reasons
     */
    public function getPolicy(): JsonResponse
    {
        try {
            $policy = ReturnPolicySetting::getActive();

            if (!$policy) {
                $policy = [
                    'return_window_days' => 7,
                    'require_images' => true,
                    'require_reason' => true,
                    'return_reasons' => ReturnPolicySetting::getDefaultReturnReasons(),
                    'return_instructions' => null,
                    'terms_and_conditions' => null,
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
                'message' => 'Failed to fetch return policy',
            ], 500);
        }
    }

    /**
     * Check if an order is eligible for return
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
                'return_window_ends' => null,
            ];

            // Check order status
            if ($order->status !== 'delivered') {
                $eligibility['eligible'] = false;
                $eligibility['reason'] = 'Only delivered orders can be returned';
                return response()->json(['success' => true, 'data' => $eligibility]);
            }

            // Check return window
            if ($policy && $order->delivered_at) {
                $windowEnd = $order->delivered_at->copy()->addDays($policy->return_window_days);
                $eligibility['return_window_ends'] = $windowEnd->toDateTimeString();

                if (now() > $windowEnd) {
                    $eligibility['eligible'] = false;
                    $eligibility['reason'] = "Return window of {$policy->return_window_days} days has expired";
                    return response()->json(['success' => true, 'data' => $eligibility]);
                }
            }

            // Check existing return request
            $existingReturn = ReturnRequest::where('order_id', $orderId)
                ->whereNotIn('status', [ReturnRequest::STATUS_REJECTED, ReturnRequest::STATUS_CLOSED])
                ->exists();

            if ($existingReturn) {
                $eligibility['eligible'] = false;
                $eligibility['reason'] = 'A return request already exists for this order';
                return response()->json(['success' => true, 'data' => $eligibility]);
            }

            // Check each item's eligibility
            foreach ($order->orderProducts as $item) {
                $itemEligible = true;
                $itemReason = null;

                // Check if category is returnable
                if ($policy && $item->product && !$policy->isCategoryReturnable($item->product->category_id)) {
                    $itemEligible = false;
                    $itemReason = 'This product category is not eligible for return';
                }

                $eligibility['eligible_items'][] = [
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Unknown',
                    'quantity' => $item->quantity,
                    'eligible' => $itemEligible,
                    'reason' => $itemReason,
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
     * Create a new return request
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'return_type' => 'required|in:refund,store_credit',
            'reason_code' => 'required|string|max:50',
            'reason_description' => 'nullable|string|max:1000',
            'customer_notes' => 'nullable|string|max:1000',
            'images' => 'nullable|array',
            'images.*' => 'string', // Base64 or URL
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason_code' => 'nullable|string|max:50',
            'items.*.reason_description' => 'nullable|string|max:500',
            'items.*.images' => 'nullable|array',
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
                    'message' => 'Only delivered orders can be returned',
                ], 422);
            }

            DB::beginTransaction();

            // Create return request
            $returnRequest = ReturnRequest::create([
                'order_id' => $request->order_id,
                'user_id' => $user->id,
                'return_type' => $request->return_type,
                'reason_code' => $request->reason_code,
                'reason_description' => $request->reason_description,
                'customer_notes' => $request->customer_notes,
                'images' => $request->images,
                'status' => ReturnRequest::STATUS_PENDING,
            ]);

            // Create return items
            foreach ($request->items as $item) {
                $orderItem = $order->orderProducts()->find($item['order_item_id']);

                if (!$orderItem) {
                    throw new \Exception("Invalid order item: {$item['order_item_id']}");
                }

                ReturnRequestItem::create([
                    'return_request_id' => $returnRequest->id,
                    'order_item_id' => $item['order_item_id'],
                    'product_id' => $orderItem->product_id,
                    'quantity' => $item['quantity'],
                    'reason_code' => $item['reason_code'] ?? $request->reason_code,
                    'reason_description' => $item['reason_description'] ?? null,
                    'images' => $item['images'] ?? null,
                    'refund_amount' => ($orderItem->price ?? 0) * $item['quantity'],
                ]);
            }

            // Calculate total refund amount
            $returnRequest->refund_amount = $returnRequest->items->sum('refund_amount');
            $returnRequest->save();

            // Log status
            $returnRequest->updateStatus(ReturnRequest::STATUS_PENDING, 'Return request created by customer');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Return request created successfully',
                'data' => $returnRequest->load('items.product:id,name,main_image'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create return request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific return request
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $returnRequest = ReturnRequest::forUser($user->id)
                ->with([
                    'order:id,order_number,total_amount,status',
                    'items.product:id,name,main_image,sku',
                    'items.orderItem',
                    'statusHistory.changedByUser:id,name',
                ])
                ->find($id);

            if (!$returnRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $returnRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch return request',
            ], 500);
        }
    }

    /**
     * Cancel a return request
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $returnRequest = ReturnRequest::forUser($user->id)->find($id);

            if (!$returnRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found',
                ], 404);
            }

            if (!$returnRequest->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This return request cannot be cancelled',
                ], 422);
            }

            $returnRequest->updateStatus(ReturnRequest::STATUS_CLOSED, 'Cancelled by customer', $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Return request cancelled successfully',
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel return request',
            ], 500);
        }
    }

    /**
     * Get return tracking information
     */
    public function tracking(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $returnRequest = ReturnRequest::forUser($user->id)
                ->select([
                    'id', 'return_code', 'status', 'pickup_scheduled_at',
                    'picked_up_at', 'pickup_tracking_number', 'pickup_courier',
                    'inspection_result', 'refund_initiated_at', 'refund_completed_at'
                ])
                ->with('statusHistory:id,request_id,from_status,to_status,notes,created_at')
                ->find($id);

            if (!$returnRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $returnRequest,
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
