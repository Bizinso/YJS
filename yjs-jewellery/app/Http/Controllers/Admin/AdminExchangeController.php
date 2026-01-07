<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRequest;
use App\Models\ExchangeRequestItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Exchange Controller
 *
 * Handles admin operations for exchange requests.
 */
class AdminExchangeController extends Controller
{
    /**
     * Get exchange dashboard statistics
     */
    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'total' => ExchangeRequest::count(),
                'pending' => ExchangeRequest::where('status', ExchangeRequest::STATUS_PENDING)->count(),
                'under_review' => ExchangeRequest::where('status', ExchangeRequest::STATUS_UNDER_REVIEW)->count(),
                'approved' => ExchangeRequest::where('status', ExchangeRequest::STATUS_APPROVED)->count(),
                'awaiting_return' => ExchangeRequest::where('status', ExchangeRequest::STATUS_AWAITING_RETURN)->count(),
                'processing' => ExchangeRequest::where('status', ExchangeRequest::STATUS_PROCESSING)->count(),
                'shipped' => ExchangeRequest::where('status', ExchangeRequest::STATUS_SHIPPED)->count(),
                'delivered' => ExchangeRequest::where('status', ExchangeRequest::STATUS_DELIVERED)->count(),
                'rejected' => ExchangeRequest::where('status', ExchangeRequest::STATUS_REJECTED)->count(),
                'this_month' => ExchangeRequest::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'pending_payment_adjustments' => ExchangeRequest::where('adjustment_type', '!=', 'none')
                    ->where('adjustment_paid', false)
                    ->count(),
            ];

            // Recent exchanges needing attention
            $needsAction = ExchangeRequest::whereIn('status', [
                    ExchangeRequest::STATUS_PENDING,
                    ExchangeRequest::STATUS_UNDER_REVIEW,
                    ExchangeRequest::STATUS_RETURN_RECEIVED,
                ])
                ->with(['user:id,first_name,last_name,email', 'order:id,custom_order_code'])
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
     * Get all exchange requests with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ExchangeRequest::with([
                'user:id,first_name,last_name,email',
                'order:id,custom_order_code,total_amount',
                'items:id,exchange_request_id,original_product_id,new_product_id',
            ]);

            // Filters
            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->order_id) {
                $query->where('order_id', $request->order_id);
            }

            if ($request->exchange_code) {
                $query->where('exchange_code', 'like', "%{$request->exchange_code}%");
            }

            if ($request->adjustment_type) {
                $query->where('adjustment_type', $request->adjustment_type);
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
                    $q->where('exchange_code', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('first_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('order', function ($oq) use ($search) {
                            $oq->where('custom_order_code', 'like', "%{$search}%");
                        });
                });
            }

            // Sorting
            $sortBy = $request->sort_by ?? 'created_at';
            $sortDir = $request->sort_dir ?? 'desc';
            $query->orderBy($sortBy, $sortDir);

            $exchanges = $query->paginate($request->per_page ?? 20);

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
     * Get a specific exchange request
     */
    public function show(int $id): JsonResponse
    {
        try {
            $exchangeRequest = ExchangeRequest::with([
                'user:id,first_name,last_name,email,phone',
                'order:id,custom_order_code,total_amount,status,created_at',
                'order.orderProducts.product:id,name,sku,main_image',
                'items.originalProduct:id,name,sku,main_image,final_price',
                'items.newProduct:id,name,sku,main_image,final_price,available_stock',
                'items.orderItem',
                'shippingAddress',
                'returnRequest',
                'newOrder',
                'reviewer:id,name,email',
                'statusHistory.changedByUser:id,name',
            ])->find($id);

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
     * Start reviewing an exchange request
     */
    public function startReview(int $id): JsonResponse
    {
        try {
            $exchangeRequest = ExchangeRequest::find($id);

            if (!$exchangeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request not found',
                ], 404);
            }

            if ($exchangeRequest->status !== ExchangeRequest::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request is not in pending status',
                ], 422);
            }

            $exchangeRequest->updateStatus(
                ExchangeRequest::STATUS_UNDER_REVIEW,
                'Review started',
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Review started',
                'data' => $exchangeRequest,
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
     * Approve an exchange request
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
            'items.*.id' => 'required_with:items|exists:exchange_request_items,id',
            'items.*.approved' => 'required_with:items|boolean',
            'items.*.new_product_id' => 'nullable|exists:products,id',
            'items.*.new_quantity' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $exchangeRequest = ExchangeRequest::with('items')->find($id);

            if (!$exchangeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request not found',
                ], 404);
            }

            // Process individual items if provided
            if ($request->items) {
                foreach ($request->items as $itemData) {
                    $item = $exchangeRequest->items->find($itemData['id']);
                    if ($item) {
                        if ($itemData['approved']) {
                            // Update new product if changed by admin
                            if (isset($itemData['new_product_id'])) {
                                $item->setNewProduct(
                                    $itemData['new_product_id'],
                                    $itemData['new_quantity'] ?? $item->original_quantity
                                );
                            }
                            $item->approve();
                        } else {
                            $item->reject();
                        }
                    }
                }
            } else {
                // Approve all items
                foreach ($exchangeRequest->items as $item) {
                    $item->approve();
                }
            }

            // Recalculate price difference
            $exchangeRequest->calculatePriceDifference();

            // Approve the request
            $exchangeRequest->approve(Auth::id(), $request->notes);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Exchange request approved',
                'data' => $exchangeRequest->fresh(['items']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve exchange request',
            ], 500);
        }
    }

    /**
     * Reject an exchange request
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
            $exchangeRequest = ExchangeRequest::find($id);

            if (!$exchangeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request not found',
                ], 404);
            }

            $exchangeRequest->reject(Auth::id(), $request->reason, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Exchange request rejected',
                'data' => $exchangeRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject exchange request',
            ], 500);
        }
    }

    /**
     * Update exchange status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
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
            $exchangeRequest = ExchangeRequest::find($id);

            if (!$exchangeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request not found',
                ], 404);
            }

            $exchangeRequest->updateStatus($request->status, $request->notes, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Status updated',
                'data' => $exchangeRequest,
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
     * Mark return received
     */
    public function markReturnReceived(Request $request, int $id): JsonResponse
    {
        try {
            $exchangeRequest = ExchangeRequest::find($id);

            if (!$exchangeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request not found',
                ], 404);
            }

            $exchangeRequest->updateStatus(
                ExchangeRequest::STATUS_RETURN_RECEIVED,
                $request->notes ?? 'Original items received',
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Return marked as received',
                'data' => $exchangeRequest,
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
     * Process exchange - prepare new items
     */
    public function processExchange(Request $request, int $id): JsonResponse
    {
        try {
            $exchangeRequest = ExchangeRequest::with('items')->find($id);

            if (!$exchangeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request not found',
                ], 404);
            }

            // Check stock availability for all new products
            foreach ($exchangeRequest->items as $item) {
                if ($item->item_status === 'approved' && $item->new_product_id) {
                    $product = Product::find($item->new_product_id);
                    if (!$product || $product->available_stock < $item->new_quantity) {
                        $productName = $product->name ?? 'Unknown';
                        return response()->json([
                            'success' => false,
                            'message' => "Insufficient stock for product: {$productName}",
                        ], 422);
                    }
                }
            }

            $exchangeRequest->updateStatus(
                ExchangeRequest::STATUS_PROCESSING,
                'Exchange items being prepared',
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Exchange processing started',
                'data' => $exchangeRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to process exchange',
            ], 500);
        }
    }

    /**
     * Ship exchange items
     */
    public function ship(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tracking_number' => 'required|string|max:100',
            'courier_name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $exchangeRequest = ExchangeRequest::find($id);

            if (!$exchangeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request not found',
                ], 404);
            }

            $exchangeRequest->markShipped($request->tracking_number, $request->courier_name);

            return response()->json([
                'success' => true,
                'message' => 'Exchange items shipped',
                'data' => $exchangeRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update shipping',
            ], 500);
        }
    }

    /**
     * Mark exchange as delivered
     */
    public function markDelivered(int $id): JsonResponse
    {
        try {
            $exchangeRequest = ExchangeRequest::find($id);

            if (!$exchangeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request not found',
                ], 404);
            }

            $exchangeRequest->markDelivered();

            // Mark all items as fulfilled
            foreach ($exchangeRequest->items as $item) {
                if ($item->item_status === 'approved') {
                    $item->markFulfilled();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Exchange marked as delivered',
                'data' => $exchangeRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update delivery status',
            ], 500);
        }
    }

    /**
     * Record payment adjustment
     */
    public function recordPaymentAdjustment(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'paid' => 'required|boolean',
            'reference' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $exchangeRequest = ExchangeRequest::find($id);

            if (!$exchangeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request not found',
                ], 404);
            }

            $exchangeRequest->adjustment_paid = $request->paid;
            $exchangeRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment adjustment recorded',
                'data' => $exchangeRequest,
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment',
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
            $exchangeRequest = ExchangeRequest::find($id);

            if (!$exchangeRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exchange request not found',
                ], 404);
            }

            $exchangeRequest->admin_notes = $request->notes;
            $exchangeRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'Notes updated',
                'data' => $exchangeRequest,
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
     * Export exchange requests
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = ExchangeRequest::with([
                'user:id,first_name,last_name,email',
                'order:id,custom_order_code',
                'items.originalProduct:id,name',
                'items.newProduct:id,name',
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

            $exchanges = $query->orderBy('created_at', 'desc')->get();

            $exportData = $exchanges->map(function ($exchange) {
                return [
                    'exchange_code' => $exchange->exchange_code,
                    'order_number' => $exchange->order->custom_order_code ?? '',
                    'customer_name' => $exchange->user->name ?? '',
                    'customer_email' => $exchange->user->email ?? '',
                    'status' => $exchange->status,
                    'reason' => $exchange->reason_code,
                    'items_count' => $exchange->items->count(),
                    'price_difference' => $exchange->price_difference,
                    'adjustment_type' => $exchange->adjustment_type,
                    'adjustment_paid' => $exchange->adjustment_paid ? 'Yes' : 'No',
                    'created_at' => $exchange->created_at->toDateTimeString(),
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
