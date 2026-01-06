<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\RefundRequest;
use App\Models\RefundStatusHistory;
use App\Models\CreditNote;
use App\Models\PaymentSettlement;
use App\Models\SettlementTransaction;
use App\Models\OutstandingPayment;
use App\Services\Payment\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Finance Controller
 *
 * Handles financial operations including refunds, credit notes,
 * settlements, and financial reporting.
 */
class AdminFinanceController extends Controller
{
    public function __construct(
        private RazorpayService $razorpayService
    ) {}

    /**
     * Get finance dashboard overview.
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 30);
            $startDate = now()->subDays($period);

            // Revenue statistics
            $revenue = Order::where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid')
                ->selectRaw('
                    SUM(order_total) as total_revenue,
                    COUNT(*) as paid_orders,
                    AVG(order_total) as avg_order_value
                ')
                ->first();

            // Refunds
            $refunds = RefundRequest::where('created_at', '>=', $startDate)
                ->selectRaw('
                    COUNT(*) as total_refunds,
                    SUM(CASE WHEN status = "completed" THEN refund_amount ELSE 0 END) as refunded_amount,
                    SUM(CASE WHEN status = "pending" OR status = "under_review" THEN 1 ELSE 0 END) as pending_refunds
                ')
                ->first();

            // Credit notes
            $creditNotes = CreditNote::where('created_at', '>=', $startDate)
                ->selectRaw('
                    COUNT(*) as total_issued,
                    SUM(amount) as total_amount,
                    SUM(balance) as total_balance
                ')
                ->first();

            // Outstanding payments
            $outstanding = OutstandingPayment::getSummary();

            // Payment breakdown by method
            $paymentMethods = Order::where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid')
                ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(order_total) as total'))
                ->groupBy('payment_method')
                ->get();

            // Daily revenue trend
            $driver = DB::getDriverName();
            $dateFormat = $driver === 'sqlite' ? "DATE(created_at)" : "DATE_FORMAT(created_at, '%Y-%m-%d')";

            $dailyRevenue = Order::where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid')
                ->select(
                    DB::raw("{$dateFormat} as date"),
                    DB::raw('SUM(order_total) as revenue'),
                    DB::raw('COUNT(*) as orders')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'period_days' => $period,
                    'revenue' => [
                        'total' => round($revenue->total_revenue ?? 0, 2),
                        'orders' => $revenue->paid_orders ?? 0,
                        'average' => round($revenue->avg_order_value ?? 0, 2),
                    ],
                    'refunds' => [
                        'total_count' => $refunds->total_refunds ?? 0,
                        'refunded_amount' => round($refunds->refunded_amount ?? 0, 2),
                        'pending_count' => $refunds->pending_refunds ?? 0,
                    ],
                    'credit_notes' => [
                        'issued_count' => $creditNotes->total_issued ?? 0,
                        'total_amount' => round($creditNotes->total_amount ?? 0, 2),
                        'available_balance' => round($creditNotes->total_balance ?? 0, 2),
                    ],
                    'outstanding' => $outstanding,
                    'payment_methods' => $paymentMethods,
                    'daily_trend' => $dailyRevenue,
                ],
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch finance dashboard',
            ], 500);
        }
    }

    /**
     * Get revenue report by period.
     */
    public function revenue(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'group_by' => 'nullable|in:day,week,month,year',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $groupBy = $request->input('group_by', 'day');
            $fromDate = $request->from_date ? \Carbon\Carbon::parse($request->from_date) : now()->subDays(30);
            $toDate = $request->to_date ? \Carbon\Carbon::parse($request->to_date) : now();

            $driver = DB::getDriverName();

            $dateFormat = match ($groupBy) {
                'week' => $driver === 'sqlite' ? "strftime('%Y-%W', created_at)" : "DATE_FORMAT(created_at, '%Y-%u')",
                'month' => $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')",
                'year' => $driver === 'sqlite' ? "strftime('%Y', created_at)" : "DATE_FORMAT(created_at, '%Y')",
                default => $driver === 'sqlite' ? "DATE(created_at)" : "DATE_FORMAT(created_at, '%Y-%m-%d')",
            };

            $data = Order::whereBetween('created_at', [$fromDate, $toDate])
                ->where('payment_status', 'paid')
                ->select(
                    DB::raw("{$dateFormat} as period"),
                    DB::raw('SUM(order_total) as revenue'),
                    DB::raw('SUM(shipping_charges) as shipping'),
                    DB::raw('SUM(total_taxes) as taxes'),
                    DB::raw('COUNT(*) as orders')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $totals = [
                'revenue' => round($data->sum('revenue'), 2),
                'shipping' => round($data->sum('shipping'), 2),
                'taxes' => round($data->sum('taxes'), 2),
                'orders' => $data->sum('orders'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'group_by' => $groupBy,
                    'from_date' => $fromDate->toDateString(),
                    'to_date' => $toDate->toDateString(),
                    'periods' => $data,
                    'totals' => $totals,
                ],
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue data',
            ], 500);
        }
    }

    // ============ REFUNDS ============

    /**
     * List all refund requests.
     */
    public function refunds(Request $request): JsonResponse
    {
        $query = RefundRequest::with([
            'order:id,custom_order_code,order_total',
            'user:id,name,email',
            'reviewedByUser:id,name',
            'approvedByUser:id,name',
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->source) {
            $query->where('source', $request->source);
        }
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('refund_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('order', function ($q) use ($search) {
                        $q->where('custom_order_code', 'like', "%{$search}%");
                    });
            });
        }

        $refunds = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $refunds,
        ]);
    }

    /**
     * Get single refund request.
     */
    public function showRefund(int $id): JsonResponse
    {
        $refund = RefundRequest::with([
            'order.customer:id,name,email,phone',
            'order.orderProducts.product:id,name,sku',
            'user:id,name,email',
            'reviewedByUser:id,name',
            'approvedByUser:id,name',
            'statusHistory.changedByUser:id,name',
            'creditNote',
        ])->find($id);

        if (!$refund) {
            return response()->json([
                'success' => false,
                'message' => 'Refund not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $refund,
        ]);
    }

    /**
     * Start reviewing a refund.
     */
    public function startRefundReview(int $id): JsonResponse
    {
        $refund = RefundRequest::find($id);

        if (!$refund) {
            return response()->json([
                'success' => false,
                'message' => 'Refund not found',
            ], 404);
        }

        if ($refund->status !== RefundRequest::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Refund is not pending review',
            ], 422);
        }

        $refund->startReview();

        return response()->json([
            'success' => true,
            'message' => 'Review started',
            'data' => $refund->fresh(),
        ]);
    }

    /**
     * Approve a refund request.
     */
    public function approveRefund(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refund_amount' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'deduction_reason' => 'required_with:deductions|string|max:500',
            'refund_method' => 'nullable|in:original_payment,bank_transfer,store_credit',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $refund = RefundRequest::find($id);

        if (!$refund) {
            return response()->json([
                'success' => false,
                'message' => 'Refund not found',
            ], 404);
        }

        if (!$refund->canBeApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Refund cannot be approved in current state',
            ], 422);
        }

        DB::beginTransaction();
        try {
            if ($request->has('refund_amount')) {
                $refund->refund_amount = $request->refund_amount;
            }
            if ($request->has('deductions')) {
                $refund->deductions = $request->deductions;
                $refund->deduction_reason = $request->deduction_reason;
            }
            if ($request->refund_method) {
                $refund->refund_method = $request->refund_method;
            }
            if ($request->notes) {
                $refund->admin_notes = $request->notes;
            }

            $refund->save();
            $refund->approve(auth()->id(), $request->notes);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund approved',
                'data' => $refund->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve refund: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a refund request.
     */
    public function rejectRefund(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $refund = RefundRequest::find($id);

        if (!$refund) {
            return response()->json([
                'success' => false,
                'message' => 'Refund not found',
            ], 404);
        }

        if (!$refund->canBeApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Refund cannot be rejected in current state',
            ], 422);
        }

        $refund->reject($request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Refund rejected',
            'data' => $refund->fresh(),
        ]);
    }

    /**
     * Process an approved refund.
     */
    public function processRefund(int $id): JsonResponse
    {
        $refund = RefundRequest::with('order.payments')->find($id);

        if (!$refund) {
            return response()->json([
                'success' => false,
                'message' => 'Refund not found',
            ], 404);
        }

        if (!$refund->canBeProcessed()) {
            return response()->json([
                'success' => false,
                'message' => 'Refund is not approved',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $refund->markProcessing();

            // Process based on refund method
            if ($refund->refund_method === RefundRequest::METHOD_STORE_CREDIT) {
                // Create credit note
                $creditNote = CreditNote::create([
                    'order_id' => $refund->order_id,
                    'refund_id' => $refund->id,
                    'user_id' => $refund->user_id,
                    'amount' => $refund->net_refund_amount,
                    'balance' => $refund->net_refund_amount,
                    'reason_code' => CreditNote::REASON_REFUND,
                    'reason_description' => "Refund for order #{$refund->order->custom_order_code}",
                    'created_by' => auth()->id(),
                ]);

                $refund->markCompleted(null, ['credit_note_id' => $creditNote->id]);
            } else {
                // Process through payment gateway
                $payment = $refund->order->payments()
                    ->where('status', 'success')
                    ->latest()
                    ->first();

                if (!$payment || !$payment->razorpay_payment_id) {
                    throw new \Exception('No valid payment found for refund');
                }

                $gatewayRefund = $this->razorpayService->refundPartial(
                    $refund->order,
                    $refund->net_refund_amount,
                    $refund->reason_code
                );

                if ($gatewayRefund) {
                    $refund->markCompleted(
                        $gatewayRefund->razorpay_refund_id,
                        ['gateway_refund' => $gatewayRefund->toArray()]
                    );
                } else {
                    throw new \Exception('Gateway refund failed');
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully',
                'data' => $refund->fresh()->load('creditNote'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $refund->markFailed($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ============ CREDIT NOTES ============

    /**
     * List credit notes.
     */
    public function creditNotes(Request $request): JsonResponse
    {
        $query = CreditNote::with([
            'user:id,name,email',
            'order:id,custom_order_code',
            'createdByUser:id,name',
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('credit_note_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $creditNotes = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $creditNotes,
        ]);
    }

    /**
     * Create a credit note manually.
     */
    public function createCreditNote(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'reason_code' => 'required|string|max:50',
            'reason_description' => 'nullable|string|max:500',
            'valid_until' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $creditNote = CreditNote::create([
                'user_id' => $request->user_id,
                'amount' => $request->amount,
                'balance' => $request->amount,
                'reason_code' => $request->reason_code,
                'reason_description' => $request->reason_description,
                'valid_until' => $request->valid_until,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Credit note created',
                'data' => $creditNote->load('user:id,name,email'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create credit note: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get credit note details.
     */
    public function showCreditNote(int $id): JsonResponse
    {
        $creditNote = CreditNote::with([
            'user:id,name,email',
            'order:id,custom_order_code',
            'refund:id,refund_code',
            'usages.order:id,custom_order_code',
            'createdByUser:id,name',
        ])->find($id);

        if (!$creditNote) {
            return response()->json([
                'success' => false,
                'message' => 'Credit note not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $creditNote,
        ]);
    }

    /**
     * Cancel a credit note.
     */
    public function cancelCreditNote(int $id): JsonResponse
    {
        $creditNote = CreditNote::find($id);

        if (!$creditNote) {
            return response()->json([
                'success' => false,
                'message' => 'Credit note not found',
            ], 404);
        }

        if ($creditNote->used_amount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel credit note that has been used',
            ], 422);
        }

        $creditNote->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Credit note cancelled',
        ]);
    }

    // ============ SETTLEMENTS ============

    /**
     * Get settlement report.
     */
    public function settlements(Request $request): JsonResponse
    {
        $query = PaymentSettlement::with('reconciledByUser:id,name');

        if ($request->gateway) {
            $query->where('payment_gateway', $request->gateway);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->from_date) {
            $query->where('settlement_date', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->where('settlement_date', '<=', $request->to_date);
        }

        $settlements = $query->orderBy('settlement_date', 'desc')
            ->paginate($request->per_page ?? 20);

        // Summary
        $summary = PaymentSettlement::getSummary(
            $request->gateway,
            $request->from_date,
            $request->to_date
        );

        return response()->json([
            'success' => true,
            'data' => $settlements,
            'summary' => $summary,
        ]);
    }

    /**
     * Reconcile payments with settlements.
     */
    public function reconcile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settlement_id' => 'required|string',
            'payment_gateway' => 'required|string',
            'settlement_date' => 'required|date',
            'gross_amount' => 'required|numeric',
            'fees' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'net_amount' => 'required|numeric',
            'transactions' => 'nullable|array',
            'transactions.*.transaction_id' => 'required|string',
            'transactions.*.type' => 'required|in:payment,refund',
            'transactions.*.amount' => 'required|numeric',
            'transactions.*.fee' => 'nullable|numeric',
            'transactions.*.transaction_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create or update settlement
            $settlement = PaymentSettlement::updateOrCreate(
                ['settlement_id' => $request->settlement_id],
                [
                    'payment_gateway' => $request->payment_gateway,
                    'settlement_date' => $request->settlement_date,
                    'gross_amount' => $request->gross_amount,
                    'fees' => $request->fees ?? 0,
                    'tax' => $request->tax ?? 0,
                    'net_amount' => $request->net_amount,
                    'transaction_count' => count($request->transactions ?? []),
                    'settlement_data' => $request->all(),
                ]
            );

            // Process transactions
            if ($request->transactions) {
                foreach ($request->transactions as $txn) {
                    $transaction = SettlementTransaction::updateOrCreate(
                        [
                            'settlement_id' => $settlement->id,
                            'transaction_id' => $txn['transaction_id'],
                        ],
                        [
                            'type' => $txn['type'],
                            'amount' => $txn['amount'],
                            'fee' => $txn['fee'] ?? 0,
                            'transaction_date' => $txn['transaction_date'],
                        ]
                    );

                    // Try to match with order
                    $transaction->matchWithPayment();
                }
            }

            // Reconcile
            $settlement->reconcile();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Settlement reconciled',
                'data' => $settlement->fresh()->load('transactions'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Reconciliation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ============ OUTSTANDING PAYMENTS ============

    /**
     * Get outstanding payments.
     */
    public function outstanding(Request $request): JsonResponse
    {
        $query = OutstandingPayment::with([
            'order:id,custom_order_code,order_total',
            'user:id,name,email,phone',
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->overdue_only) {
            $query->overdue();
        }

        $outstanding = $query->orderBy('due_date', 'asc')
            ->paginate($request->per_page ?? 20);

        $summary = OutstandingPayment::getSummary();

        return response()->json([
            'success' => true,
            'data' => $outstanding,
            'summary' => $summary,
        ]);
    }

    /**
     * Export payments data.
     */
    public function exportPayments(Request $request)
    {
        $query = OrderPayment::with([
            'order:id,custom_order_code,customer_id',
            'order.customer:id,name,email',
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $payments = $query->orderBy('created_at', 'desc')->limit(5000)->get();

        if ($request->format === 'json') {
            return response()->json([
                'success' => true,
                'data' => $payments,
                'count' => $payments->count(),
            ]);
        }

        // CSV export
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payments_export_' . date('Y-m-d_His') . '.csv"',
        ];

        $callback = function () use ($payments) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Payment ID', 'Order Code', 'Customer', 'Email', 'Amount',
                'Status', 'Payment Method', 'Gateway ID', 'Date'
            ]);

            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->id,
                    $payment->order->custom_order_code ?? '',
                    $payment->order->customer->name ?? '',
                    $payment->order->customer->email ?? '',
                    $payment->amount,
                    $payment->status,
                    $payment->payment_method ?? '',
                    $payment->razorpay_payment_id ?? '',
                    $payment->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
