<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Loyalty Tier Model
 *
 * Defines loyalty program tiers and their benefits.
 */
class LoyaltyTier extends Model
{
    protected $table = 'loyalty_tiers';

    protected $fillable = [
        'name',
        'slug',
        'min_points',
        'max_points',
        'points_multiplier',
        'benefits',
        'badge_icon',
        'badge_color',
        'order',
        'is_active',
    ];

    protected $casts = [
        'benefits' => 'array',
        'points_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get all active tiers ordered by points
     */
    public static function getActiveTiers()
    {
        return self::where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Get tier by points
     */
    public static function getTierByPoints(int $points): ?self
    {
        return self::where('is_active', true)
            ->where('min_points', '<=', $points)
            ->where(function ($q) use ($points) {
                $q->whereNull('max_points')
                  ->orWhere('max_points', '>=', $points);
            })
            ->orderBy('min_points', 'desc')
            ->first();
    }

    /**
     * Get next tier
     */
    public function getNextTier(): ?self
    {
        return self::where('is_active', true)
            ->where('min_points', '>', $this->max_points ?? $this->min_points)
            ->orderBy('min_points')
            ->first();
    }

    /**
     * Get benefit value
     */
    public function getBenefit(string $key, $default = null)
    {
        return $this->benefits[$key] ?? $default;
    }
}
