<?php

namespace App\Services\Cms;

use App\Models\Cms\Page;
use App\Models\Cms\PageVersion;
use App\Models\Cms\CmsAuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;

/**
 * VersionService - The Heart of the CMS Kernel
 *
 * Manages immutable page versions. Key principles:
 * - Versions are NEVER modified after creation
 * - Edits create NEW versions
 * - Publishing switches pointers, not content
 * - Rollbacks create NEW versions from history
 */
class VersionService
{
    /**
     * Create the initial version for a new page.
     */
    public function createInitialVersion(Page $page, array $content): PageVersion
    {
        $version = PageVersion::create([
            'page_id' => $page->id,
            'version_number' => 1,
            'title' => $content['title'] ?? 'Untitled',
            'widgets' => $content['widgets'] ?? [],
            'layout_settings' => $content['layout_settings'] ?? [],
            'data_bindings' => $content['data_bindings'] ?? [],
            'custom_fields' => $content['custom_fields'] ?? [],
            'seo' => $content['seo'] ?? [],
            'status' => 'draft',
            'change_note' => 'Initial version',
            'created_by' => $this->getCurrentUserId(),
        ]);

        // Set as draft version
        $page->update(['draft_version_id' => $version->id]);

        CmsAuditLog::logVersion($version, 'version_created', [
            'version_number' => 1,
            'initial' => true,
        ]);

        return $version;
    }

    /**
     * Create a new version from edits.
     * NEVER modifies existing versions.
     */
    public function createVersionFromEdit(
        Page $page,
        array $changes,
        ?string $note = null
    ): PageVersion {
        return DB::transaction(function () use ($page, $changes, $note) {
            // Get current draft to base new version on
            $currentDraft = $page->draftVersion;

            if (!$currentDraft) {
                throw new \RuntimeException('Page has no draft version to edit.');
            }

            // Merge changes with current content
            $content = $this->mergeChanges($currentDraft, $changes);

            // Get next version number
            $nextVersion = $page->getLatestVersionNumber() + 1;

            // Create new immutable version
            $version = PageVersion::create([
                'page_id' => $page->id,
                'version_number' => $nextVersion,
                'title' => $content['title'],
                'widgets' => $content['widgets'],
                'layout_settings' => $content['layout_settings'],
                'data_bindings' => $content['data_bindings'],
                'custom_fields' => $content['custom_fields'],
                'seo' => $content['seo'],
                'status' => 'draft',
                'change_note' => $note,
                'created_by' => $this->getCurrentUserId(),
            ]);

            // Update page's draft pointer
            $page->update(['draft_version_id' => $version->id]);

            CmsAuditLog::logVersion($version, 'version_created', [
                'version_number' => $nextVersion,
                'based_on' => $currentDraft->version_number,
                'note' => $note,
            ]);

            return $version;
        });
    }

    /**
     * Publish a version.
     * Only changes the pointer, never modifies versions.
     */
    public function publishVersion(PageVersion $version, ?int $userId = null): PageVersion
    {
        return DB::transaction(function () use ($version, $userId) {
            $page = $version->page;
            $previousPublished = $page->publishedVersion;

            // Archive previously published version
            if ($previousPublished && $previousPublished->id !== $version->id) {
                // We only change status, which is allowed
                $previousPublished->update(['status' => 'archived']);
            }

            // Mark this version as published
            $version->update(['status' => 'published']);

            // Switch the pointer
            $page->update([
                'published_version_id' => $version->id,
                // Clear scheduling if this was scheduled
                'scheduled_version_id' => null,
                'scheduled_at' => null,
            ]);

            CmsAuditLog::logVersion($version, 'version_published', [
                'version_number' => $version->version_number,
                'previous_version' => $previousPublished?->version_number,
                'published_by' => $userId ?? auth()->id(),
            ]);

            return $version;
        });
    }

    /**
     * Unpublish a page.
     * Nullifies the published pointer.
     */
    public function unpublishPage(Page $page): void
    {
        $previousPublished = $page->publishedVersion;

        $page->update(['published_version_id' => null]);

        if ($previousPublished) {
            $previousPublished->update(['status' => 'archived']);

            CmsAuditLog::logPage($page, 'unpublished', null, [
                'previous_version' => $previousPublished->version_number,
            ]);
        }
    }

    /**
     * Rollback to a historical version.
     * Creates a NEW version from the target - never modifies existing.
     */
    public function rollbackToVersion(
        Page $page,
        PageVersion $targetVersion,
        ?string $reason = null
    ): PageVersion {
        return DB::transaction(function () use ($page, $targetVersion, $reason) {
            // Verify target belongs to this page
            if ($targetVersion->page_id !== $page->id) {
                throw new \InvalidArgumentException('Version does not belong to this page.');
            }

            // Get next version number
            $nextVersion = $page->getLatestVersionNumber() + 1;

            // Clone content from target version
            $content = $targetVersion->cloneContent();

            // Create NEW version (not restore - creates new)
            $version = PageVersion::create([
                'page_id' => $page->id,
                'version_number' => $nextVersion,
                'title' => $content['title'],
                'widgets' => $content['widgets'],
                'layout_settings' => $content['layout_settings'],
                'data_bindings' => $content['data_bindings'],
                'custom_fields' => $content['custom_fields'],
                'seo' => $content['seo'],
                'status' => 'draft',
                'change_note' => $reason ?? "Reverted to version {$targetVersion->version_number}",
                'created_by' => $this->getCurrentUserId(),
            ]);

            // Update draft pointer
            $page->update(['draft_version_id' => $version->id]);

            CmsAuditLog::logVersion($version, 'version_reverted', [
                'version_number' => $nextVersion,
                'reverted_from' => $targetVersion->version_number,
                'reason' => $reason,
            ]);

            return $version;
        });
    }

