<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;
use App\Models\OrderProduct;
use App\Services\Payment\RazorpayService;
use App\Services\Shipping\ShiprocketService;
use App\Services\Offers\OfferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ORDER SERVICE - Complete Checkout Flow
 * 
 * BACKWARD COMPATIBLE: Works with EXISTING Order model fields
 * Orchestrates: Cart → Order → Payment → Shipping
 */
class OrderService
{
    public function __construct(
        private RazorpayService $razorpay,
        private ShiprocketService $shiprocket,
        private OfferService $offerService
    ) {}

    /**
     * Create order from cart
     * Uses EXISTING Order fields
     */
    public function createFromCart(
        int $userId,
        int $billingAddressId,
        int $shippingAddressId,
        ?int $offerId = null,
        ?string $couponCode = null,
        ?string $notes = null
    ): array {
        // Get cart items
        $cartItems = Cart::with(['product.charges', 'product.taxCharges'])
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->get();

        if ($cartItems->isEmpty()) {
            return ['success' => false, 'error' => 'Cart is empty'];
        }

        // Validate stock
        foreach ($cartItems as $item) {
            $product = Product::find($item->product_id);
            if (!$product || $product->available_stock < $item->quantity) {
                return [
                    'success' => false, 
                    'error' => "Insufficient stock for {$product->name ?? 'product'}"
                ];
            }
        }

        // Calculate totals
        $orderSubtotal = $cartItems->sum('product_base_price');
        $totalCharges = $cartItems->sum('charges_total');
        $totalTaxes = $cartItems->sum('tax_total');
        $orderTotal = $orderSubtotal + $totalCharges + $totalTaxes;

        // Apply offer if provided
        $discountAmount = 0;
        if ($offerId) {
            $productIds = $cartItems->pluck('product_id')->toArray();
            $categoryIds = $cartItems->pluck('product.category_id')->filter()->unique()->toArray();
            
            $offerResult = $this->offerService->applyOffer(
                $offerId, 
                $userId, 
                $orderTotal, 
                $productIds, 
                $categoryIds, 
                $couponCode
            );
            
            if ($offerResult['success']) {
                $discountAmount = $offerResult['discount_amount'];
                $orderTotal -= $discountAmount;
            }
        }

        return DB::transaction(function () use (
            $userId, $billingAddressId, $shippingAddressId, 
            $cartItems, $orderSubtotal, $totalCharges, $totalTaxes, 
            $orderTotal, $discountAmount, $offerId, $couponCode, $notes
        ) {
            // Generate order code using EXISTING field name
            $orderCode = 'YJS-' . strtoupper(Str::random(8));

            // Create order using EXISTING fields
            $order = Order::create([
                'custom_order_code' => $orderCode,
                'order_date' => now()->toDateString(),
                'customer_type' => 'existing',
                'customer_id' => $userId,
                'billing_address_id' => $billingAddressId,
                'shipping_address_id' => $shippingAddressId,
                'order_status' => 'pending', // EXISTING enum
                'payment_status' => 'pending', // EXISTING enum
                'order_subtotal' => $orderSubtotal,
                'total_charges' => $totalCharges,
                'total_taxes' => $totalTaxes,
                'coupon_code' => $couponCode,
                'order_total' => $orderTotal,
                'notes' => $notes,
            ]);

            // Create order products
            foreach ($cartItems as $item) {
                OrderProduct::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product_base_price,
                    'charges' => $item->charges_total,
                    'taxes' => $item->tax_total,
                    'total' => $item->cart_total,
                ]);

                // Reserve stock
                Product::where('id', $item->product_id)
                    ->decrement('available_stock', $item->quantity);
            }

            // Snapshot offer if applied
            if ($offerId && $discountAmount > 0) {
                $this->offerService->snapshotOfferForOrder($order, $offerId, $discountAmount, $couponCode);
            }

            // Clear cart
            Cart::where('user_id', $userId)->delete();

            // Create Razorpay order for payment
            $paymentData = $this->razorpay->createOrder($order);

            return [
                'success' => true,
                'order_id' => $order->id,
                'order_code' => $orderCode,
                'order_total' => $orderTotal,
                'discount_applied' => $discountAmount,
                'payment' => $paymentData,
            ];
        });
    }

    /**
     * Cancel order (pre-shipment only)
     * Uses EXISTING order_status enum
     */
    public function cancelOrder(Order $order, string $reason, string $cancelledBy = 'customer'): array
    {
        // Only allow cancellation before shipping
        $allowedStatuses = ['pending', 'confirmed', 'processing'];
        if (!in_array($order->order_status, $allowedStatuses)) {
            return ['success' => false, 'error' => 'Order cannot be cancelled after shipping'];
        }

        // Check 24-hour window for customer cancellations
        if ($cancelledBy === 'customer' && $order->created_at->diffInHours(now()) > 24) {
            return ['success' => false, 'error' => 'Cancellation window expired (24 hours)'];
        }

        return DB::transaction(function () use ($order, $reason, $cancelledBy) {
            $previousStatus = $order->order_status;

            // Update order using EXISTING enum
            $order->update([
                'order_status' => 'cancelled',
            ]);

            // Restore inventory
            foreach ($order->orderProducts as $item) {
                Product::where('id', $item->product_id)
                    ->increment('available_stock', $item->quantity);
            }

            // Cancel Shiprocket if created
            if ($order->shiprocket_order_id) {
                try {
                    $this->shiprocket->cancelOrder($order);
                } catch (\Exception $e) {
                    Log::warning("Failed to cancel Shiprocket order: " . $e->getMessage());
                }
            }

            // Process refund if paid
            $refund = null;
            if ($order->payment_status === 'paid') {
                try {
                    $refund = $this->razorpay->refundFull($order, $reason);
                } catch (\Exception $e) {
                    Log::error("Refund failed for order {$order->id}: " . $e->getMessage());
                }
            }

            // Rollback offer usage
            $this->offerService->rollbackOfferUsage($order->id, 'order_cancelled');

            // Create cancellation record
            \App\Models\OrderCancellation::create([
                'order_id' => $order->id,
                'cancelled_by' => $cancelledBy,
                'cancelled_by_user_id' => auth()->id(),
                'reason_code' => $this->mapReasonToCode($reason),
                'reason_text' => $reason,
                'order_status_at_cancel' => $previousStatus,
                'refund_id' => $refund?->id,
                'refund_amount' => $refund?->amount,
                'refund_status' => $refund?->status,
                'cancelled_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Order cancelled successfully',
                'refund_initiated' => $refund !== null,
                'refund_amount' => $refund?->amount,
            ];
        });
    }

    /**
     * Process order after successful payment
     * Updates EXISTING fields
     */
    public function processAfterPayment(Order $order): void
    {
        $order->update([
            'order_status' => 'confirmed', // EXISTING enum
            'payment_status' => 'paid', // EXISTING enum
            'paid_at' => now(),
        ]);

        // Optionally auto-push to Shiprocket
        if (config('services.shiprocket.auto_create_order', false)) {
            try {
                $this->shiprocket->createOrder($order);
            } catch (\Exception $e) {
                Log::warning("Auto Shiprocket creation failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Get order with full details
     */
    public function getOrderDetails(int $orderId, int $userId): ?array
    {
        $order = Order::with([
            'customer',
            'billingAddress',
            'shippingAddress',
            'orderProducts.product',
        ])->where('id', $orderId)
          ->where('customer_id', $userId)
          ->first();

        if (!$order) {
            return null;
        }

        // Get payment info
        $payment = $order->payments()->latest()->first();

        // Get tracking if shipped
        $tracking = null;
        if ($order->awb_number) {
            try {
                $tracking = $this->shiprocket->trackByAWB($order->awb_number);
            } catch (\Exception $e) {
                // Ignore tracking errors
            }
        }

        return [
            'order' => $order,
            'payment' => $payment,
            'tracking' => $tracking,
        ];
    }

    /**
     * Map reason text to code
     */
    private function mapReasonToCode(string $reason): string
    {
        $reasonMap = [
            'changed_mind' => 'CHANGED_MIND',
            'found_better_price' => 'BETTER_PRICE',
            'ordered_by_mistake' => 'MISTAKE',
            'taking_too_long' => 'DELIVERY_TIME',
            'payment_issue' => 'PAYMENT_ISSUE',
            'other' => 'OTHER',
        ];

        return $reasonMap[$reason] ?? 'OTHER';
    }
}
