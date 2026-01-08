<?php

namespace App\Services\Cms;

use App\Contracts\Cms\DataProviderInterface;
use App\Contracts\Cms\DataResult;
use App\Models\Cms\PageVersion;
use App\Models\Cms\DataSource;
use Illuminate\Support\Facades\Cache;

/**
 * DataBindingService - Resolves Dynamic Data for Widgets
 */
class DataBindingService
{
    protected array $providers = [];

    public function registerProvider(DataProviderInterface $provider): void
    {
        $this->providers[$provider->getIdentifier()] = $provider;
    }

    public function registerFromDatabase(): void
    {
        $sources = DataSource::active()->get();

        foreach ($sources as $source) {
            $provider = $source->resolveProvider();
            if ($provider instanceof DataProviderInterface) {
                $this->providers[$source->identifier] = $provider;
            }
        }
    }

    public function getProvider(string $identifier): ?DataProviderInterface
    {
        if (!isset($this->providers[$identifier])) {
            $source = DataSource::byIdentifier($identifier)->active()->first();
            if ($source) {
                $provider = $source->resolveProvider();
                if ($provider instanceof DataProviderInterface) {
                    $this->providers[$identifier] = $provider;
                }
            }
        }
        return $this->providers[$identifier] ?? null;
    }

    public function getAvailableProviders(): array
    {
        $this->registerFromDatabase();
        $result = [];

        foreach ($this->providers as $identifier => $provider) {
            $result[] = [
                'identifier' => $identifier,
                'name' => $provider->getName(),
                'description' => $provider->getDescription(),
                'config_schema' => $provider->getConfigSchema(),
                'available_fields' => $provider->getAvailableFields(),
            ];
        }
        return $result;
    }

    public function resolveBindings(PageVersion $version): array
    {
        $widgets = $version->getWidgets();
        $resolvedData = [];

        foreach ($widgets as $widget) {
            $widgetId = $widget['id'] ?? null;
            if (!$widgetId) continue;

            $binding = $widget['dataBinding'] ?? null;
            if (!$binding || empty($binding['enabled'])) continue;

            $result = $this->fetchBinding([
                'provider' => $binding['provider'] ?? null,
                'config' => $binding['config'] ?? [],
            ]);
            $resolvedData[$widgetId] = $result->toArray();
        }
        return $resolvedData;
    }

    public function fetchBinding(array $bindingConfig): DataResult
    {
        $provider = $this->getProvider($bindingConfig['provider'] ?? '');
        if (!$provider) return DataResult::empty();

        $config = $bindingConfig['config'] ?? [];
        $cacheKey = $provider->getCacheKey($config);
        $cacheTtl = $provider->getCacheTtl();

        if ($cacheTtl > 0) {
            return Cache::remember($cacheKey, $cacheTtl, fn() => $provider->fetch($config));
        }
        return $provider->fetch($config);
    }

    public function previewBinding(array $bindingConfig): DataResult
    {
        $provider = $this->getProvider($bindingConfig['provider'] ?? '');
        if (!$provider) return DataResult::empty();

        $config = $bindingConfig['config'] ?? [];
        $config['limit'] = min($config['limit'] ?? 10, 10);
        return $provider->fetch($config);
    }
}
