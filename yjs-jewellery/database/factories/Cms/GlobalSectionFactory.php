<?php

namespace Database\Factories\Cms;

use App\Models\Cms\GlobalSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class GlobalSectionFactory extends Factory
{
    protected $model = GlobalSection::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['header', 'footer']),
            'name' => $this->faker->words(2, true),
            'published_version_id' => null,
            'draft_version_id' => null,
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function header(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'header',
        ]);
    }

    public function footer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'footer',
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
