<?php

namespace Database\Factories;

use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CancellationRequestFactory extends Factory
{
    protected $model = CancellationRequest::class;

    public function definition(): array
    {
        return [
            'cancellation_code' => 'CAN-' . strtoupper(date('Ymd')) . '-' . strtoupper($this->faker->unique()->bothify('??????')),
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'status' => 'pending',
            'cancellation_type' => 'full',
            'reason_code' => $this->faker->randomElement(['changed_mind', 'ordered_by_mistake', 'found_better_price']),
            'reason_description' => $this->faker->sentence(),
            'customer_notes' => $this->faker->optional()->sentence(),
            'order_amount' => $this->faker->randomFloat(2, 1000, 10000),
            'cancellation_fee' => 0,
            'refund_amount' => $this->faker->randomFloat(2, 1000, 10000),
            'auto_approved' => false,
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

    public function autoApproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'auto_approved' => true,
            'reviewed_at' => now(),
        ]);
    }
}
