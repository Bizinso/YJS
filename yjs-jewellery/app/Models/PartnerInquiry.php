<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Partner Inquiry Model
 *
 * Represents a B2B bulk order inquiry from partners.
 * Partners browse products without pricing, create inquiries,
 * and admin processes them offline with custom quotes.
 */
class PartnerInquiry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inquiry_code',
        'partner_id',
        'user_id',
        'status',
        'notes',
        'admin_notes',
        'rejection_reason',
        'quoted_amount',
        'discount_amount',
        'shipping_charges',
        'tax_amount',
        'final_amount',
        'quote_valid_until',
        'quoted_at',
        'accepted_at',
        'rejected_at',
        'partner_response_notes',
        'shipping_address_id',
        'expected_delivery_date',
        'actual_delivery_date',
        'delivery_method',
        'tracking_number',
        'courier_name',
        'tracking_history',
        'payment_status',
        'payment_method',
        'payment_reference',
        'amount_paid',
        'payment_date',
        'handled_by',
        'handled_at',
        'priority',
    ];

    protected $casts = [
        'quoted_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_charges' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'quote_valid_until' => 'datetime',
        'quoted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'payment_date' => 'datetime',
        'handled_at' => 'datetime',
        'tracking_history' => 'array',
    ];

    /**
     * Status labels for display
     */
    public const STATUS_LABELS = [
        'pending' => 'Pending Review',
        'under_review' => 'Under Review',
        'quoted' => 'Quote Sent',
        'accepted' => 'Quote Accepted',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
    ];

    /**
     * Priority labels
     */
    public const PRIORITY_LABELS = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    /**
     * Boot method - auto-generate inquiry code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($inquiry) {
            if (empty($inquiry->inquiry_code)) {
                $inquiry->inquiry_code = self::generateInquiryCode();
            }
        });
    }

    /**
     * Generate unique inquiry code
     */
    public static function generateInquiryCode(): string
    {
        $prefix = 'INQ';
        $date = now()->format('ymd');
        $random = strtoupper(substr(uniqid(), -4));
        $code = "{$prefix}{$date}{$random}";

        // Ensure uniqueness
        while (self::where('inquiry_code', $code)->exists()) {
            $random = strtoupper(substr(uniqid(), -4));
            $code = "{$prefix}{$date}{$random}";
        }

        return $code;
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Partner who created the inquiry
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * User (partner's user account)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Inquiry items (products)
     */
    public function items(): HasMany
    {
        return $this->hasMany(PartnerInquiryItem::class, 'inquiry_id');
    }

    /**
     * Status history
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(PartnerInquiryStatusHistory::class, 'inquiry_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Messages/Communications
     */
    public function messages(): HasMany
    {
        return $this->hasMany(PartnerInquiryMessage::class, 'inquiry_id')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Shipping address
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(customerAddress::class, 'shipping_address_id');
    }

    /**
     * Admin who handled the inquiry
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get priority label
     */
    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? ucfirst($this->priority);
    }

    /**
     * Get total items count
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Get total products count
     */
    public function getTotalProductsAttribute(): int
    {
        return $this->items->count();
    }

    /**
     * Check if quote is still valid
     */
    public function getIsQuoteValidAttribute(): bool
    {
        if (!$this->quote_valid_until) {
            return false;
        }
        return now()->lt($this->quote_valid_until);
    }

    /**
     * Get balance amount
     */
    public function getBalanceAmountAttribute(): float
    {
        return ($this->final_amount ?? 0) - ($this->amount_paid ?? 0);
    }

    // ==================== SCOPES ====================

    /**
     * Scope for pending inquiries
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for active inquiries (not completed/cancelled/rejected)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled', 'rejected']);
    }

    /**
     * Scope for inquiries needing action
     */
    public function scopeNeedsAction($query)
    {
        return $query->whereIn('status', ['pending', 'under_review', 'accepted']);
    }

    /**
     * Scope by partner
     */
    public function scopeForPartner($query, $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    /**
     * Scope by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    // ==================== METHODS ====================

    /**
     * Update status with history tracking
     */
    public function updateStatus(string $newStatus, ?string $notes = null, ?int $userId = null): bool
    {
        $oldStatus = $this->status;

        if ($oldStatus === $newStatus) {
            return false;
        }

        $this->status = $newStatus;

        // Set timestamps based on status
        switch ($newStatus) {
            case 'quoted':
                $this->quoted_at = now();
                break;
            case 'accepted':
                $this->accepted_at = now();
                break;
            case 'rejected':
            case 'cancelled':
                $this->rejected_at = now();
                break;
        }

        $this->save();

        // Record status history
        $this->statusHistory()->create([
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $notes,
            'changed_by' => $userId,
        ]);

        return true;
    }

    /**
     * Add tracking update
     */
    public function addTrackingUpdate(string $status, ?string $location = null, ?string $notes = null): void
    {
        $history = $this->tracking_history ?? [];
        $history[] = [
            'status' => $status,
            'location' => $location,
            'notes' => $notes,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->tracking_history = $history;
        $this->save();
    }

    /**
     * Record payment
     */
    public function recordPayment(float $amount, string $method, ?string $reference = null): void
    {
        $this->amount_paid += $amount;
        $this->payment_method = $method;
        $this->payment_reference = $reference;
        $this->payment_date = now();

        if ($this->amount_paid >= $this->final_amount) {
            $this->payment_status = 'paid';
        } elseif ($this->amount_paid > 0) {
            $this->payment_status = 'partial';
        }

        $this->save();
    }

    /**
     * Check if inquiry can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'under_review', 'quoted']);
    }

    /**
     * Check if inquiry can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['pending', 'under_review']);
    }

    /**
     * Check if quote can be accepted
     */
    public function canAcceptQuote(): bool
    {
        return $this->status === 'quoted' && $this->is_quote_valid;
    }
}
