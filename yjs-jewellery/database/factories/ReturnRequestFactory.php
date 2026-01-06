<?php

namespace Database\Factories;

use App\Models\ReturnRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnRequestFactory extends Factory
{
    protected $model = ReturnRequest::class;

    public function definition(): array
    {
        return [
            'return_code' => 'RET-' . strtoupper(date('Ymd')) . '-' . strtoupper($this->faker->unique()->bothify('??????')),
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'status' => 'pending',
            'return_type' => $this->faker->randomElement(['refund', 'store_credit']),
            'reason_code' => $this->faker->randomElement(['defective', 'wrong_item', 'not_as_described', 'changed_mind']),
            'reason_description' => $this->faker->sentence(),
            'customer_notes' => $this->faker->optional()->sentence(),
            'refund_amount' => $this->faker->randomFloat(2, 100, 5000),
            'restocking_fee' => 0,
            'shipping_deduction' => 0,
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

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewed_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }
}
