<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\PartnerInquiry;
use App\Models\PartnerInquiryItem;
use App\Models\PartnerInquiryMessage;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Partner Inquiry Controller
 *
 * Handles B2B bulk order inquiries for partners.
 * Partners can:
 * - Create inquiries (bulk order requests) without seeing prices
 * - View and manage their inquiries
 * - Accept or reject quotes from admin
 * - Track order fulfillment
 * - Communicate with admin
 */
class PartnerInquiryController extends Controller
{
    /**
     * Get list of partner's inquiries
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner || !$partner->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Partner account not approved.',
            ], 403);
        }

        $query = PartnerInquiry::where('partner_id', $partner->id)
            ->with(['items.product:id,name,main_image,sku'])
            ->withCount('items')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search by inquiry code
        if ($request->has('search')) {
            $query->where('inquiry_code', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->input('per_page', 15);
        $inquiries = $query->paginate($perPage);

        $formatted = $inquiries->getCollection()->map(function ($inquiry) {
            return [
                'id' => $inquiry->id,
                'inquiry_code' => $inquiry->inquiry_code,
                'status' => $inquiry->status,
                'status_label' => $inquiry->status_label,
                'priority' => $inquiry->priority,
                'priority_label' => $inquiry->priority_label,
                'items_count' => $inquiry->items_count,
                'total_quantity' => $inquiry->items->sum('quantity'),
                'notes' => $inquiry->notes,
                'quoted_amount' => $inquiry->status === 'quoted' || in_array($inquiry->status, ['accepted', 'processing', 'shipped', 'delivered', 'completed'])
                    ? $inquiry->final_amount
                    : null,
                'quote_valid_until' => $inquiry->quote_valid_until,
                'is_quote_valid' => $inquiry->is_quote_valid,
                'expected_delivery_date' => $inquiry->expected_delivery_date,
                'tracking_number' => $inquiry->tracking_number,
                'payment_status' => $inquiry->payment_status,
                'created_at' => $inquiry->created_at,
                'first_product_image' => $inquiry->items->first()?->product?->main_image,
                'can_cancel' => $inquiry->canBeCancelled(),
                'can_edit' => $inquiry->canBeEdited(),
                'can_accept_quote' => $inquiry->canAcceptQuote(),
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
     * Get inquiry statistics for dashboard
     */
    public function statistics(): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner profile not found.',
            ], 404);
        }

        $baseQuery = PartnerInquiry::where('partner_id', $partner->id);

        $stats = [
            'total_inquiries' => (clone $baseQuery)->count(),
            'pending_inquiries' => (clone $baseQuery)->where('status', 'pending')->count(),
            'quoted_inquiries' => (clone $baseQuery)->where('status', 'quoted')->count(),
            'processing_inquiries' => (clone $baseQuery)->whereIn('status', ['accepted', 'processing'])->count(),
            'shipped_inquiries' => (clone $baseQuery)->where('status', 'shipped')->count(),
            'delivered_inquiries' => (clone $baseQuery)->where('status', 'delivered')->count(),
            'completed_inquiries' => (clone $baseQuery)->where('status', 'completed')->count(),
            'cancelled_inquiries' => (clone $baseQuery)->whereIn('status', ['cancelled', 'rejected'])->count(),
            'this_month_inquiries' => (clone $baseQuery)->whereMonth('created_at', now()->month)->count(),
            'pending_payments' => (clone $baseQuery)
                ->whereIn('status', ['accepted', 'processing', 'shipped', 'delivered'])
                ->where('payment_status', '!=', 'paid')
                ->sum('final_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Create a new inquiry (bulk order request)
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner || !$partner->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Partner account not approved.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.specifications' => 'nullable|string|max:1000',
            'items.*.notes' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'shipping_address_id' => 'nullable|exists:customer_addresses,id',
            'expected_delivery_date' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify all products are visible to this partner
        $productIds = collect($request->items)->pluck('product_id')->unique();
        $visibleProducts = Product::whereIn('id', $productIds)
            ->where(function ($q) use ($user) {
                $q->whereIn('visible_to', ['partner', 'both'])
                    ->where(function ($sub) use ($user) {
                        $sub->whereNull('visible_partner_ids')
                            ->orWhereJsonContains('visible_partner_ids', (string) $user->id);
                    });
            })
            ->pluck('id');

        $invalidProducts = $productIds->diff($visibleProducts);
        if ($invalidProducts->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Some products are not available for your account.',
                'invalid_products' => $invalidProducts->values(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create inquiry
            $inquiry = PartnerInquiry::create([
                'partner_id' => $partner->id,
                'user_id' => $user->id,
                'status' => 'pending',
                'notes' => $request->notes,
                'priority' => $request->priority ?? 'normal',
                'shipping_address_id' => $request->shipping_address_id,
                'expected_delivery_date' => $request->expected_delivery_date,
            ]);

            // Create inquiry items
            foreach ($request->items as $item) {
                PartnerInquiryItem::create([
                    'inquiry_id' => $inquiry->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'pcs',
                    'specifications' => $item['specifications'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'item_status' => 'pending',
                ]);
            }

            // Record initial status
            $inquiry->statusHistory()->create([
                'from_status' => null,
                'to_status' => 'pending',
                'notes' => 'Inquiry created',
                'changed_by' => $user->id,
            ]);

            DB::commit();

            $inquiry->load('items.product:id,name,sku,main_image');

            return response()->json([
                'success' => true,
                'message' => 'Inquiry submitted successfully. Our team will review and provide a quote.',
                'data' => [
                    'id' => $inquiry->id,
                    'inquiry_code' => $inquiry->inquiry_code,
                    'status' => $inquiry->status,
                    'items_count' => $inquiry->items->count(),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create inquiry.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get inquiry details
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        $inquiry = PartnerInquiry::where('id', $id)
            ->where('partner_id', $partner->id)
            ->with([
                'items.product:id,name,sku,main_image,description',
                'items.variant:id,name,sku,main_image',
                'shippingAddress',
                'statusHistory.changedBy:id,first_name,last_name',
                'messages.user:id,first_name,last_name',
            ])
            ->first();

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found.',
            ], 404);
        }

        // Mark unread admin messages as read
        $inquiry->messages()
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $inquiry->id,
                'inquiry_code' => $inquiry->inquiry_code,
                'status' => $inquiry->status,
                'status_label' => $inquiry->status_label,
                'priority' => $inquiry->priority,
                'priority_label' => $inquiry->priority_label,
                'notes' => $inquiry->notes,
                'admin_notes' => $inquiry->admin_notes,
                'rejection_reason' => $inquiry->rejection_reason,
                'items' => $inquiry->items->map(function ($item) use ($inquiry) {
                    $data = [
                        'id' => $item->id,
                        'product' => $item->product,
                        'variant' => $item->variant,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'specifications' => $item->specifications,
                        'notes' => $item->notes,
                        'item_status' => $item->item_status,
                        'status_label' => $item->status_label,
                        'quantity_fulfilled' => $item->quantity_fulfilled,
                    ];
                    // Only show pricing after quote is accepted
                    if (in_array($inquiry->status, ['quoted', 'accepted', 'processing', 'shipped', 'delivered', 'completed'])) {
                        $data['unit_price'] = $item->unit_price;
                        $data['total_price'] = $item->total_price;
                        $data['discount'] = $item->discount;
                    }
                    return $data;
                }),
                'quote' => in_array($inquiry->status, ['quoted', 'accepted', 'processing', 'shipped', 'delivered', 'completed']) ? [
                    'quoted_amount' => $inquiry->quoted_amount,
                    'discount_amount' => $inquiry->discount_amount,
                    'shipping_charges' => $inquiry->shipping_charges,
                    'tax_amount' => $inquiry->tax_amount,
                    'final_amount' => $inquiry->final_amount,
                    'quote_valid_until' => $inquiry->quote_valid_until,
                    'is_quote_valid' => $inquiry->is_quote_valid,
                    'quoted_at' => $inquiry->quoted_at,
                ] : null,
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
                    'amount_paid' => $inquiry->amount_paid,
                    'balance' => $inquiry->balance_amount,
                ],
                'status_history' => $inquiry->statusHistory->map(function ($history) {
                    return [
                        'from' => $history->from_status,
                        'to' => $history->to_status,
                        'change' => $history->status_change,
                        'notes' => $history->notes,
                        'changed_by' => $history->changedBy ? $history->changedBy->first_name . ' ' . $history->changedBy->last_name : 'System',
                        'created_at' => $history->created_at,
                    ];
                }),
                'messages' => $inquiry->messages->map(function ($msg) {
                    return [
                        'id' => $msg->id,
                        'sender_type' => $msg->sender_type,
                        'sender_name' => $msg->user ? $msg->user->first_name . ' ' . $msg->user->last_name : 'Unknown',
                        'message' => $msg->message,
                        'attachments' => $msg->attachments,
                        'is_read' => $msg->is_read,
                        'created_at' => $msg->created_at,
                    ];
                }),
                'created_at' => $inquiry->created_at,
                'can_cancel' => $inquiry->canBeCancelled(),
                'can_edit' => $inquiry->canBeEdited(),
                'can_accept_quote' => $inquiry->canAcceptQuote(),
            ],
        ]);
    }

    /**
     * Update inquiry (only if pending/under_review)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        $inquiry = PartnerInquiry::where('id', $id)
            ->where('partner_id', $partner->id)
            ->first();

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found.',
            ], 404);
        }

        if (!$inquiry->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'This inquiry can no longer be edited.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:2000',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'shipping_address_id' => 'nullable|exists:customer_addresses,id',
            'expected_delivery_date' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $inquiry->update($request->only([
            'notes',
            'priority',
            'shipping_address_id',
            'expected_delivery_date',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Inquiry updated successfully.',
        ]);
    }

    /**
     * Add item to existing inquiry
     */
    public function addItem(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        $inquiry = PartnerInquiry::where('id', $id)
            ->where('partner_id', $partner->id)
            ->first();

        if (!$inquiry || !$inquiry->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry cannot be modified.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'unit' => 'nullable|string|max:20',
            'specifications' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify product visibility
        $product = Product::where('id', $request->product_id)
            ->where(function ($q) use ($user) {
                $q->whereIn('visible_to', ['partner', 'both'])
                    ->where(function ($sub) use ($user) {
                        $sub->whereNull('visible_partner_ids')
                            ->orWhereJsonContains('visible_partner_ids', (string) $user->id);
                    });
            })
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not available.',
            ], 422);
        }

        $item = PartnerInquiryItem::create([
            'inquiry_id' => $inquiry->id,
            'product_id' => $request->product_id,
            'variant_id' => $request->variant_id,
            'quantity' => $request->quantity,
            'unit' => $request->unit ?? 'pcs',
            'specifications' => $request->specifications,
            'notes' => $request->notes,
            'item_status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item added to inquiry.',
            'data' => $item->load('product:id,name,sku,main_image'),
        ]);
    }

    /**
     * Remove item from inquiry
     */
    public function removeItem(int $inquiryId, int $itemId): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        $inquiry = PartnerInquiry::where('id', $inquiryId)
            ->where('partner_id', $partner->id)
            ->first();

        if (!$inquiry || !$inquiry->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry cannot be modified.',
            ], 422);
        }

        $item = PartnerInquiryItem::where('id', $itemId)
            ->where('inquiry_id', $inquiryId)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
            ], 404);
        }

        // Don't allow removing the last item
        if ($inquiry->items()->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove the last item. Cancel the inquiry instead.',
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from inquiry.',
        ]);
    }

    /**
     * Accept quote from admin
     */
    public function acceptQuote(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        $inquiry = PartnerInquiry::where('id', $id)
            ->where('partner_id', $partner->id)
            ->first();

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found.',
            ], 404);
        }

        if (!$inquiry->canAcceptQuote()) {
            return response()->json([
                'success' => false,
                'message' => 'Quote cannot be accepted. It may have expired or the inquiry is not in quoted status.',
            ], 422);
        }

        $inquiry->updateStatus('accepted', 'Quote accepted by partner', $user->id);
        $inquiry->partner_response_notes = $request->notes;
        $inquiry->save();

        return response()->json([
            'success' => true,
            'message' => 'Quote accepted successfully. Our team will process your order.',
        ]);
    }

    /**
     * Reject/decline quote
     */
    public function rejectQuote(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        $inquiry = PartnerInquiry::where('id', $id)
            ->where('partner_id', $partner->id)
            ->where('status', 'quoted')
            ->first();

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found or not in quoted status.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $inquiry->updateStatus('cancelled', 'Quote declined by partner: ' . $request->reason, $user->id);
        $inquiry->partner_response_notes = $request->reason;
        $inquiry->save();

        return response()->json([
            'success' => true,
            'message' => 'Quote declined.',
        ]);
    }

    /**
     * Cancel inquiry
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        $inquiry = PartnerInquiry::where('id', $id)
            ->where('partner_id', $partner->id)
            ->first();

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found.',
            ], 404);
        }

        if (!$inquiry->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'This inquiry cannot be cancelled.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $inquiry->updateStatus('cancelled', 'Cancelled by partner: ' . $request->reason, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Inquiry cancelled successfully.',
        ]);
    }

    /**
     * Send message to admin
     */
    public function sendMessage(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        $inquiry = PartnerInquiry::where('id', $id)
            ->where('partner_id', $partner->id)
            ->first();

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'attachments' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = PartnerInquiryMessage::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => $user->id,
            'sender_type' => 'partner',
            'message' => $request->message,
            'attachments' => $request->attachments,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent.',
            'data' => $message,
        ]);
    }

    /**
     * Get tracking information
     */
    public function tracking(int $id): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        $inquiry = PartnerInquiry::where('id', $id)
            ->where('partner_id', $partner->id)
            ->with('items.product:id,name,main_image')
            ->first();

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found.',
            ], 404);
        }

        // Build status timeline
        $statuses = ['pending', 'under_review', 'quoted', 'accepted', 'processing', 'shipped', 'delivered', 'completed'];
        $currentIndex = array_search($inquiry->status, $statuses);

        $timeline = collect($statuses)->map(function ($status, $index) use ($currentIndex, $inquiry) {
            $isCancelled = in_array($inquiry->status, ['cancelled', 'rejected']);
            return [
                'status' => $status,
                'label' => PartnerInquiry::STATUS_LABELS[$status] ?? ucfirst($status),
                'completed' => !$isCancelled && $index <= $currentIndex,
                'current' => $index === $currentIndex && !$isCancelled,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'inquiry_code' => $inquiry->inquiry_code,
                'status' => $inquiry->status,
                'status_label' => $inquiry->status_label,
                'timeline' => $timeline,
                'delivery' => [
                    'expected_date' => $inquiry->expected_delivery_date,
                    'actual_date' => $inquiry->actual_delivery_date,
                    'courier_name' => $inquiry->courier_name,
                    'tracking_number' => $inquiry->tracking_number,
                ],
                'tracking_history' => $inquiry->tracking_history ?? [],
                'items_fulfillment' => $inquiry->items->map(function ($item) {
                    return [
                        'product' => $item->product?->name,
                        'quantity' => $item->quantity,
                        'fulfilled' => $item->quantity_fulfilled,
                        'remaining' => $item->remaining_quantity,
                        'status' => $item->status_label,
                    ];
                }),
            ],
        ]);
    }
}
