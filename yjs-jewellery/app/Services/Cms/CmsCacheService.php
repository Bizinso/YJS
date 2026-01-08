<?php

namespace App\Services\Cms;

use App\Models\Cms\Page;
use App\Models\Cms\PageVersion;
use App\Models\Cms\GlobalSection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CmsCacheService - Caching Strategy for CMS Content
 *
 * CRITICAL RULES:
 * 1. Never cache drafts - only published immutable versions
 * 2. Published versions can be cached very long (immutable)
 * 3. Data bindings respect provider TTLs
 * 4. Invalidation happens ONLY on publish
 */
class CmsCacheService
{
    protected const PAGE_CACHE_TTL = 86400; // 24 hours for published pages
    protected const GLOBAL_SECTION_CACHE_TTL = 86400; // 24 hours
    protected const DATA_BINDING_DEFAULT_TTL = 300; // 5 minutes default

    public function __construct(
        protected RenderService $renderService,
        protected DataBindingService $dataBindingService
    ) {}

    /**
     * Get published page with caching.
     */
    public function getPublishedPage(string $slug): ?array
    {
        $cacheKey = "cms:page:published:{$slug}";

        return Cache::tags(['cms', 'cms:pages', "cms:page:{$slug}"])
            ->remember($cacheKey, self::PAGE_CACHE_TTL, function () use ($slug) {
                $page = Page::where('slug', $slug)
                    ->whereNotNull('published_version_id')
                    ->with('publishedVersion')
                    ->first();

                if (!$page || !$page->publishedVersion) {
                    return null;
                }

                return $this->buildPageResponse($page, $page->publishedVersion);
            });
    }

    /**
     * Get published page by ID.
     */
    public function getPublishedPageById(int $pageId): ?array
    {
        $cacheKey = "cms:page:published:id:{$pageId}";

        return Cache::tags(['cms', 'cms:pages', "cms:page:id:{$pageId}"])
            ->remember($cacheKey, self::PAGE_CACHE_TTL, function () use ($pageId) {
                $page = Page::where('id', $pageId)
                    ->whereNotNull('published_version_id')
                    ->with('publishedVersion')
                    ->first();

                if (!$page || !$page->publishedVersion) {
                    return null;
                }

                return $this->buildPageResponse($page, $page->publishedVersion);
            });
    }

    /**
     * Get homepage (published).
     */
    public function getHomepage(): ?array
    {
        $cacheKey = 'cms:page:homepage';

        return Cache::tags(['cms', 'cms:pages', 'cms:homepage'])
            ->remember($cacheKey, self::PAGE_CACHE_TTL, function () {
                // Look for page with slug 'home' or 'homepage', or first page with is_homepage flag
                $page = Page::whereNotNull('published_version_id')
                    ->where(function ($q) {
                        $q->whereIn('slug', ['home', 'homepage', '/'])
                            ->orWhereRaw("JSON_EXTRACT(COALESCE((SELECT layout_settings FROM page_versions_kernel WHERE id = pages.published_version_id), '{}'), '$.is_homepage') = true");
                    })
                    ->with('publishedVersion')
                    ->first();

                if (!$page || !$page->publishedVersion) {
                    // Fallback: get first published page
                    $page = Page::whereNotNull('published_version_id')
                        ->with('publishedVersion')
                        ->first();
                }

                if (!$page || !$page->publishedVersion) {
                    return null;
                }

                return $this->buildPageResponse($page, $page->publishedVersion);
            });
    }

    /**
     * Get global section (header/footer) with caching.
     */
    public function getGlobalSection(string $type): ?array
    {
        if (!in_array($type, ['header', 'footer'])) {
            return null;
        }

        $cacheKey = "cms:global:{$type}";

        return Cache::tags(['cms', 'cms:global', "cms:global:{$type}"])
            ->remember($cacheKey, self::GLOBAL_SECTION_CACHE_TTL, function () use ($type) {
                $section = GlobalSection::where('type', $type)
                    ->where('is_default', true)
                    ->where('is_active', true)
                    ->whereNotNull('published_version_id')
                    ->with('publishedVersion')
                    ->first();

                if (!$section || !$section->publishedVersion) {
                    return null;
                }

                return [
                    'id' => $section->id,
                    'type' => $section->type,
                    'name' => $section->name,
                    'widgets' => $section->publishedVersion->widgets ?? [],
                    'settings' => $section->publishedVersion->settings ?? [],
                    'version' => $section->publishedVersion->version_number,
                ];
            });
    }

