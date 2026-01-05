<?php

namespace Database\Factories;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Partner Factory
 *
 * Creates test partner records for testing B2B functionality.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->partner(),
            'business_name' => fake()->company(),
            'business_type' => fake()->randomElement(['proprietorship', 'partnership', 'pvt_ltd', 'llp', 'other']),
            'phone_number' => fake()->numerify('##########'),
            'gst_number' => strtoupper(fake()->bothify('##???####?#?#')),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'status' => 'pending',
        ];
    }

    /**
     * Create a partner for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the partner is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indicate that the partner is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the partner is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Create a proprietorship business.
     */
    public function proprietorship(): static
    {
        return $this->state(fn (array $attributes) => [
            'business_type' => 'proprietorship',
        ]);
    }

    /**
     * Create a private limited company.
     */
    public function pvtLtd(): static
    {
        return $this->state(fn (array $attributes) => [
            'business_type' => 'pvt_ltd',
        ]);
    }
}
