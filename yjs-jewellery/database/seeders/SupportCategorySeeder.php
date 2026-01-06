<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupportCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create canned responses for common support scenarios
        $cannedResponses = [
            [
                'title' => 'Order Shipped Notification',
                'shortcode' => 'order_shipped',
                'content' => "Dear {{customer_name}},\n\nGreat news! Your order #{{order_number}} has been shipped.\n\nTracking Number: {{tracking_number}}\nExpected Delivery: {{expected_delivery}}\n\nYou can track your order using the link in your order confirmation email.\n\nThank you for shopping with YJS Jewellery!\n\nBest regards,\nYJS Jewellery Team",
                'category' => 'shipping',
                'variables' => json_encode(['customer_name', 'order_number', 'tracking_number', 'expected_delivery']),
                'is_active' => true,
                'usage_count' => 0,
            ],
            [
                'title' => 'Refund Processed',
                'shortcode' => 'refund_processed',
                'content' => "Dear {{customer_name}},\n\nWe have processed your refund for order #{{order_number}}.\n\nRefund Amount: Rs. {{refund_amount}}\nRefund Method: {{refund_method}}\nExpected Credit: 5-7 business days\n\nIf you have any questions, please don't hesitate to reach out.\n\nBest regards,\nYJS Jewellery Team",
                'category' => 'payment',
                'variables' => json_encode(['customer_name', 'order_number', 'refund_amount', 'refund_method']),
                'is_active' => true,
                'usage_count' => 0,
            ],
            [
                'title' => 'Return Approved',
                'shortcode' => 'return_approved',
                'content' => "Dear {{customer_name}},\n\nYour return request for order #{{order_number}} has been approved.\n\nReturn ID: {{return_id}}\nItems: {{return_items}}\n\nPlease ship the items within 7 days. Once we receive the items, your refund will be processed within 3-5 business days.\n\nBest regards,\nYJS Jewellery Team",
                'category' => 'return',
                'variables' => json_encode(['customer_name', 'order_number', 'return_id', 'return_items']),
                'is_active' => true,
                'usage_count' => 0,
            ],
            [
                'title' => 'Issue Resolved',
                'shortcode' => 'issue_resolved',
                'content' => "Dear {{customer_name}},\n\nWe're pleased to inform you that your support ticket #{{ticket_number}} has been resolved.\n\nIf you have any further questions or if the issue persists, please reply to this message.\n\nThank you for your patience!\n\nBest regards,\nYJS Jewellery Support Team",
                'category' => 'support',
                'variables' => json_encode(['customer_name', 'ticket_number']),
                'is_active' => true,
                'usage_count' => 0,
            ],
            [
                'title' => 'Delivery Delayed',
                'shortcode' => 'delivery_delayed',
                'content' => "Dear {{customer_name}},\n\nWe apologize for the delay in delivering your order #{{order_number}}.\n\nNew Expected Delivery: {{new_delivery_date}}\nReason: {{delay_reason}}\n\nWe understand this may cause inconvenience and sincerely apologize. As a token of our appreciation for your patience, we're offering you a {{discount_code}} for your next purchase.\n\nBest regards,\nYJS Jewellery Team",
                'category' => 'shipping',
                'variables' => json_encode(['customer_name', 'order_number', 'new_delivery_date', 'delay_reason', 'discount_code']),
                'is_active' => true,
                'usage_count' => 0,
            ],
            [
                'title' => 'Order Confirmation',
                'shortcode' => 'order_confirmation',
                'content' => "Dear {{customer_name}},\n\nThank you for your order! We've received your order #{{order_number}} and it's being processed.\n\nOrder Total: Rs. {{order_total}}\nPayment Method: {{payment_method}}\n\nYou'll receive another notification once your order has been shipped.\n\nBest regards,\nYJS Jewellery Team",
                'category' => 'order',
                'variables' => json_encode(['customer_name', 'order_number', 'order_total', 'payment_method']),
                'is_active' => true,
                'usage_count' => 0,
            ],
            [
                'title' => 'Payment Failed',
                'shortcode' => 'payment_failed',
                'content' => "Dear {{customer_name}},\n\nWe noticed that your payment for order #{{order_number}} could not be processed.\n\nAmount: Rs. {{order_total}}\nReason: {{failure_reason}}\n\nPlease try again using a different payment method or contact your bank for assistance.\n\nYour cart items have been saved and you can complete your purchase at any time.\n\nBest regards,\nYJS Jewellery Team",
                'category' => 'payment',
                'variables' => json_encode(['customer_name', 'order_number', 'order_total', 'failure_reason']),
                'is_active' => true,
                'usage_count' => 0,
            ],
            [
                'title' => 'Product Back in Stock',
                'shortcode' => 'back_in_stock',
                'content' => "Dear {{customer_name}},\n\nGreat news! The item you were waiting for is back in stock:\n\n{{product_name}}\nPrice: Rs. {{product_price}}\n\nHurry, limited stock available! Shop now before it's gone again.\n\nBest regards,\nYJS Jewellery Team",
                'category' => 'product',
                'variables' => json_encode(['customer_name', 'product_name', 'product_price']),
                'is_active' => true,
                'usage_count' => 0,
            ],
        ];

        foreach ($cannedResponses as $response) {
            DB::table('canned_responses')->updateOrInsert(
                ['shortcode' => $response['shortcode']],
                array_merge($response, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Create email templates
        $emailTemplates = [
            [
                'name' => 'Order Confirmation',
                'slug' => 'order-confirmation',
                'subject' => 'Order Confirmation - #{{order_number}}',
                'body_html' => '<h2>Thank you for your order!</h2><p>Dear {{customer_name}},</p><p>We have received your order <strong>#{{order_number}}</strong>.</p><p>Order Total: <strong>Rs. {{order_total}}</strong></p><p>We will notify you once your order is shipped.</p><p>Best regards,<br>YJS Jewellery Team</p>',
                'body_text' => "Thank you for your order!\n\nDear {{customer_name}},\n\nWe have received your order #{{order_number}}.\n\nOrder Total: Rs. {{order_total}}\n\nWe will notify you once your order is shipped.\n\nBest regards,\nYJS Jewellery Team",
                'category' => 'order',
                'variables' => json_encode(['customer_name', 'order_number', 'order_total', 'order_items']),
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Order Shipped',
                'slug' => 'order-shipped',
                'subject' => 'Your Order #{{order_number}} Has Been Shipped!',
                'body_html' => '<h2>Your order is on its way!</h2><p>Dear {{customer_name}},</p><p>Great news! Your order <strong>#{{order_number}}</strong> has been shipped.</p><p>Tracking Number: <strong>{{tracking_number}}</strong></p><p>Expected Delivery: {{expected_delivery}}</p><p><a href="{{tracking_link}}">Track Your Order</a></p><p>Best regards,<br>YJS Jewellery Team</p>',
                'body_text' => "Your order is on its way!\n\nDear {{customer_name}},\n\nGreat news! Your order #{{order_number}} has been shipped.\n\nTracking Number: {{tracking_number}}\nExpected Delivery: {{expected_delivery}}\n\nTrack your order: {{tracking_link}}\n\nBest regards,\nYJS Jewellery Team",
                'category' => 'shipping',
                'variables' => json_encode(['customer_name', 'order_number', 'tracking_number', 'expected_delivery', 'tracking_link']),
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Order Delivered',
                'slug' => 'order-delivered',
                'subject' => 'Your Order #{{order_number}} Has Been Delivered!',
                'body_html' => '<h2>Your order has arrived!</h2><p>Dear {{customer_name}},</p><p>Your order <strong>#{{order_number}}</strong> has been delivered.</p><p>We hope you love your new jewellery! Please take a moment to rate your experience.</p><p><a href="{{review_link}}">Leave a Review</a></p><p>Best regards,<br>YJS Jewellery Team</p>',
                'body_text' => "Your order has arrived!\n\nDear {{customer_name}},\n\nYour order #{{order_number}} has been delivered.\n\nWe hope you love your new jewellery! Please take a moment to rate your experience.\n\nLeave a review: {{review_link}}\n\nBest regards,\nYJS Jewellery Team",
                'category' => 'shipping',
                'variables' => json_encode(['customer_name', 'order_number', 'review_link']),
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Refund Processed',
                'slug' => 'refund-processed',
                'subject' => 'Refund Processed for Order #{{order_number}}',
                'body_html' => '<h2>Your refund has been processed</h2><p>Dear {{customer_name}},</p><p>We have processed your refund for order <strong>#{{order_number}}</strong>.</p><p>Refund Amount: <strong>Rs. {{refund_amount}}</strong></p><p>The amount will be credited to your account within 5-7 business days.</p><p>Best regards,<br>YJS Jewellery Team</p>',
                'body_text' => "Your refund has been processed\n\nDear {{customer_name}},\n\nWe have processed your refund for order #{{order_number}}.\n\nRefund Amount: Rs. {{refund_amount}}\n\nThe amount will be credited to your account within 5-7 business days.\n\nBest regards,\nYJS Jewellery Team",
                'category' => 'payment',
                'variables' => json_encode(['customer_name', 'order_number', 'refund_amount']),
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Support Ticket Created',
                'slug' => 'ticket-created',
                'subject' => 'Support Ticket #{{ticket_number}} Created',
                'body_html' => '<h2>We received your support request</h2><p>Dear {{customer_name}},</p><p>Your support ticket <strong>#{{ticket_number}}</strong> has been created.</p><p>Subject: {{ticket_subject}}</p><p>Our team will get back to you within 24 hours.</p><p>Best regards,<br>YJS Jewellery Support Team</p>',
                'body_text' => "We received your support request\n\nDear {{customer_name}},\n\nYour support ticket #{{ticket_number}} has been created.\n\nSubject: {{ticket_subject}}\n\nOur team will get back to you within 24 hours.\n\nBest regards,\nYJS Jewellery Support Team",
                'category' => 'support',
                'variables' => json_encode(['customer_name', 'ticket_number', 'ticket_subject']),
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Welcome Email',
                'slug' => 'welcome-email',
                'subject' => 'Welcome to YJS Jewellery!',
                'body_html' => '<h2>Welcome to YJS Jewellery!</h2><p>Dear {{customer_name}},</p><p>Thank you for creating an account with us. We are delighted to have you as part of our family.</p><p>As a welcome gift, use code <strong>{{welcome_code}}</strong> for 10% off your first order!</p><p><a href="{{shop_link}}">Start Shopping</a></p><p>Best regards,<br>YJS Jewellery Team</p>',
                'body_text' => "Welcome to YJS Jewellery!\n\nDear {{customer_name}},\n\nThank you for creating an account with us. We are delighted to have you as part of our family.\n\nAs a welcome gift, use code {{welcome_code}} for 10% off your first order!\n\nStart Shopping: {{shop_link}}\n\nBest regards,\nYJS Jewellery Team",
                'category' => 'notification',
                'variables' => json_encode(['customer_name', 'welcome_code', 'shop_link']),
                'is_active' => true,
                'is_system' => true,
            ],
        ];

        foreach ($emailTemplates as $template) {
            DB::table('email_templates')->updateOrInsert(
                ['slug' => $template['slug']],
                array_merge($template, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
