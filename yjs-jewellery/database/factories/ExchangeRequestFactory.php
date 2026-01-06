<?php

namespace Database\Factories;

use App\Models\ExchangeRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExchangeRequestFactory extends Factory
{
    protected $model = ExchangeRequest::class;

    public function definition(): array
    {
        return [
            'exchange_code' => 'EXC-' . strtoupper(date('Ymd')) . '-' . strtoupper($this->faker->unique()->bothify('??????')),
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'status' => 'pending',
            'reason_code' => $this->faker->randomElement(['size_exchange', 'color_exchange', 'defective']),
            'reason_description' => $this->faker->sentence(),
            'customer_notes' => $this->faker->optional()->sentence(),
            'original_amount' => $this->faker->randomFloat(2, 1000, 10000),
            'new_amount' => $this->faker->randomFloat(2, 1000, 10000),
            'price_difference' => 0,
            'adjustment_type' => 'none',
            'adjustment_paid' => false,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);
    }
}
