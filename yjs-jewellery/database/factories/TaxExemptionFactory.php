<?php

namespace Database\Factories;

use App\Models\TaxExemption;
use App\Models\TaxRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxExemption>
 */
class TaxExemptionFactory extends Factory
{
    protected $model = TaxExemption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tax_rule_id' => TaxRule::factory(),
            'exemption_type' => $this->faker->randomElement(['full', 'partial']),
            'exemption_percentage' => $this->faker->randomElement([25, 50, 75, 100]),
            'reason' => $this->faker->sentence,
            'certificate_number' => 'CERT-' . $this->faker->unique()->numerify('######'),
            'valid_from' => $this->faker->dateTimeThisYear(),
            'valid_until' => $this->faker->dateTimeBetween('+1 month', '+1 year'),
            'is_active' => true,
        ];
    }

    /**
     * Create a full exemption.
     */
    public function fullExemption(): static
    {
        return $this->state(fn (array $attributes) => [
            'exemption_type' => 'full',
            'exemption_percentage' => 100,
        ]);
    }

    /**
     * Create an expired exemption.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_until' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
            'is_active' => false,
        ]);
    }
}
