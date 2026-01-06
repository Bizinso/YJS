<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CancellationRequest;
use App\Models\CancellationRequestItem;
use App\Models\ReturnPolicySetting;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Cancellation Controller
 *
 * Handles admin operations for cancellation requests.
 */
class AdminCancellationController extends Controller
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * Get cancellation dashboard statistics
     */
    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'total' => CancellationRequest::count(),
                'pending' => CancellationRequest::where('status', CancellationRequest::STATUS_PENDING)->count(),
                'under_review' => CancellationRequest::where('status', CancellationRequest::STATUS_UNDER_REVIEW)->count(),
                'approved' => CancellationRequest::where('status', CancellationRequest::STATUS_APPROVED)->count(),
                'refund_pending' => CancellationRequest::whereIn('status', [
                    CancellationRequest::STATUS_APPROVED,
                    CancellationRequest::STATUS_REFUND_INITIATED
                ])->count(),
                'refund_completed' => CancellationRequest::where('status', CancellationRequest::STATUS_REFUND_COMPLETED)->count(),
                'rejected' => CancellationRequest::where('status', CancellationRequest::STATUS_REJECTED)->count(),
                'auto_approved' => CancellationRequest::where('auto_approved', true)->count(),
                'total_refund_amount' => CancellationRequest::where('status', CancellationRequest::STATUS_REFUND_COMPLETED)
                    ->sum('refund_amount'),
                'this_month' => CancellationRequest::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];

            // Recent cancellations needing attention
            $needsAction = CancellationRequest::whereIn('status', [
                    CancellationRequest::STATUS_PENDING,
                    CancellationRequest::STATUS_UNDER_REVIEW,
                ])
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
     * Get all cancellation requests with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = CancellationRequest::with([
                'user:id,name,email',
                'order:id,order_number,total_amount,status',
            ]);

            // Filters
            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->cancellation_type) {
                $query->where('cancellation_type', $request->cancellation_type);
            }

            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->order_id) {
                $query->where('order_id', $request->order_id);
            }

            if ($request->cancellation_code) {
                $query->where('cancellation_code', 'like', "%{$request->cancellation_code}%");
            }

            if ($request->auto_approved !== null) {
                $query->where('auto_approved', $request->auto_approved);
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
                    $q->where('cancellation_code', 'like', "%{$search}%")
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

            $cancellations = $query->paginate($request->per_page ?? 20);

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
     * Get a specific cancellation request
     */
    public function show(int $id): JsonResponse
    {
        try {
            $cancellationRequest = CancellationRequest::with([
                'user:id,name,email,phone',
                'order:id,order_number,total_amount,status,created_at',
                'order.orderProducts.product:id,name,sku,main_image',
                'items.product:id,name,sku,main_image',
                'items.orderItem',
                'reviewer:id,name,email',
                'statusHistory.changedByUser:id,name',
            ])->find($id);

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
     * Start reviewing a cancellation request
     */
    public function startReview(int $id): JsonResponse
    {
        try {
            $cancellationRequest = CancellationRequest::find($id);

            if (!$cancellationRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation request not found',
                ], 404);
            }

            if ($cancellationRequest->status !== CancellationRequest::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation request is not in pending status',
                ], 422);
            }

            $cancellationRequest->updateStatus(
                CancellationRequest::STATUS_UNDER_REVIEW,
                'Review started',
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Review started',
                'data' => $cancellationRequest,
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
     * Approve a cancellation request
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
            'cancellation_fee' => 'nullable|numeric|min:0',
            'items' => 'nullable|array',
            'items.*.id' => 'required_with:items|exists:cancellation_request_items,id',
            'items.*.approved' => 'required_with:items|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $cancellationRequest = CancellationRequest::with(['items', 'order'])->find($id);

            if (!$cancellationRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation request not found',
                ], 404);
            }

            // For partial cancellations, process individual items
            if ($cancellationRequest->cancellation_type === CancellationRequest::TYPE_PARTIAL && $request->items) {
                foreach ($request->items as $itemData) {
                    $item = $cancellationRequest->items->find($itemData['id']);
                    if ($item) {
                        if ($itemData['approved']) {
                            $item->approve();
                        } else {
                            $item->reject();
                        }
                    }
                }
            } else {
                // Approve all items
                foreach ($cancellationRequest->items as $item) {
                    $item->approve();
                }
            }

            // Set cancellation fee
            $cancellationRequest->cancellation_fee = $request->cancellation_fee ?? 0;
            $cancellationRequest->save();

            // Approve the request
            $cancellationRequest->approve(Auth::id(), $request->notes);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cancellation request approved',
                'data' => $cancellationRequest->fresh(['items']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve cancellation request',
            ], 500);
        }
    }

    /**
     * Reject a cancellation request
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
            $cancellationRequest = CancellationRequest::find($id);

            if (!$cancellationRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation request not found',
                ], 404);
            }

            $cancellationRequest->reject(Auth::id(), $request->reason, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Cancellation request rejected',
                'data' => $cancellationRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject cancellation request',
            ], 500);
        }
    }

    /**
     * Initiate refund for cancellation
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
            $cancellationRequest = CancellationRequest::with('order.payments')->find($id);

            if (!$cancellationRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation request not found',
                ], 404);
            }

            if ($cancellationRequest->status !== CancellationRequest::STATUS_APPROVED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation must be approved before initiating refund',
                ], 422);
            }

            $amount = $request->amount ?? $cancellationRequest->refund_amount;

            // Process refund via RefundService if original payment method
            if ($request->method === 'original_payment') {
                $payment = $cancellationRequest->order->payments->first();
                if ($payment && $payment->payment_id) {
                    $refundResult = $this->refundService->processRefund(
                        $cancellationRequest->order,
                        $amount,
                        "Cancellation refund: {$cancellationRequest->cancellation_code}"
                    );

                    if ($refundResult['success']) {
                        $cancellationRequest->initiateRefund($request->method, $refundResult['refund_id'] ?? null);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Refund processing failed: ' . ($refundResult['message'] ?? 'Unknown error'),
                        ], 500);
                    }
                } else {
                    $cancellationRequest->initiateRefund($request->method);
                }
            } else {
                $cancellationRequest->initiateRefund($request->method);
            }

            return response()->json([
                'success' => true,
                'message' => 'Refund initiated',
                'data' => $cancellationRequest,
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
            $cancellationRequest = CancellationRequest::find($id);

            if (!$cancellationRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation request not found',
                ], 404);
            }

            $cancellationRequest->completeRefund($request->reference);

            // Mark items as refunded
            foreach ($cancellationRequest->items as $item) {
                if ($item->item_status === CancellationRequestItem::STATUS_APPROVED) {
                    $item->markRefunded();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Refund completed',
                'data' => $cancellationRequest,
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
            $cancellationRequest = CancellationRequest::find($id);

            if (!$cancellationRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation request not found',
                ], 404);
            }

            $cancellationRequest->admin_notes = $request->notes;
            $cancellationRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'Notes updated',
                'data' => $cancellationRequest,
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
            'ids.*' => 'exists:cancellation_requests,id',
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
                $cancellationRequest = CancellationRequest::find($id);
                if ($cancellationRequest) {
                    $cancellationRequest->updateStatus($request->status, $request->notes, Auth::id());
                    $updated++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$updated} cancellation requests updated",
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cancellation requests',
            ], 500);
        }
    }

    /**
     * Export cancellation requests
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = CancellationRequest::with([
                'user:id,name,email',
                'order:id,order_number',
            ]);

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $cancellations = $query->orderBy('created_at', 'desc')->get();

            $exportData = $cancellations->map(function ($cancellation) {
                return [
                    'cancellation_code' => $cancellation->cancellation_code,
                    'order_number' => $cancellation->order->order_number ?? '',
                    'customer_name' => $cancellation->user->name ?? '',
                    'customer_email' => $cancellation->user->email ?? '',
                    'status' => $cancellation->status,
                    'cancellation_type' => $cancellation->cancellation_type,
                    'reason' => $cancellation->reason_code,
                    'order_amount' => $cancellation->order_amount,
                    'cancellation_fee' => $cancellation->cancellation_fee,
                    'refund_amount' => $cancellation->refund_amount,
                    'auto_approved' => $cancellation->auto_approved ? 'Yes' : 'No',
                    'created_at' => $cancellation->created_at->toDateTimeString(),
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
