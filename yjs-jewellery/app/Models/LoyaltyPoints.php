<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Loyalty Points Model
 *
 * Tracks user loyalty points balance and tier.
 */
class LoyaltyPoints extends Model
{
    use HasFactory;

    protected $table = 'loyalty_points';

    protected $fillable = [
        'user_id',
        'points_balance',
        'lifetime_points',
        'redeemed_points',
        'tier',
        'tier_updated_at',
    ];

    protected $casts = [
        'tier_updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyPointTransaction::class, 'user_id', 'user_id');
    }

    public function tierConfig(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTier::class, 'tier', 'slug');
    }

    /**
     * Add points to balance
     */
    public function addPoints(int $points, string $type, string $description, ?int $orderId = null, ?string $referenceType = null, ?int $referenceId = null): LoyaltyPointTransaction
    {
        $this->points_balance += $points;
        $this->lifetime_points += $points;
        $this->save();

        $transaction = LoyaltyPointTransaction::create([
            'user_id' => $this->user_id,
            'order_id' => $orderId,
            'type' => $type,
            'points' => $points,
            'balance_after' => $this->points_balance,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'expires_at' => now()->addYear(), // Points expire in 1 year
        ]);

        $this->updateTier();

        return $transaction;
    }

    /**
     * Redeem points
     */
    public function redeemPoints(int $points, string $description, ?int $orderId = null): ?LoyaltyPointTransaction
    {
        if ($points > $this->points_balance) {
            return null;
        }

        $this->points_balance -= $points;
        $this->redeemed_points += $points;
        $this->save();

        return LoyaltyPointTransaction::create([
            'user_id' => $this->user_id,
            'order_id' => $orderId,
            'type' => 'redeem',
            'points' => -$points,
            'balance_after' => $this->points_balance,
            'description' => $description,
            'reference_type' => 'order',
            'reference_id' => $orderId,
        ]);
    }

    /**
     * Update tier based on lifetime points
     */
    public function updateTier(): void
    {
        $newTier = LoyaltyTier::where('is_active', true)
            ->where('min_points', '<=', $this->lifetime_points)
            ->where(function ($q) {
                $q->whereNull('max_points')
                  ->orWhere('max_points', '>=', $this->lifetime_points);
            })
            ->orderBy('min_points', 'desc')
            ->first();

        if ($newTier && $newTier->slug !== $this->tier) {
            $this->tier = $newTier->slug;
            $this->tier_updated_at = now();
            $this->save();
        }
    }

    /**
     * Get points value in rupees
     */
    public function getPointsValueAttribute(): float
    {
        $tier = $this->tierConfig;
        $conversionRate = 100; // Default: 100 points = Rs.1

        if ($tier) {
            $benefits = $tier->benefits ?? [];
            // Parse redemption rate from benefits if available
        }

        return $this->points_balance / $conversionRate;
    }

    /**
     * Get or create loyalty record for user
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'points_balance' => 0,
                'lifetime_points' => 0,
                'redeemed_points' => 0,
                'tier' => 'bronze',
            ]
        );
    }
}
