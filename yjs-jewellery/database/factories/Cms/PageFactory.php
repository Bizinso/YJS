<?php

namespace Database\Factories\Cms;

use App\Models\Cms\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = $this->faker->words(3, true);
        return [
            'uuid' => Str::uuid(),
            'slug' => Str::slug($title) . '-' . $this->faker->randomNumber(4),
            'content_type_id' => null,
            'published_version_id' => null,
            'draft_version_id' => null,
            'scheduled_version_id' => null,
            'scheduled_at' => null,
            'header_section_id' => null,
            'footer_section_id' => null,
            'created_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_version_id' => 1,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_version_id' => 1,
            'scheduled_at' => now()->addDays(7),
        ]);
    }
}
