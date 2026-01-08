<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;

/**
 * DataSource Model - Provider Configuration
 *
 * Data sources configure how widgets fetch dynamic content.
 * Each source maps to a DataProvider implementation.
 *
 * @property int $id
 * @property string $name
 * @property string $identifier
 * @property string $provider_class
 * @property array $config
 * @property int $cache_ttl
 * @property string $cache_strategy
 * @property bool $is_active
 */
class DataSource extends Model
{
    protected $table = 'data_sources';

    protected $fillable = [
        'name',
        'identifier',
        'provider_class',
        'config',
        'cache_ttl',
        'cache_strategy',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'cache_ttl' => 'integer',
        'is_active' => 'boolean',
    ];

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByIdentifier($query, string $identifier)
    {
        return $query->where('identifier', $identifier);
    }

    // ============ HELPERS ============

    /**
     * Get provider configuration.
     */
    public function getConfig(): array
    {
        return $this->config ?? [];
    }

    /**
     * Get cache TTL in seconds.
     */
    public function getCacheTtl(): int
    {
        return $this->cache_ttl ?? 300;
    }

    /**
     * Get cache strategy.
     */
    public function getCacheStrategy(): string
    {
        return $this->cache_strategy ?? 'tag';
    }

    /**
     * Resolve the provider instance.
     */
    public function resolveProvider(): ?object
    {
        if (!class_exists($this->provider_class)) {
            return null;
        }

        return app($this->provider_class);
    }

    /**
     * Check if provider class exists and is valid.
     */
    public function isProviderValid(): bool
    {
        if (!class_exists($this->provider_class)) {
            return false;
        }

        $provider = $this->resolveProvider();

        return $provider instanceof \App\Contracts\Cms\DataProviderInterface;
    }
}
