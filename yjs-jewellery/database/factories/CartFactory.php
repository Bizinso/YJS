<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Cart Factory
 *
 * Factory for creating cart test data.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $basePrice = fake()->randomFloat(2, 1000, 50000);
        $charges = fake()->randomFloat(2, 100, 500);
        $taxes = fake()->randomFloat(2, 50, 300);
        $quantity = fake()->numberBetween(1, 5);
        $finalPrice = $basePrice + $charges + $taxes;

        return [
            'user_id' => User::factory()->customer(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'product_base_price' => $basePrice,
            'charges_total' => $charges,
            'tax_total' => $taxes,
            'total_discount' => 0,
            'final_price' => $finalPrice,
            'cart_total' => $finalPrice * $quantity,
        ];
    }

    /**
     * Set cart for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set cart for a specific product.
     */
    public function forProduct(Product $product): static
    {
        $charges = fake()->randomFloat(2, 100, 500);
        $taxes = fake()->randomFloat(2, 50, 300);
        $finalPrice = $product->base_price + $charges + $taxes;

        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'product_base_price' => $product->base_price,
            'charges_total' => $charges,
            'tax_total' => $taxes,
            'final_price' => $finalPrice,
            'cart_total' => $finalPrice * ($attributes['quantity'] ?? 1),
        ]);
    }

    /**
     * Set specific quantity.
     */
    public function withQuantity(int $quantity): static
    {
        return $this->state(function (array $attributes) use ($quantity) {
            $finalPrice = $attributes['final_price'] ?? 1000;
            return [
                'quantity' => $quantity,
                'cart_total' => $finalPrice * $quantity,
            ];
        });
    }
}
