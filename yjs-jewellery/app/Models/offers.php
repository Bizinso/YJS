<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class offers extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'offers';

    protected $fillable = [
        'title',
        'description',
        'offer_type_id',
        'offer_type_option',
        'offer_category',
        'discount_type',
        'discount_amount',
        'discount_percent',
        'max_discount_amount',
        'buy_quantity',
        'get_quantity',
        'get_discount_percent',
        'get_products',
        'combo_products',
        'combo_price',
        'payment_methods',
        'card_networks',
        'banks',
        'is_flash_sale',
        'flash_stock_limit',
        'flash_sold_count',
        'free_gift_products',
        'free_gift_quantity',
        'tier_rules',
        'target_user_ids',
        'target_user_tags',
        'is_birthday_offer',
        'birthday_days_before',
        'birthday_days_after',
        'is_referral_offer',
        'referrer_reward',
        'referee_discount',
        'is_stackable',
        'stackable_with',
        'exclusive_with',
        'stack_priority',
        'nth_item_number',
        'nth_item_discount_percent',
        'badge_text',
        'badge_color',
        'banner_image',
        'terms_conditions',
        'details',
        'apply_on',
        'apply_on_value',
        'valid_from',
        'valid_to',
        'status',
        'coupon_code',
        'created_by',
    ];

    protected $casts = [
        'details' => 'array',
        'apply_on_value' => 'array',
        'get_products' => 'array',
        'combo_products' => 'array',
        'payment_methods' => 'array',
        'card_networks' => 'array',
        'banks' => 'array',
        'free_gift_products' => 'array',
        'tier_rules' => 'array',
        'target_user_ids' => 'array',
        'target_user_tags' => 'array',
        'stackable_with' => 'array',
        'exclusive_with' => 'array',
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'combo_price' => 'decimal:2',
        'get_discount_percent' => 'decimal:2',
        'referrer_reward' => 'decimal:2',
        'referee_discount' => 'decimal:2',
        'nth_item_discount_percent' => 'decimal:2',
        'is_flash_sale' => 'boolean',
        'is_birthday_offer' => 'boolean',
        'is_referral_offer' => 'boolean',
        'is_stackable' => 'boolean',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('offers')
            ->logAll()                       // log all fields
            ->logOnlyDirty()                 // log only changed fields
            ->dontSubmitEmptyLogs()          // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Offer has been {$event}"
            );
    }

    /**
     * Get offer usages.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(OfferUsage::class, 'offer_id');
    }

    /**
     * Get offer type master.
     */
    public function offerType(): BelongsTo
    {
        return $this->belongsTo(offerTypeMaster::class, 'offer_type_id');
    }

    /**
     * Get flash sale inventory.
     */
    public function flashSaleInventory(): HasMany
    {
        return $this->hasMany(FlashSaleInventory::class, 'offer_id');
    }

    /**
     * Get offer bundles.
     */
    public function bundles(): HasMany
    {
        return $this->hasMany(OfferBundle::class, 'offer_id');
    }

    /**
     * Scope for active offers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_to')
                  ->orWhere('valid_to', '>=', now());
            });
    }

    /**
     * Scope for flash sales.
     */
    public function scopeFlashSales($query)
    {
        return $query->where('is_flash_sale', true);
    }

    /**
     * Scope for birthday offers.
     */
    public function scopeBirthdayOffers($query)
    {
        return $query->where('is_birthday_offer', true);
    }

    /**
     * Scope for stackable offers.
     */
    public function scopeStackable($query)
    {
        return $query->where('is_stackable', true);
    }

    /**
     * Scope by offer category.
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('offer_category', $category);
    }

    /**
     * Check if offer is currently valid.
     */
    public function getIsValidAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->valid_from && $this->valid_from > now()) {
            return false;
        }

        if ($this->valid_to && $this->valid_to < now()) {
            return false;
        }

        return true;
    }

    /**
     * Check if offer is a BOGO type.
     */
    public function getIsBogoAttribute(): bool
    {
        return $this->buy_quantity && $this->get_quantity;
    }

    /**
     * Check if offer is a combo/bundle type.
     */
    public function getIsComboAttribute(): bool
    {
        return !empty($this->combo_products);
    }

    /**
     * Check if offer has payment method restriction.
     */
    public function getHasPaymentRestrictionAttribute(): bool
    {
        return !empty($this->payment_methods) || !empty($this->card_networks) || !empty($this->banks);
    }

    /**
     * Check if offer provides free gift.
     */
    public function getHasFreeGiftAttribute(): bool
    {
        return !empty($this->free_gift_products);
    }

    /**
     * Get remaining flash sale stock.
     */
    public function getRemainingFlashStockAttribute(): ?int
    {
        if (!$this->is_flash_sale || !$this->flash_stock_limit) {
            return null;
        }
        return max(0, $this->flash_stock_limit - ($this->flash_sold_count ?? 0));
    }

    /**
     * Increment flash sale sold count.
     */
    public function incrementFlashSold(int $quantity = 1): void
    {
        $this->increment('flash_sold_count', $quantity);
        $this->increment('usage_count');
    }

    /**
     * Increment view count.
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    /**
     * Add to total discount given.
     */
    public function addDiscountGiven(float $amount): void
    {
        $this->increment('total_discount_given', $amount);
    }
}
