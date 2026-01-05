<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerInquiry;
use App\Models\PartnerInquiryItem;
use App\Models\PartnerInquiryMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Partner Inquiry Controller
 *
 * Handles admin operations for B2B partner inquiries:
 * - View all partner inquiries
 * - Review and process inquiries
 * - Provide quotes
 * - Update fulfillment status
 * - Track payments (offline)
 * - Communicate with partners
 */
class AdminPartnerInquiryController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function dashboard(): JsonResponse
    {
        $stats = [
            'total_inquiries' => PartnerInquiry::count(),
            'pending_review' => PartnerInquiry::where('status', 'pending')->count(),
            'under_review' => PartnerInquiry::where('status', 'under_review')->count(),
            'awaiting_response' => PartnerInquiry::where('status', 'quoted')->count(),
            'accepted' => PartnerInquiry::where('status', 'accepted')->count(),
            'processing' => PartnerInquiry::where('status', 'processing')->count(),
            'shipped' => PartnerInquiry::where('status', 'shipped')->count(),
            'delivered' => PartnerInquiry::where('status', 'delivered')->count(),
            'completed' => PartnerInquiry::where('status', 'completed')->count(),
            'cancelled' => PartnerInquiry::whereIn('status', ['cancelled', 'rejected'])->count(),
            'urgent_count' => PartnerInquiry::where('priority', 'urgent')
                ->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
                ->count(),
            'total_quoted_value' => PartnerInquiry::whereIn('status', ['quoted', 'accepted', 'processing', 'shipped', 'delivered', 'completed'])
                ->sum('final_amount'),
            'pending_payment_value' => PartnerInquiry::whereIn('status', ['accepted', 'processing', 'shipped', 'delivered'])
                ->where('payment_status', '!=', 'paid')
                ->sum(DB::raw('final_amount - amount_paid')),
            'this_month_inquiries' => PartnerInquiry::whereMonth('created_at', now()->month)->count(),
            'this_month_value' => PartnerInquiry::whereMonth('created_at', now()->month)
                ->whereIn('status', ['accepted', 'processing', 'shipped', 'delivered', 'completed'])
                ->sum('final_amount'),
        ];

        // Recent inquiries needing attention
        $recentPending = PartnerInquiry::whereIn('status', ['pending', 'under_review'])
            ->with(['partner:id,business_name', 'user:id,first_name,last_name'])
            ->withCount('items')
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'statistics' => $stats,
            'pending_inquiries' => $recentPending->map(function ($inq) {
                return [
                    'id' => $inq->id,
                    'inquiry_code' => $inq->inquiry_code,
                    'partner_name' => $inq->partner?->business_name ?? ($inq->user?->first_name . ' ' . $inq->user?->last_name),
                    'status' => $inq->status,
                    'priority' => $inq->priority,
                    'items_count' => $inq->items_count,
                    'created_at' => $inq->created_at,
                    'days_pending' => $inq->created_at->diffInDays(now()),
                ];
            }),
        ]);
    }

    /**
     * Get all inquiries with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = PartnerInquiry::with([
            'partner:id,business_name,contact_person',
            'user:id,first_name,last_name,email,phone',
        ])
            ->withCount('items');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by partner
        if ($request->has('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('inquiry_code', 'like', "%{$search}%")
                    ->orWhereHas('partner', function ($pq) use ($search) {
                        $pq->where('business_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if ($sortBy === 'priority') {
            $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low') " . ($sortOrder === 'desc' ? 'DESC' : 'ASC'));
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->input('per_page', 20);
        $inquiries = $query->paginate($perPage);

        $formatted = $inquiries->getCollection()->map(function ($inq) {
            return [
                'id' => $inq->id,
                'inquiry_code' => $inq->inquiry_code,
                'partner' => [
                    'id' => $inq->partner_id,
                    'business_name' => $inq->partner?->business_name,
                    'contact_person' => $inq->partner?->contact_person,
                ],
                'user' => [
                    'name' => $inq->user ? $inq->user->first_name . ' ' . $inq->user->last_name : null,
                    'email' => $inq->user?->email,
                    'phone' => $inq->user?->phone,
                ],
                'status' => $inq->status,
                'status_label' => $inq->status_label,
                'priority' => $inq->priority,
                'priority_label' => $inq->priority_label,
                'items_count' => $inq->items_count,
                'quoted_amount' => $inq->quoted_amount,
                'final_amount' => $inq->final_amount,
                'payment_status' => $inq->payment_status,
                'amount_paid' => $inq->amount_paid,
                'balance' => $inq->balance_amount,
                'expected_delivery' => $inq->expected_delivery_date,
                'created_at' => $inq->created_at,
                'handled_by' => $inq->handler?->first_name,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'pagination' => [
                'current_page' => $inquiries->currentPage(),
                'last_page' => $inquiries->lastPage(),
                'per_page' => $inquiries->perPage(),
                'total' => $inquiries->total(),
            ],
        ]);
    }

    /**
     * Get inquiry details
     */
    public function show(int $id): JsonResponse
    {
        $inquiry = PartnerInquiry::with([
            'partner',
            'user:id,first_name,last_name,email,phone',
            'items.product:id,name,sku,main_image,description,base_price,final_price',
            'items.variant:id,name,sku,main_image',
            'shippingAddress',
            'statusHistory.changedBy:id,first_name,last_name',
            'messages.user:id,first_name,last_name',
            'handler:id,first_name,last_name',
        ])->find($id);

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found.',
            ], 404);
        }

        // Mark unread partner messages as read
        $inquiry->messages()
            ->where('sender_type', 'partner')
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $inquiry->id,
                'inquiry_code' => $inquiry->inquiry_code,
                'partner' => $inquiry->partner,
                'user' => $inquiry->user,
                'status' => $inquiry->status,
                'status_label' => $inquiry->status_label,
                'priority' => $inquiry->priority,
                'notes' => $inquiry->notes,
                'admin_notes' => $inquiry->admin_notes,
                'rejection_reason' => $inquiry->rejection_reason,
                'items' => $inquiry->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => $item->product,
                        'variant' => $item->variant,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'specifications' => $item->specifications,
                        'notes' => $item->notes,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                        'discount' => $item->discount,
                        'item_status' => $item->item_status,
                        'quantity_fulfilled' => $item->quantity_fulfilled,
                        'remaining' => $item->remaining_quantity,
                        // Admin can see product prices
                        'product_base_price' => $item->product?->base_price,
                        'product_final_price' => $item->product?->final_price,
                    ];
                }),
                'quote' => [
                    'quoted_amount' => $inquiry->quoted_amount,
                    'discount_amount' => $inquiry->discount_amount,
                    'shipping_charges' => $inquiry->shipping_charges,
                    'tax_amount' => $inquiry->tax_amount,
                    'final_amount' => $inquiry->final_amount,
                    'quote_valid_until' => $inquiry->quote_valid_until,
                    'quoted_at' => $inquiry->quoted_at,
                ],
                'shipping_address' => $inquiry->shippingAddress,
                'delivery' => [
                    'expected_date' => $inquiry->expected_delivery_date,
                    'actual_date' => $inquiry->actual_delivery_date,
                    'method' => $inquiry->delivery_method,
                    'tracking_number' => $inquiry->tracking_number,
                    'courier_name' => $inquiry->courier_name,
                    'tracking_history' => $inquiry->tracking_history,
                ],
                'payment' => [
                    'status' => $inquiry->payment_status,
                    'method' => $inquiry->payment_method,
                    'reference' => $inquiry->payment_reference,
                    'amount_paid' => $inquiry->amount_paid,
                    'balance' => $inquiry->balance_amount,
                    'payment_date' => $inquiry->payment_date,
                ],
                'handling' => [
                    'handled_by' => $inquiry->handler,
                    'handled_at' => $inquiry->handled_at,
                ],
                'status_history' => $inquiry->statusHistory,
                'messages' => $inquiry->messages,
                'partner_response_notes' => $inquiry->partner_response_notes,
                'accepted_at' => $inquiry->accepted_at,
                'created_at' => $inquiry->created_at,
                'updated_at' => $inquiry->updated_at,
            ],
        ]);
    }

    /**
     * Start reviewing an inquiry
     */
    public function startReview(int $id): JsonResponse
    {
        $inquiry = PartnerInquiry::find($id);

        if (!$inquiry) {
            return response()->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
        }

        if ($inquiry->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Inquiry is not in pending status.'], 422);
        }

        $user = Auth::user();
        $inquiry->updateStatus('under_review', 'Review started', $user->id);
        $inquiry->handled_by = $user->id;
        $inquiry->handled_at = now();
        $inquiry->save();

        return response()->json([
            'success' => true,
            'message' => 'Inquiry marked as under review.',
        ]);
    }

    /**
     * Provide quote for inquiry
     */
    public function provideQuote(Request $request, int $id): JsonResponse
    {
        $inquiry = PartnerInquiry::with('items')->find($id);

        if (!$inquiry) {
            return response()->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
        }

        if (!in_array($inquiry->status, ['pending', 'under_review'])) {
            return response()->json(['success' => false, 'message' => 'Inquiry cannot be quoted.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.id' => 'required|exists:partner_inquiry_items,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'shipping_charges' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'quote_valid_days' => 'nullable|integer|min:1|max:30',
            'admin_notes' => 'nullable|string|max:2000',
            'expected_delivery_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $quotedAmount = 0;

            // Update item prices
            foreach ($request->items as $itemData) {
                $item = PartnerInquiryItem::find($itemData['id']);
                if ($item && $item->inquiry_id === $inquiry->id) {
                    $item->unit_price = $itemData['unit_price'];
                    $item->discount = $itemData['discount'] ?? 0;
                    $item->calculateTotal();
                    $item->item_status = 'confirmed';
                    $item->save();
                    $quotedAmount += $item->total_price;
                }
            }

            // Calculate final amount
            $discountAmount = $request->discount_amount ?? 0;
            $shippingCharges = $request->shipping_charges ?? 0;
            $taxAmount = $request->tax_amount ?? 0;
            $finalAmount = $quotedAmount - $discountAmount + $shippingCharges + $taxAmount;

            // Update inquiry
            $inquiry->quoted_amount = $quotedAmount;
            $inquiry->discount_amount = $discountAmount;
            $inquiry->shipping_charges = $shippingCharges;
            $inquiry->tax_amount = $taxAmount;
            $inquiry->final_amount = $finalAmount;
            $inquiry->quote_valid_until = now()->addDays($request->quote_valid_days ?? 7);
            $inquiry->admin_notes = $request->admin_notes;
            if ($request->expected_delivery_date) {
                $inquiry->expected_delivery_date = $request->expected_delivery_date;
            }
            $inquiry->save();

            $user = Auth::user();
            $inquiry->updateStatus('quoted', 'Quote provided', $user->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Quote sent to partner.',
                'data' => [
                    'quoted_amount' => $quotedAmount,
                    'discount_amount' => $discountAmount,
                    'shipping_charges' => $shippingCharges,
                    'tax_amount' => $taxAmount,
                    'final_amount' => $finalAmount,
                    'valid_until' => $inquiry->quote_valid_until,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to provide quote.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject inquiry
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $inquiry = PartnerInquiry::find($id);

        if (!$inquiry) {
            return response()->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
        }

        if (!in_array($inquiry->status, ['pending', 'under_review'])) {
            return response()->json(['success' => false, 'message' => 'Inquiry cannot be rejected.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $inquiry->rejection_reason = $request->reason;
        $inquiry->save();
        $inquiry->updateStatus('rejected', 'Rejected: ' . $request->reason, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Inquiry rejected.',
        ]);
    }

    /**
     * Update inquiry status (for processing flow)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $inquiry = PartnerInquiry::find($id);

        if (!$inquiry) {
            return response()->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:processing,shipped,delivered,completed',
            'notes' => 'nullable|string|max:500',
            'tracking_number' => 'nullable|string|max:100',
            'courier_name' => 'nullable|string|max:100',
            'actual_delivery_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Validate status transitions
        $allowedTransitions = [
            'accepted' => ['processing'],
            'processing' => ['shipped'],
            'shipped' => ['delivered'],
            'delivered' => ['completed'],
        ];

        if (!isset($allowedTransitions[$inquiry->status]) || !in_array($request->status, $allowedTransitions[$inquiry->status])) {
            return response()->json([
                'success' => false,
                'message' => "Cannot change status from {$inquiry->status} to {$request->status}.",
            ], 422);
        }

        $user = Auth::user();

        // Update shipping info if provided
        if ($request->tracking_number) {
            $inquiry->tracking_number = $request->tracking_number;
        }
        if ($request->courier_name) {
            $inquiry->courier_name = $request->courier_name;
        }
        if ($request->actual_delivery_date) {
            $inquiry->actual_delivery_date = $request->actual_delivery_date;
        }
        $inquiry->save();

        $inquiry->updateStatus($request->status, $request->notes, $user->id);

        // Add tracking history if shipped
        if ($request->status === 'shipped') {
            $inquiry->addTrackingUpdate('Shipped', null, 'Order dispatched via ' . ($request->courier_name ?? 'courier'));
        }
        if ($request->status === 'delivered') {
            $inquiry->addTrackingUpdate('Delivered', null, 'Order delivered successfully');
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated to ' . $request->status,
        ]);
    }

    /**
     * Record payment (offline)
     */
    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $inquiry = PartnerInquiry::find($id);

        if (!$inquiry) {
            return response()->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
        }

        if (!in_array($inquiry->status, ['accepted', 'processing', 'shipped', 'delivered', 'completed'])) {
            return response()->json(['success' => false, 'message' => 'Payment cannot be recorded for this inquiry.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|in:cash,bank_transfer,cheque,upi,other',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $inquiry->recordPayment($request->amount, $request->method, $request->reference);

        // Log the payment
        $user = Auth::user();
        $inquiry->statusHistory()->create([
            'from_status' => $inquiry->status,
            'to_status' => $inquiry->status,
            'notes' => "Payment recorded: ₹{$request->amount} via {$request->method}" . ($request->notes ? " - {$request->notes}" : ''),
            'changed_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded.',
            'data' => [
                'amount_paid' => $inquiry->amount_paid,
                'balance' => $inquiry->balance_amount,
                'payment_status' => $inquiry->payment_status,
            ],
        ]);
    }

    /**
     * Update item fulfillment
     */
    public function updateItemFulfillment(Request $request, int $inquiryId, int $itemId): JsonResponse
    {
        $inquiry = PartnerInquiry::find($inquiryId);

        if (!$inquiry) {
            return response()->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
        }

        $item = PartnerInquiryItem::where('id', $itemId)
            ->where('inquiry_id', $inquiryId)
            ->first();

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantity_fulfilled' => 'required|integer|min:0|max:' . $item->quantity,
            'status' => 'nullable|in:pending,confirmed,out_of_stock,partially_available,ready,shipped,delivered',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $item->quantity_fulfilled = $request->quantity_fulfilled;
        if ($request->status) {
            $item->item_status = $request->status;
        }
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Item fulfillment updated.',
        ]);
    }

    /**
     * Send message to partner
     */
    public function sendMessage(Request $request, int $id): JsonResponse
    {
        $inquiry = PartnerInquiry::find($id);

        if (!$inquiry) {
            return response()->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'attachments' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $message = PartnerInquiryMessage::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => $user->id,
            'sender_type' => 'admin',
            'message' => $request->message,
            'attachments' => $request->attachments,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent to partner.',
            'data' => $message,
        ]);
    }

    /**
     * Add tracking update
     */
    public function addTrackingUpdate(Request $request, int $id): JsonResponse
    {
        $inquiry = PartnerInquiry::find($id);

        if (!$inquiry) {
            return response()->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|max:100',
            'location' => 'nullable|string|max:200',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $inquiry->addTrackingUpdate($request->status, $request->location, $request->notes);

        return response()->json([
            'success' => true,
            'message' => 'Tracking update added.',
        ]);
    }

    /**
     * Update admin notes
     */
    public function updateNotes(Request $request, int $id): JsonResponse
    {
        $inquiry = PartnerInquiry::find($id);

        if (!$inquiry) {
            return response()->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $inquiry->admin_notes = $request->admin_notes;
        $inquiry->save();

        return response()->json([
            'success' => true,
            'message' => 'Notes updated.',
        ]);
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'inquiry_ids' => 'required|array|min:1',
            'inquiry_ids.*' => 'exists:partner_inquiries,id',
            'status' => 'required|in:under_review,processing,shipped,delivered,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $updated = 0;
        $failed = 0;

        foreach ($request->inquiry_ids as $id) {
            $inquiry = PartnerInquiry::find($id);
            if ($inquiry) {
                try {
                    $inquiry->updateStatus($request->status, 'Bulk status update', $user->id);
                    $updated++;
                } catch (\Exception $e) {
                    $failed++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Updated {$updated} inquiries. {$failed} failed.",
        ]);
    }
}
