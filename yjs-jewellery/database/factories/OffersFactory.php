<?php

namespace Database\Factories;

use App\Models\offers;
use App\Models\offerTypeMaster;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Offers Factory
 *
 * Factory for creating offer test data.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\offers>
 */
class OffersFactory extends Factory
{
    protected $model = offers::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $discountType = fake()->randomElement(['flat', 'percent']);

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'offer_type_id' => offerTypeMaster::factory(),
            'discount_type' => $discountType,
            'discount_amount' => $discountType === 'flat' ? fake()->randomFloat(2, 100, 1000) : null,
            'discount_percent' => $discountType === 'percent' ? fake()->randomFloat(2, 5, 50) : null,
            'max_discount_amount' => $discountType === 'percent' ? fake()->randomFloat(2, 500, 2000) : null,
            'apply_on' => 'all',
            'apply_on_value' => null,
            'valid_from' => now()->subDays(5),
            'valid_to' => now()->addDays(30),
            'status' => 'active',
            'coupon_code' => null,
            'details' => [
                'min_cart_value' => null,
                'first_order_only' => false,
                'max_usage_global' => null,
                'max_usage_per_user' => null,
            ],
            'created_by' => null,
        ];
    }

    /**
     * Set offer as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Set offer as expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'valid_from' => now()->subDays(60),
            'valid_to' => now()->subDays(30),
        ]);
    }

    /**
     * Create a flat discount offer.
     */
    public function flatDiscount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'flat',
            'discount_amount' => $amount,
            'discount_percent' => null,
            'max_discount_amount' => null,
        ]);
    }

    /**
     * Create a percentage discount offer.
     */
    public function percentDiscount(float $percent, ?float $maxDiscount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'percent',
            'discount_percent' => $percent,
            'discount_amount' => null,
            'max_discount_amount' => $maxDiscount,
        ]);
    }

    /**
     * Create offer with coupon code.
     */
    public function withCoupon(string $code = null): static
    {
        return $this->state(fn (array $attributes) => [
            'coupon_code' => $code ?? strtoupper(Str::random(8)),
        ]);
    }

    /**
     * Set minimum cart value.
     */
    public function minCartValue(float $value): static
    {
        return $this->state(function (array $attributes) use ($value) {
            $details = $attributes['details'] ?? [];
            $details['min_cart_value'] = $value;
            return ['details' => $details];
        });
    }

    /**
     * Set first order only.
     */
    public function firstOrderOnly(): static
    {
        return $this->state(function (array $attributes) {
            $details = $attributes['details'] ?? [];
            $details['first_order_only'] = true;
            return ['details' => $details];
        });
    }

    /**
     * Set max usage limits.
     */
    public function withUsageLimits(?int $global = null, ?int $perUser = null): static
    {
        return $this->state(function (array $attributes) use ($global, $perUser) {
            $details = $attributes['details'] ?? [];
            $details['max_usage_global'] = $global;
            $details['max_usage_per_user'] = $perUser;
            return ['details' => $details];
        });
    }

    /**
     * Apply to specific products.
     */
    public function forProducts(array $productIds): static
    {
        return $this->state(fn (array $attributes) => [
            'apply_on' => 'products',
            'apply_on_value' => $productIds,
        ]);
    }

    /**
     * Apply to specific categories.
     */
    public function forCategories(array $categoryIds): static
    {
        return $this->state(fn (array $attributes) => [
            'apply_on' => 'categories',
            'apply_on_value' => $categoryIds,
        ]);
    }

    /**
     * Set custom validity dates.
     */
    public function validBetween($from, $to): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => $from,
            'valid_to' => $to,
        ]);
    }
}
