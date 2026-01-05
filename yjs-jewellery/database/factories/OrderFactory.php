<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\CustomerAddress;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'custom_order_code' => 'YJS-' . strtoupper(fake()->unique()->bothify('??####')),
            'order_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'customer_type' => 'existing',
            'customer_id' => User::factory()->customer(),
            'email' => fake()->safeEmail(),
            'country_code' => '+91',
            'country_id' => Country::factory(),
            'shipping_method' => 'standard',
            'shipping_charges' => fake()->randomFloat(2, 0, 500),
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'razorpay',
            'order_subtotal' => fake()->randomFloat(2, 1000, 50000),
            'total_taxes' => fake()->randomFloat(2, 50, 2000),
            'total_charges' => fake()->randomFloat(2, 0, 500),
            'order_total' => fake()->randomFloat(2, 1000, 55000),
        ];
    }

    /**
     * Indicate that the order is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'confirmed',
        ]);
    }

    /**
     * Indicate that the order is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'processing',
        ]);
    }

    /**
     * Indicate that the order is shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'shipped',
            'awb_number' => fake()->numerify('##########'),
            'courier_name' => fake()->randomElement(['Bluedart', 'DTDC', 'Delhivery']),
        ]);
    }

    /**
     * Indicate that the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'delivered',
            'delivery_date' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the order is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Create an order for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
