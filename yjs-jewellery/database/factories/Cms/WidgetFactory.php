<?php

namespace Database\Factories\Cms;

use App\Models\Cms\Widget;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WidgetFactory extends Factory
{
    protected $model = Widget::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        $categories = ['layout', 'content', 'media', 'ecommerce', 'conversion', 'social', 'forms', 'advanced'];

        return [
            'identifier' => Str::slug($name),
            'name' => ucwords($name),
            'category' => $this->faker->randomElement($categories),
            'description' => $this->faker->sentence,
            'icon' => 'bi-square',
            'config_schema' => [],
            'default_config' => [],
            'supports_data_binding' => false,
            'data_provider_types' => null,
            'admin_component' => 'GenericBlockPreview',
            'frontend_component' => 'GenericBlock',
            'is_system' => false,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function dataBinding(): static
    {
        return $this->state(fn (array $attributes) => [
            'supports_data_binding' => true,
            'data_provider_types' => ['products', 'categories'],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
