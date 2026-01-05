<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->country(),
            'iso_code' => fake()->countryISOAlpha3(),
            'phone_code' => '+' . fake()->numberBetween(1, 999),
            'is_active' => true,
        ];
    }

    /**
     * India country state.
     */
    public function india(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'India',
            'iso_code' => 'IND',
            'phone_code' => '+91',
            'is_active' => true,
        ]);
    }
}
