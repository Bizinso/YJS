<?php

namespace Database\Factories\Cms;

use App\Models\Cms\Page;
use App\Models\Cms\PageVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageVersionFactory extends Factory
{
    protected $model = PageVersion::class;

    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'version_number' => $this->faker->numberBetween(1, 100),
            'title' => $this->faker->sentence(3),
            'widgets' => [
                [
                    'id' => 'widget_' . $this->faker->uuid,
                    'type' => 'hero',
                    'order' => 0,
                    'visible' => true,
                    'data' => [
                        'title' => $this->faker->sentence,
                        'subtitle' => $this->faker->sentence,
                    ],
                ],
            ],
            'layout_settings' => [],
            'data_bindings' => [],
            'custom_fields' => [],
            'seo' => [
                'meta_title' => $this->faker->sentence,
                'meta_description' => $this->faker->paragraph,
            ],
            'status' => 'draft',
            'change_note' => null,
            'created_by' => User::factory(),
            'content_hash' => hash('sha256', json_encode([])),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }
}
