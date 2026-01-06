<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' Warehouse',
            'code' => 'WH-' . strtoupper($this->faker->unique()->bothify('???-###')),
            'description' => $this->faker->sentence,
            'type' => $this->faker->randomElement(['warehouse', 'store', 'fulfillment_center']),
            'address_line1' => $this->faker->streetAddress,
            'address_line2' => $this->faker->optional()->secondaryAddress,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'pincode' => $this->faker->numerify('######'),
            'country' => 'IN',
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->companyEmail,
            'is_active' => true,
            'is_default' => false,
            'accepts_returns' => true,
            'allows_pickup' => false,
            'priority' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Indicate that the warehouse is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the warehouse is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
