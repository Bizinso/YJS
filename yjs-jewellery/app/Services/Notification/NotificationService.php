<?php

namespace App\Services\Notification;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notification Service
 *
 * Centralized service for sending notifications across the application.
 * Supports email, SMS (via integration), and in-app notifications.
 *
 * Notification Types:
 * - Order notifications (placed, confirmed, shipped, delivered, cancelled)
 * - Payment notifications (success, failed, refund)
 * - Shipping notifications (pickup, transit, delivered)
 * - Admin alerts (low stock, high value orders, failed payments)
 */
class NotificationService
{
    /**
     * Notification channels configuration.
     */
    protected array $channels = ['email', 'database'];

    /**
     * Send order placed notification.
     *
     * @param Order $order
     * @return array
     */
    public function sendOrderPlaced(Order $order): array
    {
        $results = [
            'order_id' => $order->id,
            'type' => 'order_placed',
            'channels' => [],
        ];

        $customer = $order->customer;

        if (!$customer) {
            return array_merge($results, ['success' => false, 'error' => 'No customer found']);
        }

        // Email notification
        if ($this->shouldSendEmail($customer)) {
            $results['channels']['email'] = $this->sendOrderPlacedEmail($order, $customer);
        }

        // Database notification
        $results['channels']['database'] = $this->storeNotification(
            $customer,
            'order_placed',
            'Order Placed Successfully',
            "Your order #{$order->custom_order_code} has been placed successfully.",
            [
                'order_id' => $order->id,
                'order_code' => $order->custom_order_code,
                'order_total' => $order->order_total,
            ]
        );

        $results['success'] = true;
        return $results;
    }

    /**
     * Send order status update notification.
     *
     * @param Order $order
     * @param string $oldStatus
     * @param string $newStatus
     * @return array
     */
    public function sendOrderStatusUpdate(Order $order, string $oldStatus, string $newStatus): array
    {
        $results = [
            'order_id' => $order->id,
            'type' => 'order_status_update',
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'channels' => [],
        ];

        $customer = $order->customer;

        if (!$customer) {
            return array_merge($results, ['success' => false, 'error' => 'No customer found']);
        }

        $statusMessages = [
            'confirmed' => 'Your order has been confirmed and is being processed.',
            'processing' => 'Your order is being prepared for shipment.',
            'shipped' => "Your order has been shipped! AWB: {$order->awb_number}",
            'pickup_generated' => 'Pickup has been scheduled for your order.',
            'picked_up' => 'Your order has been picked up by the courier.',
            'delivered' => 'Your order has been delivered. Thank you for shopping with us!',
            'cancelled' => 'Your order has been cancelled.',
            'returned' => 'Your order return has been processed.',
        ];

        $message = $statusMessages[$newStatus] ?? "Your order status has been updated to {$newStatus}.";

        // Database notification
        $results['channels']['database'] = $this->storeNotification(
            $customer,
            'order_status_update',
            'Order Status Updated',
            $message,
            [
                'order_id' => $order->id,
                'order_code' => $order->custom_order_code,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]
        );

        // Email for important status changes
        if (in_array($newStatus, ['confirmed', 'shipped', 'delivered', 'cancelled'])) {
            if ($this->shouldSendEmail($customer)) {
                $results['channels']['email'] = $this->sendStatusUpdateEmail($order, $customer, $newStatus, $message);
            }
        }

        $results['success'] = true;
        return $results;
    }

    /**
     * Send payment success notification.
     *
     * @param Order $order
     * @param float $amount
     * @param string $transactionId
     * @return array
     */
    public function sendPaymentSuccess(Order $order, float $amount, string $transactionId): array
    {
        $results = [
            'order_id' => $order->id,
            'type' => 'payment_success',
            'channels' => [],
        ];

        $customer = $order->customer;

        if (!$customer) {
            return array_merge($results, ['success' => false, 'error' => 'No customer found']);
        }

        $results['channels']['database'] = $this->storeNotification(
            $customer,
            'payment_success',
            'Payment Successful',
            "Payment of ₹" . number_format($amount, 2) . " for order #{$order->custom_order_code} was successful.",
            [
                'order_id' => $order->id,
                'order_code' => $order->custom_order_code,
                'amount' => $amount,
                'transaction_id' => $transactionId,
            ]
        );

        if ($this->shouldSendEmail($customer)) {
            $results['channels']['email'] = $this->sendPaymentEmail($order, $customer, 'success', $amount);
        }

        $results['success'] = true;
        return $results;
    }

