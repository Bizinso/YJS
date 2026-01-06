<?php

namespace Database\Factories;

use App\Models\WarehouseStock;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WarehouseStock>
 */
class WarehouseStockFactory extends Factory
{
    protected $model = WarehouseStock::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(10, 500),
            'reserved_quantity' => 0,
            'reorder_level' => $this->faker->numberBetween(5, 20),
            'reorder_quantity' => $this->faker->numberBetween(50, 200),
            'bin_location' => $this->faker->bothify('A##-B##'),
            'last_counted_at' => $this->faker->dateTimeThisMonth(),
        ];
    }

    /**
     * Indicate low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 5,
            'reorder_level' => 10,
        ]);
    }

    /**
     * Indicate out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }
}
