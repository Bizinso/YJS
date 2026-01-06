<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\ReturnPolicySetting;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Return Controller
 *
 * Handles admin operations for return requests.
 */
class AdminReturnController extends Controller
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * Get return dashboard statistics
     */
    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'total' => ReturnRequest::count(),
                'pending' => ReturnRequest::where('status', ReturnRequest::STATUS_PENDING)->count(),
                'under_review' => ReturnRequest::where('status', ReturnRequest::STATUS_UNDER_REVIEW)->count(),
                'approved' => ReturnRequest::where('status', ReturnRequest::STATUS_APPROVED)->count(),
                'pickup_scheduled' => ReturnRequest::where('status', ReturnRequest::STATUS_PICKUP_SCHEDULED)->count(),
                'received' => ReturnRequest::where('status', ReturnRequest::STATUS_RECEIVED)->count(),
                'refund_pending' => ReturnRequest::whereIn('status', [
                    ReturnRequest::STATUS_INSPECTED,
                    ReturnRequest::STATUS_REFUND_INITIATED
                ])->count(),
                'completed' => ReturnRequest::where('status', ReturnRequest::STATUS_REFUND_COMPLETED)->count(),
                'rejected' => ReturnRequest::where('status', ReturnRequest::STATUS_REJECTED)->count(),
                'total_refund_amount' => ReturnRequest::where('status', ReturnRequest::STATUS_REFUND_COMPLETED)
                    ->sum('final_refund_amount'),
                'this_month' => ReturnRequest::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];

            // Recent returns needing attention
            $needsAction = ReturnRequest::needsAction()
                ->with(['user:id,name,email', 'order:id,order_number'])
                ->orderBy('created_at', 'asc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'needs_action' => $needsAction,
                ],
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
            ], 500);
        }
    }

    /**
     * Get all return requests with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ReturnRequest::with([
                'user:id,name,email',
                'order:id,order_number,total_amount',
                'items:id,return_request_id,product_id,quantity',
            ]);

            // Filters
            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->return_type) {
                $query->where('return_type', $request->return_type);
            }

            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->order_id) {
                $query->where('order_id', $request->order_id);
            }

            if ($request->return_code) {
                $query->where('return_code', 'like', "%{$request->return_code}%");
            }

            if ($request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('return_code', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('order', function ($oq) use ($search) {
                            $oq->where('order_number', 'like', "%{$search}%");
                        });
                });
            }

            // Sorting
            $sortBy = $request->sort_by ?? 'created_at';
            $sortDir = $request->sort_dir ?? 'desc';
            $query->orderBy($sortBy, $sortDir);

            $returns = $query->paginate($request->per_page ?? 20);

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
     * Get a specific return request
     */
    public function show(int $id): JsonResponse
    {
        try {
            $returnRequest = ReturnRequest::with([
                'user:id,name,email,phone',
                'order:id,order_number,total_amount,status,created_at',
                'order.orderProducts.product:id,name,sku,main_image',
                'items.product:id,name,sku,main_image',
                'items.orderItem',
                'reviewer:id,name,email',
                'statusHistory.changedByUser:id,name',
            ])->find($id);

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
     * Start reviewing a return request
     */
    public function startReview(int $id): JsonResponse
    {
        try {
            $returnRequest = ReturnRequest::find($id);

            if (!$returnRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found',
                ], 404);
            }

            if ($returnRequest->status !== ReturnRequest::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request is not in pending status',
                ], 422);
            }

            $returnRequest->updateStatus(
                ReturnRequest::STATUS_UNDER_REVIEW,
                'Review started',
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Review started',
                'data' => $returnRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to start review',
            ], 500);
        }
    }

    /**
     * Approve a return request
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
            'restocking_fee' => 'nullable|numeric|min:0',
            'shipping_deduction' => 'nullable|numeric|min:0',
            'items' => 'nullable|array',
            'items.*.id' => 'required_with:items|exists:return_request_items,id',
            'items.*.approved' => 'required_with:items|boolean',
            'items.*.refund_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $returnRequest = ReturnRequest::with('items')->find($id);

            if (!$returnRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found',
                ], 404);
            }

            // Process individual items if provided
            if ($request->items) {
                foreach ($request->items as $itemData) {
                    $item = $returnRequest->items->find($itemData['id']);
                    if ($item) {
                        if ($itemData['approved']) {
                            $item->approve();
                            if (isset($itemData['refund_amount'])) {
                                $item->refund_amount = $itemData['refund_amount'];
                                $item->save();
                            }
                        } else {
                            $item->reject();
                        }
                    }
                }
            } else {
                // Approve all items
                foreach ($returnRequest->items as $item) {
                    $item->approve();
                }
            }

            // Set fees
            $returnRequest->restocking_fee = $request->restocking_fee ?? 0;
            $returnRequest->shipping_deduction = $request->shipping_deduction ?? 0;
            $returnRequest->refund_amount = $returnRequest->items->sum('refund_amount');
            $returnRequest->final_refund_amount = $returnRequest->calculateRefundAmount();
            $returnRequest->save();

            // Approve the request
            $returnRequest->approve(Auth::id(), $request->notes);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Return request approved',
                'data' => $returnRequest->fresh(['items']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve return request',
            ], 500);
        }
    }

    /**
     * Reject a return request
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $returnRequest = ReturnRequest::find($id);

            if (!$returnRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found',
                ], 404);
            }

            $returnRequest->reject(Auth::id(), $request->reason, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Return request rejected',
                'data' => $returnRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject return request',
            ], 500);
        }
    }

    /**
     * Schedule pickup for return
     */
    public function schedulePickup(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pickup_address' => 'required|string|max:500',
            'pickup_scheduled_at' => 'required|date|after:now',
            'courier' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $returnRequest = ReturnRequest::find($id);

            if (!$returnRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found',
                ], 404);
            }

            if ($returnRequest->status !== ReturnRequest::STATUS_APPROVED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return must be approved before scheduling pickup',
                ], 422);
            }

            $returnRequest->schedulePickup(
                $request->pickup_address,
                new \DateTime($request->pickup_scheduled_at),
                $request->courier
            );

            return response()->json([
                'success' => true,
                'message' => 'Pickup scheduled successfully',
                'data' => $returnRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule pickup',
            ], 500);
        }
    }

    /**
     * Mark items as picked up
     */
    public function markPickedUp(Request $request, int $id): JsonResponse
    {
        try {
            $returnRequest = ReturnRequest::find($id);

            if (!$returnRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found',
                ], 404);
            }

            $returnRequest->markPickedUp($request->tracking_number);

            return response()->json([
                'success' => true,
                'message' => 'Marked as picked up',
                'data' => $returnRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
            ], 500);
        }
    }

    /**
     * Mark items as received
     */
    public function markReceived(Request $request, int $id): JsonResponse
    {
        try {
            $returnRequest = ReturnRequest::find($id);

            if (!$returnRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found',
                ], 404);
            }

            $returnRequest->markReceived($request->notes);

            // Update all items as received
            foreach ($returnRequest->items as $item) {
                $item->markReceived();
            }

            return response()->json([
                'success' => true,
                'message' => 'Marked as received',
                'data' => $returnRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
            ], 500);
        }
    }

    /**
     * Record inspection result
     */
    public function recordInspection(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'result' => 'required|in:passed,failed,partial',
            'notes' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
            'items.*.id' => 'required_with:items|exists:return_request_items,id',
            'items.*.condition' => 'required_with:items|in:unopened,opened,damaged,defective,wrong_item',
            'items.*.approved' => 'required_with:items|boolean',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $returnRequest = ReturnRequest::with('items')->find($id);

            if (!$returnRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found',
                ], 404);
            }

            // Inspect individual items if provided
            if ($request->items) {
                foreach ($request->items as $itemData) {
                    $item = $returnRequest->items->find($itemData['id']);
                    if ($item) {
                        $item->inspect(
                            $itemData['condition'],
                            $itemData['notes'] ?? null,
                            $itemData['approved']
                        );
                    }
                }
            }

            // Record overall inspection
            $returnRequest->recordInspection($request->result, $request->notes);

            // Recalculate refund amount
            $returnRequest->refund_amount = $returnRequest->items->sum('refund_amount');
            $returnRequest->final_refund_amount = $returnRequest->calculateRefundAmount();
            $returnRequest->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inspection recorded',
                'data' => $returnRequest->fresh(['items']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to record inspection',
            ], 500);
        }
    }

    /**
     * Initiate refund
     */
    public function initiateRefund(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'method' => 'required|in:original_payment,store_credit,bank_transfer',
            'amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $returnRequest = ReturnRequest::with('order.payments')->find($id);

            if (!$returnRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found',
                ], 404);
            }

            $amount = $request->amount ?? $returnRequest->final_refund_amount;

            // Process refund via RefundService if original payment method
            if ($request->method === 'original_payment') {
                $payment = $returnRequest->order->payments->first();
                if ($payment && $payment->payment_id) {
                    $refundResult = $this->refundService->processRefund(
                        $returnRequest->order,
                        $amount,
                        "Return refund: {$returnRequest->return_code}"
                    );

                    if ($refundResult['success']) {
                        $returnRequest->initiateRefund($amount, $request->method, $refundResult['refund_id'] ?? null);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Refund processing failed: ' . ($refundResult['message'] ?? 'Unknown error'),
                        ], 500);
                    }
                }
            } else {
                $returnRequest->initiateRefund($amount, $request->method);
            }

            return response()->json([
                'success' => true,
                'message' => 'Refund initiated',
                'data' => $returnRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate refund',
            ], 500);
        }
    }

    /**
     * Complete refund
     */
    public function completeRefund(Request $request, int $id): JsonResponse
    {
        try {
            $returnRequest = ReturnRequest::find($id);

            if (!$returnRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found',
                ], 404);
            }

            $returnRequest->completeRefund($request->reference);

            return response()->json([
                'success' => true,
                'message' => 'Refund completed',
                'data' => $returnRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete refund',
            ], 500);
        }
    }

    /**
     * Update admin notes
     */
    public function updateNotes(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $returnRequest = ReturnRequest::find($id);

            if (!$returnRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Return request not found',
                ], 404);
            }

            $returnRequest->admin_notes = $request->notes;
            $returnRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'Notes updated',
                'data' => $returnRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notes',
            ], 500);
        }
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:return_requests,id',
            'status' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updated = 0;

            foreach ($request->ids as $id) {
                $returnRequest = ReturnRequest::find($id);
                if ($returnRequest) {
                    $returnRequest->updateStatus($request->status, $request->notes, Auth::id());
                    $updated++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$updated} return requests updated",
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update return requests',
            ], 500);
        }
    }

    /**
     * Export return requests
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = ReturnRequest::with([
                'user:id,name,email',
                'order:id,order_number',
                'items.product:id,name,sku',
            ]);

            // Apply same filters as index
            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $returns = $query->orderBy('created_at', 'desc')->get();

            $exportData = $returns->map(function ($return) {
                return [
                    'return_code' => $return->return_code,
                    'order_number' => $return->order->order_number ?? '',
                    'customer_name' => $return->user->name ?? '',
                    'customer_email' => $return->user->email ?? '',
                    'status' => $return->status,
                    'return_type' => $return->return_type,
                    'reason' => $return->reason_code,
                    'items_count' => $return->items->count(),
                    'refund_amount' => $return->final_refund_amount,
                    'created_at' => $return->created_at->toDateTimeString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $exportData,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data',
            ], 500);
        }
    }
}