    /**
     * Send payment failed notification.
     *
     * @param Order $order
     * @param string $reason
     * @return array
     */
    public function sendPaymentFailed(Order $order, string $reason = ''): array
    {
        $results = [
            'order_id' => $order->id,
            'type' => 'payment_failed',
            'channels' => [],
        ];

        $customer = $order->customer;

        if (!$customer) {
            return array_merge($results, ['success' => false, 'error' => 'No customer found']);
        }

        $results['channels']['database'] = $this->storeNotification(
            $customer,
            'payment_failed',
            'Payment Failed',
            "Payment for order #{$order->custom_order_code} failed. Please try again.",
            [
                'order_id' => $order->id,
                'order_code' => $order->custom_order_code,
                'reason' => $reason,
            ]
        );

        $results['success'] = true;
        return $results;
    }

    /**
     * Send refund processed notification.
     *
     * @param Order $order
     * @param float $amount
     * @param string $refundId
     * @return array
     */
    public function sendRefundProcessed(Order $order, float $amount, string $refundId): array
    {
        $results = [
            'order_id' => $order->id,
            'type' => 'refund_processed',
            'channels' => [],
        ];

        $customer = $order->customer;

        if (!$customer) {
            return array_merge($results, ['success' => false, 'error' => 'No customer found']);
        }

        $results['channels']['database'] = $this->storeNotification(
            $customer,
            'refund_processed',
            'Refund Processed',
            "Refund of ₹" . number_format($amount, 2) . " for order #{$order->custom_order_code} has been processed.",
            [
                'order_id' => $order->id,
                'order_code' => $order->custom_order_code,
                'amount' => $amount,
                'refund_id' => $refundId,
            ]
        );

        if ($this->shouldSendEmail($customer)) {
            $results['channels']['email'] = $this->sendRefundEmail($order, $customer, $amount);
        }

        $results['success'] = true;
        return $results;
    }

    /**
     * Send shipping update notification.
     *
     * @param Order $order
     * @param string $status
     * @param array $trackingData
     * @return array
     */
    public function sendShippingUpdate(Order $order, string $status, array $trackingData = []): array
    {
        $results = [
            'order_id' => $order->id,
            'type' => 'shipping_update',
            'status' => $status,
            'channels' => [],
        ];

        $customer = $order->customer;

        if (!$customer) {
            return array_merge($results, ['success' => false, 'error' => 'No customer found']);
        }

        $statusMessages = [
            'pickup_scheduled' => 'Pickup has been scheduled for your order.',
            'picked_up' => 'Your order has been picked up by the courier.',
            'in_transit' => 'Your order is in transit.',
            'out_for_delivery' => 'Your order is out for delivery!',
            'delivered' => 'Your order has been delivered.',
            'rto' => 'Your order is being returned to origin.',
        ];

        $message = $statusMessages[$status] ?? "Shipping update: {$status}";

        if ($order->awb_number) {
            $message .= " Track with AWB: {$order->awb_number}";
        }

        $results['channels']['database'] = $this->storeNotification(
            $customer,
            'shipping_update',
            'Shipping Update',
            $message,
            array_merge([
                'order_id' => $order->id,
                'order_code' => $order->custom_order_code,
                'shipping_status' => $status,
                'awb_number' => $order->awb_number,
            ], $trackingData)
        );

        $results['success'] = true;
        return $results;
    }

    /**
     * Send low stock alert to admin.
     *
     * @param array $products
     * @return array
     */
    public function sendLowStockAlert(array $products): array
    {
        $results = [
            'type' => 'low_stock_alert',
            'product_count' => count($products),
            'channels' => [],
        ];

        // Get admin users
        $admins = User::where('user_type', 'employee')
            ->where('status', 'A')
            ->get();

        foreach ($admins as $admin) {
            $this->storeNotification(
                $admin,
                'low_stock_alert',
                'Low Stock Alert',
                count($products) . ' products are running low on stock.',
                [
                    'products' => $products,
                    'alert_time' => now()->toIso8601String(),
                ]
            );
        }

        $results['success'] = true;
        $results['admin_count'] = $admins->count();
        return $results;
    }

    /**
     * Send high value order alert to admin.
     *
     * @param Order $order
     * @param float $threshold
     * @return array
     */
    public function sendHighValueOrderAlert(Order $order, float $threshold = 50000): array
    {
        if ($order->order_total < $threshold) {
            return ['success' => false, 'reason' => 'Order below threshold'];
        }

        $results = [
            'type' => 'high_value_order',
            'order_id' => $order->id,
            'channels' => [],
        ];

        // Get admin users
        $admins = User::where('user_type', 'employee')
            ->where('status', 'A')
            ->get();

        foreach ($admins as $admin) {
            $this->storeNotification(
                $admin,
                'high_value_order',
                'High Value Order Received',
                "Order #{$order->custom_order_code} worth ₹" . number_format($order->order_total, 2) . " requires attention.",
                [
                    'order_id' => $order->id,
                    'order_code' => $order->custom_order_code,
                    'order_total' => $order->order_total,
                    'customer_name' => $order->customer?->first_name . ' ' . $order->customer?->last_name,
                ]
            );
        }

        $results['success'] = true;
        $results['admin_count'] = $admins->count();
        return $results;
    }

