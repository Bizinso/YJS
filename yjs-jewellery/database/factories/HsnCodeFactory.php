<?php

namespace Database\Factories;

use App\Models\HsnCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HsnCode>
 */
class HsnCodeFactory extends Factory
{
    protected $model = HsnCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hsnCodes = [
            ['code' => '7113', 'category' => 'Gold Jewellery', 'rate' => 3.00],
            ['code' => '71131100', 'category' => 'Silver Jewellery', 'rate' => 3.00],
            ['code' => '71131911', 'category' => 'Diamond Jewellery', 'rate' => 3.00],
            ['code' => '7102', 'category' => 'Diamonds', 'rate' => 0.25],
            ['code' => '7103', 'category' => 'Gemstones', 'rate' => 0.25],
            ['code' => '7117', 'category' => 'Imitation Jewellery', 'rate' => 12.00],
        ];

        $hsn = $this->faker->randomElement($hsnCodes);

        return [
            'code' => $hsn['code'] . $this->faker->unique()->numerify('##'),
            'description' => $this->faker->sentence,
            'category' => $hsn['category'],
            'default_rate' => $hsn['rate'],
            'is_active' => true,
        ];
    }

    /**
     * Create a gold jewellery HSN code.
     */
    public function goldJewellery(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => '71131919',
            'description' => 'Other gold jewellery',
            'category' => 'Gold Jewellery',
            'default_rate' => 3.00,
        ]);
    }

    /**
     * Create a diamond HSN code.
     */
    public function diamonds(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => '71023100',
            'description' => 'Non-industrial diamonds, unworked or simply sawn',
            'category' => 'Diamonds',
            'default_rate' => 0.25,
        ]);
    }
}
