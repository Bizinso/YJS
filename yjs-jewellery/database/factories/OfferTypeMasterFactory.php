<?php

namespace Database\Factories;

use App\Models\offerTypeMaster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * OfferTypeMaster Factory
 *
 * Factory for creating offer type master test data.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\offerTypeMaster>
 */
class OfferTypeMasterFactory extends Factory
{
    protected $model = offerTypeMaster::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'offer_type' => fake()->randomElement(['discount', 'coupon', 'promo']),
            'offer_type_option' => ['flat', 'percent'],
            'description' => fake()->sentence(),
            'apply_to' => 'all',
            'apply_to_option' => null,
            'status' => 'A',
        ];
    }
}
