<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Email Template Model
 */
class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'subject',
        'body_html',
        'body_text',
        'category',
        'variables',
        'is_active',
        'is_system',
        'updated_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    // Categories
    const CATEGORY_ORDER = 'order';
    const CATEGORY_PAYMENT = 'payment';
    const CATEGORY_SHIPPING = 'shipping';
    const CATEGORY_SUPPORT = 'support';
    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_NOTIFICATION = 'notification';

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Render the template with data.
     */
    public function render(array $data = [], bool $html = true): string
    {
        $content = $html ? $this->body_html : ($this->body_text ?? strip_tags($this->body_html));

        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $content = str_replace('{{' . $key . '}}', $value, $content);
                $content = str_replace('{' . $key . '}', $value, $content);
            }
        }

        return $content;
    }

    /**
     * Render the subject with data.
     */
    public function renderSubject(array $data = []): string
    {
        $subject = $this->subject;

        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $subject = str_replace('{{' . $key . '}}', $value, $subject);
                $subject = str_replace('{' . $key . '}', $value, $subject);
            }
        }

        return $subject;
    }

    /**
     * Get default templates.
     */
    public static function getDefaults(): array
    {
        return [
            [
                'name' => 'Order Confirmation',
                'slug' => 'order_confirmation',
                'subject' => 'Order Confirmed - #{order_number}',
                'category' => self::CATEGORY_ORDER,
                'variables' => ['customer_name', 'order_number', 'order_total', 'order_items', 'order_date'],
                'is_system' => true,
            ],
            [
                'name' => 'Order Shipped',
                'slug' => 'order_shipped',
                'subject' => 'Your Order #{order_number} Has Been Shipped',
                'category' => self::CATEGORY_SHIPPING,
                'variables' => ['customer_name', 'order_number', 'tracking_number', 'courier_name', 'estimated_delivery'],
                'is_system' => true,
            ],
            [
                'name' => 'Payment Received',
                'slug' => 'payment_received',
                'subject' => 'Payment Received - Order #{order_number}',
                'category' => self::CATEGORY_PAYMENT,
                'variables' => ['customer_name', 'order_number', 'amount', 'payment_method'],
                'is_system' => true,
            ],
            [
                'name' => 'Ticket Created',
                'slug' => 'ticket_created',
                'subject' => 'Support Ticket #{ticket_number} Created',
                'category' => self::CATEGORY_SUPPORT,
                'variables' => ['customer_name', 'ticket_number', 'subject', 'status'],
                'is_system' => true,
            ],
            [
                'name' => 'Ticket Response',
                'slug' => 'ticket_response',
                'subject' => 'Re: Support Ticket #{ticket_number}',
                'category' => self::CATEGORY_SUPPORT,
                'variables' => ['customer_name', 'ticket_number', 'message', 'agent_name'],
                'is_system' => true,
            ],
        ];
    }

    /**
     * Get template by slug.
     */
    public static function getBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->where('is_active', true)->first();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
