<?php

namespace Database\Factories;

use App\Models\TaxZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxZone>
 */
class TaxZoneFactory extends Factory
{
    protected $model = TaxZone::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stateCode = $this->faker->unique()->stateAbbr;

        return [
            'name' => $this->faker->state,
            'code' => $stateCode,
            'description' => $this->faker->optional()->sentence,
            'countries' => ['IN'],
            'states' => [$stateCode],
            'pincodes' => null,
            'is_default' => false,
            'is_active' => true,
            'priority' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Indicate that the zone is default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the zone is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
