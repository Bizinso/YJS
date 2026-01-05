<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Order Payment Factory
 *
 * Factory for creating order payment test data.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderPayment>
 */
class OrderPaymentFactory extends Factory
{
    protected $model = OrderPayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_mode' => fake()->randomElement(['upi', 'card', 'netbanking', 'cod']),
            'transaction_id' => 'txn_' . fake()->unique()->uuid(),
            'razorpay_order_id' => 'order_' . fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'razorpay_payment_id' => 'pay_' . fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'status' => 'pending',
        ];
    }

    /**
     * Set payment as successful.
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
        ]);
    }

    /**
     * Set payment as failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * Create payment for specific order.
     */
    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
            'amount' => $order->order_total,
        ]);
    }
}
