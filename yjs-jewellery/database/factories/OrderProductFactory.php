<?php

namespace Database\Factories;

use App\Models\orderProduct;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\orderProduct>
 */
class OrderProductFactory extends Factory
{
    protected $model = orderProduct::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 1000, 50000);
        $quantity = fake()->numberBetween(1, 3);
        $discount = fake()->randomFloat(2, 0, $price * 0.1);
        $tax = ($price - $discount) * 0.18; // 18% GST
        $total = ($price - $discount + $tax) * $quantity;

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'price' => $price,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    /**
     * Create an order product for a specific order.
     */
    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
        ]);
    }

    /**
     * Create an order product for a specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }
}