    /**
     * Schedule a version for future publish.
     */
    public function scheduleVersion(PageVersion $version, Carbon $publishAt): PageVersion
    {
        $page = $version->page;

        $page->update([
            'scheduled_version_id' => $version->id,
            'scheduled_at' => $publishAt,
        ]);

        CmsAuditLog::logVersion($version, 'scheduled', [
            'version_number' => $version->version_number,
            'scheduled_for' => $publishAt->toDateTimeString(),
        ]);

        return $version;
    }

    /**
     * Remove scheduled publish.
     */
    public function unscheduleVersion(Page $page): void
    {
        $scheduledVersion = $page->scheduledVersion;

        $page->update([
            'scheduled_version_id' => null,
            'scheduled_at' => null,
        ]);

        if ($scheduledVersion) {
            CmsAuditLog::logPage($page, 'unscheduled', null, [
                'version_number' => $scheduledVersion->version_number,
            ]);
        }
    }

    /**
     * Process scheduled publishes (called by scheduler).
     */
    public function processScheduledPublishes(): int
    {
        $pages = Page::dueForPublish()->get();
        $count = 0;

        foreach ($pages as $page) {
            $scheduledVersion = $page->scheduledVersion;

            if ($scheduledVersion) {
                $this->publishVersion($scheduledVersion);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Compare two versions.
     */
    public function compareVersions(PageVersion $v1, PageVersion $v2): array
    {
        $differences = [];

        // Compare title
        if ($v1->title !== $v2->title) {
            $differences['title'] = [
                'v1' => $v1->title,
                'v2' => $v2->title,
            ];
        }

        // Compare widgets (complex diff)
        $widgetsDiff = $this->compareArrays($v1->widgets, $v2->widgets);
        if (!empty($widgetsDiff)) {
            $differences['widgets'] = $widgetsDiff;
        }

        // Compare layout settings
        $layoutDiff = $this->compareArrays($v1->layout_settings, $v2->layout_settings);
        if (!empty($layoutDiff)) {
            $differences['layout_settings'] = $layoutDiff;
        }

        // Compare SEO
        $seoDiff = $this->compareArrays($v1->seo, $v2->seo);
        if (!empty($seoDiff)) {
            $differences['seo'] = $seoDiff;
        }

        // Compare data bindings
        $bindingsDiff = $this->compareArrays($v1->data_bindings, $v2->data_bindings);
        if (!empty($bindingsDiff)) {
            $differences['data_bindings'] = $bindingsDiff;
        }

        // Compare custom fields
        $fieldsDiff = $this->compareArrays($v1->custom_fields ?? [], $v2->custom_fields ?? []);
        if (!empty($fieldsDiff)) {
            $differences['custom_fields'] = $fieldsDiff;
        }

        return [
            'v1' => [
                'id' => $v1->id,
                'version_number' => $v1->version_number,
                'created_at' => $v1->created_at,
            ],
            'v2' => [
                'id' => $v2->id,
                'version_number' => $v2->version_number,
                'created_at' => $v2->created_at,
            ],
            'differences' => $differences,
            'has_changes' => !empty($differences),
        ];
    }

    /**
     * Get version history for a page.
     */
    public function getHistory(Page $page, int $limit = 20): Collection
    {
        return $page->versions()
            ->with('creator:id,first_name,last_name')
            ->select([
                'id',
                'page_id',
                'version_number',
                'title',
                'status',
                'change_note',
                'created_by',
                'created_at',
                'content_hash',
            ])
            ->take($limit)
            ->get()
            ->map(function ($version) use ($page) {
                return [
                    'id' => $version->id,
                    'version_number' => $version->version_number,
                    'title' => $version->title,
                    'status' => $version->status,
                    'change_note' => $version->change_note,
                    'created_by' => $version->creator
                        ? trim("{$version->creator->first_name} {$version->creator->last_name}")
                        : 'Unknown',
                    'created_at' => $version->created_at,
                    'is_published' => $page->published_version_id === $version->id,
                    'is_draft' => $page->draft_version_id === $version->id,
                    'is_scheduled' => $page->scheduled_version_id === $version->id,
                ];
            });
    }

    /**
     * Merge changes into existing content.
     */
    protected function mergeChanges(PageVersion $current, array $changes): array
    {
        return [
            'title' => $changes['title'] ?? $current->title,
            'widgets' => $changes['widgets'] ?? $current->widgets,
            'layout_settings' => array_merge(
                $current->layout_settings ?? [],
                $changes['layout_settings'] ?? []
            ),
            'data_bindings' => $changes['data_bindings'] ?? $current->data_bindings,
            'custom_fields' => array_merge(
                $current->custom_fields ?? [],
                $changes['custom_fields'] ?? []
            ),
            'seo' => array_merge(
                $current->seo ?? [],
                $changes['seo'] ?? []
            ),
        ];
    }

    /**
     * Compare two arrays and return differences.
     */
    protected function compareArrays(array $arr1, array $arr2): array
    {
        $json1 = json_encode($arr1, JSON_UNESCAPED_UNICODE);
        $json2 = json_encode($arr2, JSON_UNESCAPED_UNICODE);

        if ($json1 === $json2) {
            return [];
        }

        return [
            'changed' => true,
            'v1' => $arr1,
            'v2' => $arr2,
        ];
    }

    /**
     * Get the current user ID with fallback to first system user.
     */
    protected function getCurrentUserId(): int
    {
        // Try authenticated user first
        if (auth()->id()) {
            return auth()->id();
        }

        // Fallback to first user in system (for CLI/console context)
        static $defaultUserId = null;

        if ($defaultUserId === null) {
            $defaultUserId = User::query()->min('id') ?? 1;
        }

        return $defaultUserId;
    }
}
