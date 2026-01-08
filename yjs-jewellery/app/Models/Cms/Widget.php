<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;

/**
 * Widget Model - Component Registry
 *
 * Widgets are the building blocks of pages. This registry defines
 * available widgets, their configuration schemas, and component mappings.
 *
 * @property int $id
 * @property string $identifier
 * @property string $name
 * @property string $category
 * @property string|null $description
 * @property string|null $icon
 * @property array $config_schema
 * @property array $default_config
 * @property bool $supports_data_binding
 * @property array|null $data_provider_types
 * @property string $admin_component
 * @property string $frontend_component
 * @property bool $is_system
 * @property bool $is_active
 * @property int $sort_order
 */
class Widget extends Model
{
    protected $table = 'widgets';

    protected $fillable = [
        'identifier',
        'name',
        'category',
        'description',
        'icon',
        'config_schema',
        'default_config',
        'supports_data_binding',
        'data_provider_types',
        'admin_component',
        'frontend_component',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'config_schema' => 'array',
        'default_config' => 'array',
        'data_provider_types' => 'array',
        'supports_data_binding' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeWithDataBinding($query)
    {
        return $query->where('supports_data_binding', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('category')->orderBy('sort_order');
    }

    // ============ HELPERS ============

    /**
     * Get configuration schema.
     */
    public function getConfigSchema(): array
    {
        return $this->config_schema ?? [];
    }

    /**
     * Get default configuration.
     */
    public function getDefaultConfig(): array
    {
        return $this->default_config ?? [];
    }

    /**
     * Get supported data provider types.
     */
    public function getSupportedProviders(): array
    {
        return $this->data_provider_types ?? [];
    }

    /**
     * Check if widget supports a specific data provider.
     */
    public function supportsProvider(string $providerIdentifier): bool
    {
        if (!$this->supports_data_binding) {
            return false;
        }

        $providers = $this->getSupportedProviders();

        return empty($providers) || in_array($providerIdentifier, $providers);
    }

    /**
     * Create a new widget instance with default config.
     */
    public function createInstance(array $overrides = []): array
    {
        return array_merge([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'type' => $this->identifier,
            'order' => 0,
            'visible' => true,
            'data' => $this->getDefaultConfig(),
            'settings' => [],
            'style' => [
                'backgroundColor' => 'transparent',
                'padding' => ['top' => 0, 'bottom' => 0, 'left' => 0, 'right' => 0],
                'margin' => ['top' => 0, 'bottom' => 0],
                'customCSS' => '',
            ],
            'responsive' => [
                'desktop' => ['visible' => true],
                'tablet' => ['visible' => true],
                'mobile' => ['visible' => true],
            ],
            'animation' => [
                'type' => 'none',
                'duration' => 500,
                'delay' => 0,
            ],
            'dataBinding' => $this->supports_data_binding ? [
                'enabled' => false,
                'provider' => null,
                'config' => [],
            ] : null,
        ], $overrides);
    }

    /**
     * Get widget categories with widgets.
     */
    public static function getByCategory(): array
    {
        return static::active()
            ->ordered()
            ->get()
            ->groupBy('category')
            ->toArray();
    }
}
