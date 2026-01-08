<?php

namespace Database\Factories\Cms;

use App\Models\Cms\ContentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContentTypeFactory extends Factory
{
    protected $model = ContentType::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name) . '-' . $this->faker->randomNumber(4),
            'description' => $this->faker->sentence,
            'icon' => 'bi-file-text',
            'fields' => [],
            'default_widgets' => null,
            'default_seo' => null,
            'supports_versioning' => true,
            'supports_scheduling' => true,
            'is_system' => false,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