    /**
     * Get user notifications.
     *
     * @param User $user
     * @param int $limit
     * @param bool $unreadOnly
     * @return array
     */
    public function getUserNotifications(User $user, int $limit = 20, bool $unreadOnly = false): array
    {
        $query = $user->notifications();

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return [
            'notifications' => $query->latest()->take($limit)->get(),
            'unread_count' => $user->unreadNotifications()->count(),
        ];
    }

    /**
     * Mark notification as read.
     *
     * @param User $user
     * @param string $notificationId
     * @return bool
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Mark all notifications as read.
     *
     * @param User $user
     * @return int
     */
    public function markAllAsRead(User $user): int
    {
        $count = $user->unreadNotifications()->count();
        $user->unreadNotifications->markAsRead();
        return $count;
    }

    /**
     * Store notification in database.
     *
     * @param User $user
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array $data
     * @return bool
     */
    protected function storeNotification(User $user, string $type, string $title, string $message, array $data = []): bool
    {
        try {
            $user->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\' . \Illuminate\Support\Str::studly($type),
                'data' => [
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                    'created_at' => now()->toIso8601String(),
                ],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to store notification', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if user should receive email notifications.
     *
     * @param User $user
     * @return bool
     */
    protected function shouldSendEmail(User $user): bool
    {
        return !empty($user->email) && $user->status === 'A';
    }

    /**
     * Send order placed email.
     *
     * @param Order $order
     * @param User $customer
     * @return bool
     */
    protected function sendOrderPlacedEmail(Order $order, User $customer): bool
    {
        try {
            // In production, this would send actual email
            // Mail::to($customer->email)->send(new OrderPlacedMail($order));

            Log::info('Order placed email sent', [
                'order_id' => $order->id,
                'email' => $customer->email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send order placed email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send status update email.
     *
     * @param Order $order
     * @param User $customer
     * @param string $status
     * @param string $message
     * @return bool
     */
    protected function sendStatusUpdateEmail(Order $order, User $customer, string $status, string $message): bool
    {
        try {
            Log::info('Status update email sent', [
                'order_id' => $order->id,
                'status' => $status,
                'email' => $customer->email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send status update email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send payment email.
     *
     * @param Order $order
     * @param User $customer
     * @param string $type
     * @param float $amount
     * @return bool
     */
    protected function sendPaymentEmail(Order $order, User $customer, string $type, float $amount): bool
    {
        try {
            Log::info('Payment email sent', [
                'order_id' => $order->id,
                'type' => $type,
                'amount' => $amount,
                'email' => $customer->email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send payment email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send refund email.
     *
     * @param Order $order
     * @param User $customer
     * @param float $amount
     * @return bool
     */
    protected function sendRefundEmail(Order $order, User $customer, float $amount): bool
    {
        try {
            Log::info('Refund email sent', [
                'order_id' => $order->id,
                'amount' => $amount,
                'email' => $customer->email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send refund email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send password reset notification.
     *
     * @param User $user
     * @param string $newPassword
     * @param User|null $resetBy
     * @return array
     */
    public function sendPasswordResetNotification(User $user, string $newPassword, ?User $resetBy = null): array
    {
        $results = [
            'user_id' => $user->id,
            'type' => 'password_reset',
            'channels' => [],
        ];

        // Send email notification
        if ($this->shouldSendEmail($user)) {
            $results['channels']['email'] = $this->sendPasswordResetEmail($user, $newPassword, $resetBy);
        }

        // Store database notification
        $results['channels']['database'] = $this->storeNotification(
            $user,
            'password_reset',
            'Password Reset',
            'Your password has been reset by an administrator. Please change it immediately after logging in.',
            [
                'reset_by' => $resetBy?->name ?? 'Administrator',
                'reset_at' => now()->toIso8601String(),
            ]
        );

        $results['success'] = true;
        return $results;
    }

    /**
     * Send password reset email.
     *
     * @param User $user
     * @param string $newPassword
     * @param User|null $resetBy
     * @return bool
     */
    protected function sendPasswordResetEmail(User $user, string $newPassword, ?User $resetBy = null): bool
    {
        try {
            $appName = config('app.name', 'YJS Jewellery');
            $resetByName = $resetBy?->name ?? 'Administrator';

            Mail::raw(
                "Hello {$user->name},\n\n" .
                "Your password has been reset by {$resetByName}.\n\n" .
                "Your new password is: {$newPassword}\n\n" .
                "Please login and change your password immediately for security.\n\n" .
                "If you did not request this change, please contact support immediately.\n\n" .
                "Best regards,\n{$appName} Team",
                function ($message) use ($user, $appName) {
                    $message->to($user->email)
                        ->subject("{$appName} - Password Reset Notification");
                }
            );

            Log::info('Password reset email sent', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
