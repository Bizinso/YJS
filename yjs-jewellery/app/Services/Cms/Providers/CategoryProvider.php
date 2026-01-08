<?php

namespace App\Services\Cms\Providers;

use App\Contracts\Cms\DataProviderInterface;
use App\Contracts\Cms\DataResult;
use App\Models\Category;

class CategoryProvider implements DataProviderInterface
{
    public function getIdentifier(): string
    {
        return 'categories';
    }

    public function getName(): string
    {
        return 'Categories';
    }

    public function getDescription(): string
    {
        return 'Fetch product categories';
    }

    public function getConfigSchema(): array
    {
        return [
            'source' => [
                'type' => 'select',
                'label' => 'Source',
                'options' => [
                    ['value' => 'all', 'label' => 'All Categories'],
                    ['value' => 'root', 'label' => 'Root Categories Only'],
                    ['value' => 'manual', 'label' => 'Manual Selection'],
                ],
                'default' => 'root',
            ],
            'category_ids' => [
                'type' => 'multi_select',
                'label' => 'Categories',
                'data_source' => 'categories',
                'visible_when' => ['source' => 'manual'],
            ],
            'include_product_count' => [
                'type' => 'boolean',
                'label' => 'Include Product Count',
                'default' => true,
            ],
            'limit' => [
                'type' => 'number',
                'label' => 'Limit',
                'default' => 12,
                'min' => 1,
                'max' => 50,
            ],
        ];
    }

    public function getAvailableFields(): array
    {
        return [
            'id' => ['type' => 'integer', 'label' => 'ID'],
            'name' => ['type' => 'string', 'label' => 'Name'],
            'slug' => ['type' => 'string', 'label' => 'Slug'],
            'image' => ['type' => 'image', 'label' => 'Image'],
            'description' => ['type' => 'text', 'label' => 'Description'],
            'product_count' => ['type' => 'integer', 'label' => 'Product Count'],
        ];
    }

    public function fetch(array $config): DataResult
    {
        $source = $config['source'] ?? 'root';
        $limit = $config['limit'] ?? 12;
        $includeCount = $config['include_product_count'] ?? true;

        $query = Category::query()->where('status', 'A');

        if ($includeCount) {
            $query->withCount('products');
        }

        switch ($source) {
            case 'root':
                $query->whereNull('parent_id');
                break;
            case 'manual':
                if (!empty($config['category_ids'])) {
                    $query->whereIn('id', $config['category_ids']);
                }
                break;
        }

        $categories = $query->limit($limit)->get();

        return DataResult::fromCollection(
            $categories->map(fn($c) => $this->transformCategory($c, $includeCount))
        );
    }

    public function fetchById(int|string $id): ?array
    {
        $category = Category::withCount('products')->find($id);
        return $category ? $this->transformCategory($category, true) : null;
    }

    public function supports(string $capability): bool
    {
        return in_array($capability, ['filter']);
    }

    public function getCacheTtl(): int
    {
        return 600; // 10 minutes
    }

    public function getCacheKey(array $config): string
    {
        return 'cms:data:categories:' . md5(json_encode($config));
    }

    protected function transformCategory($category, bool $includeCount): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'image' => $category->image,
            'description' => $category->description,
            'product_count' => $includeCount ? ($category->products_count ?? 0) : null,
            'url' => "/categories/{$category->slug}",
        ];
    }
}
