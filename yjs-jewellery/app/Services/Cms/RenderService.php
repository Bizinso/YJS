<?php

namespace App\Services\Cms;

use App\Models\Cms\Page;
use App\Models\Cms\PageVersion;
use App\Models\Cms\GlobalSection;

/**
 * RenderService - Renders CMS pages for frontend display.
 *
 * Handles:
 * - Resolving data bindings
 * - Building SEO meta tags
 * - Building JSON-LD schema markup
 */
class RenderService
{
    public function __construct(
        protected DataBindingService $dataBindingService
    ) {}

    /**
     * Render a published page for frontend.
     */
    public function renderPage(Page $page): array
    {
        if (!$page->publishedVersion) {
            throw new \RuntimeException("Page has no published version");
        }

        return $this->renderVersion($page, $page->publishedVersion);
    }

    /**
     * Render preview (no caching, resolves draft).
     */
    public function renderPreview(PageVersion $version): array
    {
        return $this->renderVersion($version->page, $version, true);
    }

    /**
     * Render a specific version.
     */
    protected function renderVersion(Page $page, PageVersion $version, bool $isPreview = false): array
    {
        // Resolve data bindings
        $resolvedBindings = $this->dataBindingService->resolveBindings($version);

        // Process widgets with resolved data
        $widgets = $this->processWidgets($version->widgets ?? [], $resolvedBindings);

        return [
            'id' => $page->id,
            'uuid' => $page->uuid,
            'slug' => $page->slug,
            'title' => $version->title,
            'widgets' => $widgets,
            'layout_settings' => $version->layout_settings ?? [],
            'seo' => $this->buildSeoMeta($version),
            'schema_markup' => $this->buildSchemaMarkup($version),
            'custom_fields' => $version->custom_fields ?? [],
            'version' => $version->version_number,
            'is_preview' => $isPreview,
            'content_type' => $page->contentType?->slug,
            'header' => $this->getGlobalSection($page, 'header'),
            'footer' => $this->getGlobalSection($page, 'footer'),
        ];
    }

    /**
     * Process widgets with resolved data bindings.
     */
    protected function processWidgets(array $widgets, array $resolvedBindings): array
    {
        foreach ($widgets as &$widget) {
            // Inject resolved data if widget has data binding
            if (!empty($widget['data_binding']) && isset($widget['id'])) {
                $bindingKey = $widget['id'];
                if (isset($resolvedBindings[$bindingKey])) {
                    $widget['resolved_data'] = $resolvedBindings[$bindingKey];
                }
            }

            // Process nested widgets if any
            if (!empty($widget['children']) && is_array($widget['children'])) {
                $widget['children'] = $this->processWidgets($widget['children'], $resolvedBindings);
            }
        }

        return $widgets;
    }

    /**
     * Build SEO meta tags from version.
     */
    public function buildSeoMeta(PageVersion $version): array
    {
        $seo = $version->seo ?? [];

        return [
            'title' => $seo['meta_title'] ?? $version->title,
            'description' => $seo['meta_description'] ?? null,
            'keywords' => $seo['meta_keywords'] ?? null,
            'canonical' => $seo['canonical'] ?? null,
            'robots' => $seo['robots'] ?? 'index, follow',
            'og' => [
                'title' => $seo['og_title'] ?? $seo['meta_title'] ?? $version->title,
                'description' => $seo['og_description'] ?? $seo['meta_description'] ?? null,
                'image' => $seo['og_image'] ?? null,
                'type' => $seo['og_type'] ?? 'website',
            ],
            'twitter' => [
                'card' => $seo['twitter_card'] ?? 'summary_large_image',
                'title' => $seo['twitter_title'] ?? $seo['og_title'] ?? $version->title,
                'description' => $seo['twitter_description'] ?? $seo['og_description'] ?? null,
                'image' => $seo['twitter_image'] ?? $seo['og_image'] ?? null,
            ],
        ];
    }

    /**
     * Build JSON-LD schema markup.
     */
    public function buildSchemaMarkup(PageVersion $version): ?string
    {
        $seo = $version->seo ?? [];

        // If custom schema is provided, use it
        if (!empty($seo['schema_markup'])) {
            return $seo['schema_markup'];
        }

        // Build default WebPage schema
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $seo['meta_title'] ?? $version->title,
            'description' => $seo['meta_description'] ?? null,
        ];

        // Add breadcrumb if available
        if (!empty($version->layout_settings['breadcrumbs'])) {
            $schema['breadcrumb'] = $this->buildBreadcrumbSchema($version->layout_settings['breadcrumbs']);
        }

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Build breadcrumb schema.
     */
    protected function buildBreadcrumbSchema(array $breadcrumbs): array
    {
        $items = [];
        foreach ($breadcrumbs as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['label'] ?? $crumb['name'] ?? '',
                'item' => $crumb['url'] ?? null,
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Get global section (header/footer) for a page.
     */
    protected function getGlobalSection(Page $page, string $type): ?array
    {
        // Check if page has specific section assigned
        $sectionId = $type === 'header' ? $page->header_section_id : $page->footer_section_id;

        if ($sectionId) {
            $section = GlobalSection::find($sectionId);
        } else {
            // Use default section
            $section = GlobalSection::where('type', $type)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();
        }

        if (!$section || !$section->publishedVersion) {
            return null;
        }

        return [
            'id' => $section->id,
            'type' => $section->type,
            'widgets' => $section->publishedVersion->widgets ?? [],
            'settings' => $section->publishedVersion->settings ?? [],
        ];
    }

    /**
     * Render page by slug.
     */
    public function renderBySlug(string $slug): ?array
    {
        $page = Page::where('slug', $slug)
            ->whereNotNull('published_version_id')
            ->with(['publishedVersion', 'contentType'])
            ->first();

        if (!$page) {
            return null;
        }

        return $this->renderPage($page);
    }
}
