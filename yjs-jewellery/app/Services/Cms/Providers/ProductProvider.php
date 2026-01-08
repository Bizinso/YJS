<?php

namespace App\Services\Cms\Providers;

use App\Contracts\Cms\DataProviderInterface;
use App\Contracts\Cms\DataResult;
use App\Models\Product;

class ProductProvider implements DataProviderInterface
{
    public function getIdentifier(): string
    {
        return 'products';
    }

    public function getName(): string
    {
        return 'Products';
    }

    public function getDescription(): string
    {
        return 'Fetch products from the catalog';
    }

    public function getConfigSchema(): array
    {
        return [
            'source' => [
                'type' => 'select',
                'label' => 'Source',
                'options' => [
                    ['value' => 'featured', 'label' => 'Featured Products'],
                    ['value' => 'category', 'label' => 'By Category'],
                    ['value' => 'manual', 'label' => 'Manual Selection'],
                    ['value' => 'bestsellers', 'label' => 'Best Sellers'],
                    ['value' => 'new', 'label' => 'New Arrivals'],
                ],
                'default' => 'featured',
            ],
            'category_id' => [
                'type' => 'select',
                'label' => 'Category',
                'data_source' => 'categories',
                'visible_when' => ['source' => 'category'],
            ],
            'product_ids' => [
                'type' => 'multi_select',
                'label' => 'Products',
                'data_source' => 'products',
                'visible_when' => ['source' => 'manual'],
            ],
            'limit' => [
                'type' => 'number',
                'label' => 'Limit',
                'default' => 8,
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
            'price' => ['type' => 'money', 'label' => 'Price'],
            'sale_price' => ['type' => 'money', 'label' => 'Sale Price'],
            'image' => ['type' => 'image', 'label' => 'Main Image'],
            'category' => ['type' => 'string', 'label' => 'Category'],
            'short_description' => ['type' => 'text', 'label' => 'Short Description'],
        ];
    }

    public function fetch(array $config): DataResult
    {
        $source = $config['source'] ?? 'featured';
        $limit = $config['limit'] ?? 8;

        $query = Product::query()
            ->where('status', 'active')
            ->where('visibility', 1);

        switch ($source) {
            case 'featured':
                $query->where('is_featured', true);
                break;
            case 'category':
                if (!empty($config['category_id'])) {
                    $query->where('category_id', $config['category_id']);
                }
                break;
            case 'manual':
                if (!empty($config['product_ids'])) {
                    $query->whereIn('id', $config['product_ids']);
                }
                break;
            case 'bestsellers':
                $query->orderByDesc('views_count');
                break;
            case 'new':
                $query->orderByDesc('created_at');
                break;
        }

        $products = $query->limit($limit)->get();

        return DataResult::fromCollection(
            $products->map(fn($p) => $this->transformProduct($p))
        );
    }

    public function fetchById(int|string $id): ?array
    {
        $product = Product::find($id);
        return $product ? $this->transformProduct($product) : null;
    }

    public function supports(string $capability): bool
    {
        return in_array($capability, ['pagination', 'filter', 'sort']);
    }

    public function getCacheTtl(): int
    {
        return 300; // 5 minutes
    }

    public function getCacheKey(array $config): string
    {
        return 'cms:data:products:' . md5(json_encode($config));
    }

    protected function transformProduct($product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
            'sale_price' => $product->sale_price,
            'image' => $product->main_image ?? $product->images[0] ?? null,
            'category' => $product->category?->name,
            'short_description' => $product->short_description,
            'url' => "/products/{$product->slug}",
        ];
    }
}
