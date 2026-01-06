<?php

namespace Database\Factories;

use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockTransfer>
 */
class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transfer_number' => 'TRF-' . $this->faker->unique()->numerify('######'),
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id' => Warehouse::factory(),
            'status' => $this->faker->randomElement(['draft', 'pending', 'in_transit', 'shipped', 'received', 'cancelled']),
            'notes' => $this->faker->sentence,
            'initiated_by' => User::factory(),
            'approved_by' => null,
            'shipped_at' => null,
            'received_at' => null,
        ];
    }

    /**
     * Create a pending transfer.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Create a completed transfer.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'received',
            'shipped_at' => $this->faker->dateTimeThisMonth(),
            'received_at' => now(),
        ]);
    }

    /**
     * Create a cancelled transfer.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
