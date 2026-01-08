<?php

namespace App\Services\Cms\Providers;

use App\Contracts\Cms\DataProviderInterface;
use App\Contracts\Cms\DataResult;
use App\Models\Offer;

class OfferProvider implements DataProviderInterface
{
    public function getIdentifier(): string
    {
        return 'offers';
    }

    public function getName(): string
    {
        return 'Offers';
    }

    public function getDescription(): string
    {
        return 'Fetch active offers and promotions';
    }

    public function getConfigSchema(): array
    {
        return [
            'source' => [
                'type' => 'select',
                'label' => 'Source',
                'options' => [
                    ['value' => 'active', 'label' => 'All Active Offers'],
                    ['value' => 'flash_sales', 'label' => 'Flash Sales Only'],
                    ['value' => 'coupons', 'label' => 'Coupons Only'],
                    ['value' => 'manual', 'label' => 'Manual Selection'],
                ],
                'default' => 'active',
            ],
            'offer_ids' => [
                'type' => 'multi_select',
                'label' => 'Offers',
                'data_source' => 'offers',
                'visible_when' => ['source' => 'manual'],
            ],
            'limit' => [
                'type' => 'number',
                'label' => 'Limit',
                'default' => 6,
                'min' => 1,
                'max' => 20,
            ],
        ];
    }

    public function getAvailableFields(): array
    {
        return [
            'id' => ['type' => 'integer', 'label' => 'ID'],
            'name' => ['type' => 'string', 'label' => 'Name'],
            'title' => ['type' => 'string', 'label' => 'Title'],
            'description' => ['type' => 'text', 'label' => 'Description'],
            'discount_type' => ['type' => 'string', 'label' => 'Discount Type'],
            'discount_value' => ['type' => 'number', 'label' => 'Discount Value'],
            'image' => ['type' => 'image', 'label' => 'Banner Image'],
            'starts_at' => ['type' => 'datetime', 'label' => 'Start Date'],
            'ends_at' => ['type' => 'datetime', 'label' => 'End Date'],
        ];
    }

    public function fetch(array $config): DataResult
    {
        $source = $config['source'] ?? 'active';
        $limit = $config['limit'] ?? 6;

        $query = Offer::query()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });

        switch ($source) {
            case 'flash_sales':
                $query->where('type', 'flash_sale');
                break;
            case 'coupons':
                $query->where('type', 'coupon');
                break;
            case 'manual':
                if (!empty($config['offer_ids'])) {
                    $query->whereIn('id', $config['offer_ids']);
                }
                break;
        }

        $offers = $query->orderBy('priority')->limit($limit)->get();

        return DataResult::fromCollection(
            $offers->map(fn($o) => $this->transformOffer($o))
        );
    }

    public function fetchById(int|string $id): ?array
    {
        $offer = Offer::find($id);
        return $offer ? $this->transformOffer($offer) : null;
    }

    public function supports(string $capability): bool
    {
        return in_array($capability, ['filter']);
    }

    public function getCacheTtl(): int
    {
        return 60; // 1 minute (offers change frequently)
    }

    public function getCacheKey(array $config): string
    {
        return 'cms:data:offers:' . md5(json_encode($config));
    }

    protected function transformOffer($offer): array
    {
        return [
            'id' => $offer->id,
            'name' => $offer->name,
            'title' => $offer->title ?? $offer->name,
            'description' => $offer->description,
            'discount_type' => $offer->discount_type,
            'discount_value' => $offer->discount_value,
            'image' => $offer->banner_image ?? $offer->image,
            'starts_at' => $offer->starts_at?->toISOString(),
            'ends_at' => $offer->ends_at?->toISOString(),
            'countdown' => $offer->ends_at ? [
                'enabled' => true,
                'target' => $offer->ends_at->toISOString(),
            ] : null,
        ];
    }
}
