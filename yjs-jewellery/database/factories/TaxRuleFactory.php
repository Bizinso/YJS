<?php

namespace Database\Factories;

use App\Models\TaxRule;
use App\Models\TaxZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxRule>
 */
class TaxRuleFactory extends Factory
{
    protected $model = TaxRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rates = [0.25, 3.00, 5.00, 12.00, 18.00, 28.00];
        $rate = $this->faker->randomElement($rates);

        return [
            'name' => 'GST ' . $rate . '% - ' . $this->faker->word,
            'code' => 'TAX-' . strtoupper($this->faker->unique()->bothify('???-###')),
            'description' => $this->faker->optional()->sentence,
            'tax_zone_id' => TaxZone::factory(),
            'tax_type' => 'gst',
            'rate' => $rate,
            'cgst_rate' => $rate / 2,
            'sgst_rate' => $rate / 2,
            'igst_rate' => $rate,
            'apply_to' => 'all',
            'apply_to_ids' => null,
            'calculation_type' => 'percentage',
            'is_compound' => false,
            'is_inclusive' => false,
            'is_active' => true,
            'priority' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Create a rule for gold jewellery.
     */
    public function goldJewellery(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'GST 3% - Gold Jewellery',
            'rate' => 3.00,
            'cgst_rate' => 1.50,
            'sgst_rate' => 1.50,
            'igst_rate' => 3.00,
        ]);
    }

    /**
     * Create a rule for diamonds.
     */
    public function diamonds(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'GST 0.25% - Diamonds',
            'rate' => 0.25,
            'cgst_rate' => 0.125,
            'sgst_rate' => 0.125,
            'igst_rate' => 0.25,
        ]);
    }

    /**
     * Indicate that the rule is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