    /**
     * Get data binding result with caching.
     */
    public function getBindingData(string $providerId, array $config, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? self::DATA_BINDING_DEFAULT_TTL;
        $configHash = md5(json_encode($config));
        $cacheKey = "cms:binding:{$providerId}:{$configHash}";

        return Cache::tags(['cms', 'cms:bindings', "cms:binding:{$providerId}"])
            ->remember($cacheKey, $ttl, function () use ($providerId, $config) {
                try {
                    $result = $this->dataBindingService->fetchBinding([
                        'provider' => $providerId,
                        ...$config,
                    ]);

                    return $result->toArray();
                } catch (\Exception $e) {
                    Log::warning("Data binding fetch failed", [
                        'provider' => $providerId,
                        'config' => $config,
                        'error' => $e->getMessage(),
                    ]);
                    return null;
                }
            });
    }

    /**
     * Resolve all bindings for a version with caching.
     */
    public function resolveVersionBindings(PageVersion $version): array
    {
        $bindings = $version->data_bindings ?? [];
        if (empty($bindings)) {
            return [];
        }

        $resolved = [];
        foreach ($bindings as $key => $binding) {
            if (empty($binding['provider'])) {
                continue;
            }

            $ttl = $binding['cache_ttl'] ?? self::DATA_BINDING_DEFAULT_TTL;
            $resolved[$key] = $this->getBindingData(
                $binding['provider'],
                $binding,
                $ttl
            );
        }

        return $resolved;
    }

    /**
     * Preview a page (NO caching - drafts are never cached).
     */
    public function getPreview(PageVersion $version): array
    {
        return $this->buildPageResponse($version->page, $version, true);
    }

    /**
     * Invalidate page cache on publish.
     */
    public function invalidatePageCache(Page $page): void
    {
        // Invalidate specific page caches
        Cache::tags(["cms:page:{$page->slug}"])->flush();
        Cache::tags(["cms:page:id:{$page->id}"])->flush();

        // If this might be homepage, also invalidate homepage cache
        if (in_array($page->slug, ['home', 'homepage', '/'])) {
            Cache::tags(['cms:homepage'])->flush();
        }

        Log::info("CMS page cache invalidated", ['page_id' => $page->id, 'slug' => $page->slug]);
    }

    /**
     * Invalidate global section cache on publish.
     */
    public function invalidateGlobalSectionCache(GlobalSection $section): void
    {
        Cache::tags(["cms:global:{$section->type}"])->flush();

        Log::info("CMS global section cache invalidated", [
            'section_id' => $section->id,
            'type' => $section->type,
        ]);
    }

    /**
     * Invalidate all binding caches for a provider.
     */
    public function invalidateBindingCache(string $providerId): void
    {
        Cache::tags(["cms:binding:{$providerId}"])->flush();

        Log::info("CMS binding cache invalidated", ['provider' => $providerId]);
    }

    /**
     * Invalidate all CMS caches.
     */
    public function invalidateAll(): void
    {
        Cache::tags(['cms'])->flush();

        Log::info("All CMS caches invalidated");
    }

    /**
     * Warm cache for a specific page.
     */
    public function warmPageCache(Page $page): void
    {
        if (!$page->published_version_id) {
            return;
        }

        // Force cache refresh
        $this->invalidatePageCache($page);
        $this->getPublishedPage($page->slug);
    }

    /**
     * Warm cache for all published pages.
     */
    public function warmAllPageCaches(): int
    {
        $pages = Page::whereNotNull('published_version_id')
            ->with('publishedVersion')
            ->get();

        $count = 0;
        foreach ($pages as $page) {
            $this->warmPageCache($page);
            $count++;
        }

        Log::info("Warmed cache for {$count} pages");
        return $count;
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        // This is a simple implementation - in production you'd want
        // more sophisticated monitoring
        return [
            'driver' => config('cache.default'),
            'tags_supported' => $this->supportsTagging(),
        ];
    }

    /**
     * Check if cache driver supports tagging.
     */
    protected function supportsTagging(): bool
    {
        try {
            Cache::tags(['test'])->get('test');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Build page response array.
     */
    protected function buildPageResponse(Page $page, PageVersion $version, bool $isPreview = false): array
    {
        // Resolve data bindings
        $resolvedBindings = $isPreview
            ? $this->dataBindingService->resolveBindings($version)
            : $this->resolveVersionBindings($version);

        // Build widgets with resolved data
        $widgets = $version->widgets ?? [];
        foreach ($widgets as &$widget) {
            if (!empty($widget['data_binding']) && isset($resolvedBindings[$widget['id']])) {
                $widget['resolved_data'] = $resolvedBindings[$widget['id']];
            }
        }

        return [
            'id' => $page->id,
            'uuid' => $page->uuid,
            'slug' => $page->slug,
            'title' => $version->title,
            'widgets' => $widgets,
            'layout_settings' => $version->layout_settings ?? [],
            'seo' => $version->seo ?? [],
            'custom_fields' => $version->custom_fields ?? [],
            'version' => $version->version_number,
            'is_preview' => $isPreview,
            'content_type' => $page->contentType?->slug,
        ];
    }
}
