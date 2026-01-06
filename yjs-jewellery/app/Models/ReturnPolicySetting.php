<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Return Policy Settings Model
 *
 * Manages configurable return, exchange, and cancellation policies.
 *
 * @property int $id
 * @property string $policy_type
 * @property int $return_window_days
 * @property int $exchange_window_days
 * @property int $cancellation_window_hours
 * @property bool $allow_partial_returns
 * @property bool $require_images
 * @property bool $require_reason
 * @property bool $auto_approve_cancellations
 * @property float $restocking_fee_percent
 * @property array|null $non_returnable_categories
 * @property array|null $return_reasons
 * @property array|null $exchange_reasons
 * @property array|null $cancellation_reasons
 * @property string|null $return_instructions
 * @property string|null $terms_and_conditions
 * @property bool $is_active
 */
class ReturnPolicySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_type',
        'return_window_days',
        'exchange_window_days',
        'cancellation_window_hours',
        'allow_partial_returns',
        'require_images',
        'require_reason',
        'auto_approve_cancellations',
        'restocking_fee_percent',
        'non_returnable_categories',
        'return_reasons',
        'exchange_reasons',
        'cancellation_reasons',
        'return_instructions',
        'terms_and_conditions',
        'is_active',
    ];

    protected $casts = [
        'allow_partial_returns' => 'boolean',
        'require_images' => 'boolean',
        'require_reason' => 'boolean',
        'auto_approve_cancellations' => 'boolean',
        'restocking_fee_percent' => 'decimal:2',
        'non_returnable_categories' => 'array',
        'return_reasons' => 'array',
        'exchange_reasons' => 'array',
        'cancellation_reasons' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the active policy settings
     */
    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Get default return reasons
     */
    public static function getDefaultReturnReasons(): array
    {
        return [
            'defective' => 'Product is defective',
            'wrong_item' => 'Wrong item received',
            'not_as_described' => 'Product not as described',
            'quality_issue' => 'Quality not satisfactory',
            'size_issue' => 'Size/fit issue',
            'changed_mind' => 'Changed my mind',
            'better_price' => 'Found better price elsewhere',
            'other' => 'Other reason',
        ];
    }

    /**
     * Get default exchange reasons
     */
    public static function getDefaultExchangeReasons(): array
    {
        return [
            'size_exchange' => 'Need different size',
            'color_exchange' => 'Need different color/variant',
            'style_exchange' => 'Want different style',
            'defective' => 'Product is defective',
            'wrong_item' => 'Wrong item received',
            'other' => 'Other reason',
        ];
    }

    /**
     * Get default cancellation reasons
     */
    public static function getDefaultCancellationReasons(): array
    {
        return [
            'changed_mind' => 'Changed my mind',
            'ordered_by_mistake' => 'Ordered by mistake',
            'duplicate_order' => 'Duplicate order',
            'delivery_too_long' => 'Delivery time too long',
            'found_better_price' => 'Found better price',
            'payment_issue' => 'Payment issue',
            'other' => 'Other reason',
        ];
    }

    /**
     * Check if a category is returnable
     */
    public function isCategoryReturnable(int $categoryId): bool
    {
        if (empty($this->non_returnable_categories)) {
            return true;
        }

        return !in_array($categoryId, $this->non_returnable_categories);
    }

    /**
     * Check if order is within return window
     */
    public function isWithinReturnWindow(\DateTime $deliveryDate): bool
    {
        $windowEnd = (clone $deliveryDate)->modify("+{$this->return_window_days} days");
        return now() <= $windowEnd;
    }

    /**
     * Check if order is within exchange window
     */
    public function isWithinExchangeWindow(\DateTime $deliveryDate): bool
    {
        $windowEnd = (clone $deliveryDate)->modify("+{$this->exchange_window_days} days");
        return now() <= $windowEnd;
    }

    /**
     * Check if order is within cancellation window
     */
    public function isWithinCancellationWindow(\DateTime $orderDate): bool
    {
        $windowEnd = (clone $orderDate)->modify("+{$this->cancellation_window_hours} hours");
        return now() <= $windowEnd;
    }

    /**
     * Calculate restocking fee
     */
    public function calculateRestockingFee(float $amount): float
    {
        return round($amount * ($this->restocking_fee_percent / 100), 2);
    }
}
