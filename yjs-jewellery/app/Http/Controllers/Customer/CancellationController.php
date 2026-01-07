<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CancellationRequest;
use App\Models\CancellationRequestItem;
use App\Models\ReturnPolicySetting;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Customer Cancellation Controller
 *
 * Handles customer-facing order cancellation request operations.
 */
class CancellationController extends Controller
{
    /**
     * Get all cancellation requests for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $cancellations = CancellationRequest::forUser($user->id)
                ->with(['order:id,custom_order_code,total_amount'])
                ->when($request->status, function ($query, $status) {
                    $query->where('status', $status);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 10);

            return response()->json([
                'success' => true,
                'data' => $cancellations,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch cancellation requests',
            ], 500);
        }
    }

    /**
     * Get cancellation policy and reasons
     */
    public function getPolicy(): JsonResponse
    {
        try {
            $policy = ReturnPolicySetting::getActive();

            if (!$policy) {
                $policy = [
                    'cancellation_window_hours' => 24,
                    'auto_approve_cancellations' => false,
                    'cancellation_reasons' => ReturnPolicySetting::getDefaultCancellationReasons(),
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
                'message' => 'Failed to fetch cancellation policy',
            ], 500);
        }
    }

    /**
     * Check if an order is eligible for cancellation
     */
    public function checkEligibility(int $orderId): JsonResponse
    {
        try {
            $user = Auth::user();
            $order = Order::where('id', $orderId)
                ->where('customer_id', $user->id)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $result = CancellationRequest::canCancelOrder($order);

            $policy = ReturnPolicySetting::getActive();
            $windowEnd = null;

            if ($policy) {
                $windowEnd = $order->created_at->copy()->addHours($policy->cancellation_window_hours);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'can_cancel' => $result['can_cancel'],
                    'reason' => $result['reason'],
                    'order_status' => $order->status,
                    'cancellation_window_ends' => $windowEnd?->toDateTimeString(),
                    'allow_partial' => $policy?->allow_partial_returns ?? true,
                ],
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
     * Create a new cancellation request
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'cancellation_type' => 'required|in:full,partial',
            'reason_code' => 'required|string|max:50',
            'reason_description' => 'nullable|string|max:1000',
            'customer_notes' => 'nullable|string|max:1000',
            'items' => 'required_if:cancellation_type,partial|array',
            'items.*.order_item_id' => 'required_with:items|exists:order_products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
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
                ->with('orderProducts')
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Check eligibility
            $eligibility = CancellationRequest::canCancelOrder($order);
            if (!$eligibility['can_cancel']) {
                return response()->json([
                    'success' => false,
                    'message' => $eligibility['reason'],
                ], 422);
            }

            DB::beginTransaction();

            // Calculate order amount
            $orderAmount = $order->total_amount ?? $order->orderProducts->sum(function ($item) {
                return ($item->price ?? 0) * ($item->quantity ?? 1);
            });

            // Create cancellation request
            $cancellationRequest = CancellationRequest::create([
                'order_id' => $request->order_id,
                'user_id' => $user->id,
                'cancellation_type' => $request->cancellation_type,
                'reason_code' => $request->reason_code,
                'reason_description' => $request->reason_description,
                'customer_notes' => $request->customer_notes,
                'order_amount' => $orderAmount,
                'status' => CancellationRequest::STATUS_PENDING,
            ]);

            // For partial cancellations, create items
            if ($request->cancellation_type === CancellationRequest::TYPE_PARTIAL && $request->items) {
                foreach ($request->items as $item) {
                    $orderItem = $order->orderProducts->find($item['order_item_id']);

                    if (!$orderItem) {
                        throw new \Exception("Invalid order item: {$item['order_item_id']}");
                    }

                    $itemAmount = ($orderItem->price ?? 0) * $item['quantity'];

                    CancellationRequestItem::create([
                        'cancellation_request_id' => $cancellationRequest->id,
                        'order_item_id' => $item['order_item_id'],
                        'product_id' => $orderItem->product_id,
                        'quantity' => $item['quantity'],
                        'item_amount' => $itemAmount,
                        'refund_amount' => $itemAmount,
                    ]);
                }
            }

            // Calculate refund amount
            $cancellationRequest->refund_amount = $cancellationRequest->calculateRefundAmount();
            $cancellationRequest->save();

            // Log status
            $cancellationRequest->updateStatus(CancellationRequest::STATUS_PENDING, 'Cancellation request created by customer');

            // Check for auto-approval
            if ($cancellationRequest->checkAutoApproval()) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Cancellation request auto-approved',
                    'data' => $cancellationRequest->fresh()->load('items.product:id,name'),
                ], 201);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cancellation request created successfully',
                'data' => $cancellationRequest->load('items.product:id,name'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create cancellation request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific cancellation request
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $cancellationRequest = CancellationRequest::forUser($user->id)
                ->with([
                    'order:id,custom_order_code,total_amount,status',
                    'items.product:id,name,main_image,sku',
                    'items.orderItem',
                    'statusHistory.changedByUser:id,name',
                ])
                ->find($id);

            if (!$cancellationRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation request not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $cancellationRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch cancellation request',
            ], 500);
        }
    }

    /**
     * Get cancellation status/tracking
     */
    public function tracking(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $cancellationRequest = CancellationRequest::forUser($user->id)
                ->select([
                    'id', 'cancellation_code', 'status', 'cancellation_type',
                    'order_amount', 'cancellation_fee', 'refund_amount',
                    'refund_method', 'refund_initiated_at', 'refund_completed_at',
                    'auto_approved'
                ])
                ->with('statusHistory:id,request_id,from_status,to_status,notes,created_at')
                ->find($id);

            if (!$cancellationRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation request not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $cancellationRequest,
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
