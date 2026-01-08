<?php

namespace App\Contracts\Cms;

/**
 * DataResult - Wrapper for Provider Results
 */
class DataResult
{
    public function __construct(
        public array $items,
        public int $total,
        public array $meta = []
    ) {}

    /**
     * Create from a collection.
     */
    public static function fromCollection($collection): self
    {
        $items = $collection instanceof \Illuminate\Support\Collection
            ? $collection->toArray()
            : (array) $collection;

        return new self(
            items: array_values($items),
            total: count($items)
        );
    }

    /**
     * Create from paginated result.
     */
    public static function fromPaginator($paginator): self
    {
        return new self(
            items: $paginator->items(),
            total: $paginator->total(),
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
            ]
        );
    }

    /**
     * Create empty result.
     */
    public static function empty(): self
    {
        return new self([], 0);
    }

    /**
     * Check if result has items.
     */
    public function hasItems(): bool
    {
        return !empty($this->items);
    }

    /**
     * Get first item.
     */
    public function first(): ?array
    {
        return $this->items[0] ?? null;
    }

    /**
     * Transform items.
     */
    public function transform(callable $callback): self
    {
        return new self(
            items: array_map($callback, $this->items),
            total: $this->total,
            meta: $this->meta
        );
    }

    /**
     * To array.
     */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'total' => $this->total,
            'meta' => $this->meta,
        ];
    }
}
