<?php

namespace App\Services\Cms;

use App\Models\Cms\Page;
use App\Models\Cms\PageVersion;
use App\Models\Cms\ContentType;
use App\Models\Cms\CmsAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PageService - Orchestrator for Page Operations
 */
class PageService
{
    public function __construct(
        protected VersionService $versionService,
        protected DataBindingService $dataBindingService
    ) {}

    /**
     * Create a new page with initial version.
     */
    public function create(array $data): Page
    {
        return DB::transaction(function () use ($data) {
            $page = Page::create([
                'uuid' => Str::uuid()->toString(),
                'slug' => $data['slug'],
                'content_type_id' => $data['content_type_id'] ?? null,
                'header_section_id' => $data['header_section_id'] ?? null,
                'footer_section_id' => $data['footer_section_id'] ?? null,
                'created_by' => $this->getCreatorId(),
            ]);

            // Get default widgets from content type
            $contentType = $page->contentType;
            $defaultWidgets = $contentType?->getDefaultWidgets() ?? [];
            $defaultSeo = $contentType?->getDefaultSeo() ?? [];

            $this->versionService->createInitialVersion($page, [
                'title' => $data['title'] ?? 'Untitled',
                'widgets' => $data['widgets'] ?? $defaultWidgets,
                'layout_settings' => $data['layout_settings'] ?? [],
                'data_bindings' => $data['data_bindings'] ?? [],
                'custom_fields' => $data['custom_fields'] ?? [],
                'seo' => array_merge($defaultSeo, $data['seo'] ?? []),
            ]);

            CmsAuditLog::logPage($page, 'created');

            return $page->fresh(['draftVersion', 'contentType']);
        });
    }

    /**
     * Update a page (creates new version).
     */
    public function update(Page $page, array $data): PageVersion
    {
        // Handle slug change
        if (isset($data['slug']) && $data['slug'] !== $page->slug) {
            $this->handleSlugChange($page, $data['slug']);
        }

        // Update page-level attributes
        $page->update(array_filter([
            'slug' => $data['slug'] ?? null,
            'content_type_id' => $data['content_type_id'] ?? null,
            'header_section_id' => $data['header_section_id'] ?? null,
            'footer_section_id' => $data['footer_section_id'] ?? null,
        ], fn($v) => $v !== null));

        // Create new version with content changes
        return $this->versionService->createVersionFromEdit($page, [
            'title' => $data['title'] ?? null,
            'widgets' => $data['widgets'] ?? null,
            'layout_settings' => $data['layout_settings'] ?? null,
            'data_bindings' => $data['data_bindings'] ?? null,
            'custom_fields' => $data['custom_fields'] ?? null,
            'seo' => $data['seo'] ?? null,
        ], $data['change_note'] ?? null);
    }

    /**
     * Publish a page (specific version or current draft).
     */
    public function publish(Page $page, ?PageVersion $version = null): PageVersion
    {
        $version = $version ?? $page->draftVersion;

        if (!$version) {
            throw new \RuntimeException('No version to publish.');
        }

        return $this->versionService->publishVersion($version);
    }

    /**
     * Unpublish a page.
     */
    public function unpublish(Page $page): void
    {
        $this->versionService->unpublishPage($page);
    }

    /**
     * Revert to a previous version.
     */
    public function revert(Page $page, int $versionId, ?string $reason = null): PageVersion
    {
        $targetVersion = PageVersion::findOrFail($versionId);
        return $this->versionService->rollbackToVersion($page, $targetVersion, $reason);
    }

    /**
     * Duplicate a page.
     */
    public function duplicate(Page $page, ?string $newSlug = null): Page
    {
        return DB::transaction(function () use ($page, $newSlug) {
            $draftVersion = $page->draftVersion;

            if (!$draftVersion) {
                throw new \RuntimeException('Cannot duplicate page without draft version.');
            }

            $slug = $newSlug ?? $page->slug . '-copy-' . Str::random(6);

            return $this->create([
                'slug' => $slug,
                'content_type_id' => $page->content_type_id,
                'header_section_id' => $page->header_section_id,
                'footer_section_id' => $page->footer_section_id,
                'title' => $draftVersion->title . ' (Copy)',
                'widgets' => $draftVersion->widgets,
                'layout_settings' => $draftVersion->layout_settings,
                'data_bindings' => $draftVersion->data_bindings,
                'custom_fields' => $draftVersion->custom_fields,
                'seo' => $draftVersion->seo,
            ]);
        });
    }

    /**
     * Soft delete a page.
     */
    public function delete(Page $page): void
    {
        CmsAuditLog::logPage($page, 'deleted');
        $page->delete();
    }

    /**
     * Restore a soft-deleted page.
     */
    public function restore(Page $page): void
    {
        $page->restore();
        CmsAuditLog::logPage($page, 'restored');
    }

    /**
     * Permanently delete a page and all versions.
     */
    public function forceDelete(Page $page): void
    {
        CmsAuditLog::logPage($page, 'force_deleted');
        $page->forceDelete();
    }

    /**
     * Handle slug change - create redirect.
     */
    protected function handleSlugChange(Page $page, string $newSlug): void
    {
        if ($page->isPublished()) {
            \App\Models\Cms\UrlRedirect::create([
                'old_url' => '/' . $page->slug,
                'new_url' => '/' . $newSlug,
                'type' => '301',
                'is_active' => true,
            ]);
        }
    }

    /**
     * Get page for frontend (published version with resolved bindings).
     */
    public function getForFrontend(string $slug): ?array
    {
        $page = Page::where('slug', $slug)->published()->first();

        if (!$page || !$page->publishedVersion) {
            return null;
        }

        $version = $page->publishedVersion;
        $resolvedData = $this->dataBindingService->resolveBindings($version);

        return [
            'page' => [
                'id' => $page->id,
                'uuid' => $page->uuid,
                'slug' => $page->slug,
            ],
            'version' => [
                'id' => $version->id,
                'title' => $version->title,
                'widgets' => $version->widgets,
                'layout_settings' => $version->layout_settings,
            ],
            'seo' => $version->getSeoMeta(),
            'data' => $resolvedData,
            'header' => $page->headerSection?->publishedVersion,
            'footer' => $page->footerSection?->publishedVersion,
        ];
    }

    /**
     * Get page for preview (draft version).
     */
    public function getForPreview(Page $page): array
    {
        $version = $page->draftVersion;

        if (!$version) {
            throw new \RuntimeException('Page has no draft version.');
        }

        $resolvedData = $this->dataBindingService->resolveBindings($version);

        return [
            'page' => [
                'id' => $page->id,
                'uuid' => $page->uuid,
                'slug' => $page->slug,
            ],
            'version' => [
                'id' => $version->id,
                'title' => $version->title,
                'widgets' => $version->widgets,
                'layout_settings' => $version->layout_settings,
            ],
            'seo' => $version->getSeoMeta(),
            'data' => $resolvedData,
            'is_preview' => true,
        ];
    }

    /**
     * Get the creator ID for the current authenticated user.
     * Handles both User and Employee authentication.
     */
    protected function getCreatorId(): int
    {
        $user = auth()->user();

        if (!$user) {
            return 1; // Default to system user
        }

        // If authenticated as Employee, use their linked user_id
        if ($user instanceof \App\Models\Employee) {
            return $user->user_id ?? 1;
        }

        // Otherwise use the authenticated user's id
        return $user->id;
    }
}
