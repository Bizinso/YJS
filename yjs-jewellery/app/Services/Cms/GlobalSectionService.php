<?php

namespace App\Services\Cms;

use App\Models\Cms\GlobalSection;
use App\Models\Cms\GlobalSectionVersion;
use App\Models\Cms\CmsAuditLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * GlobalSectionService - Manages Header/Footer with Immutable Versioning
 *
 * Applies the same versioning principles as pages:
 * - Versions are NEVER modified after creation
 * - Edits create NEW versions
 * - Publishing switches pointers, not content
 */
class GlobalSectionService
{
    /**
     * Create a new global section with initial version.
     */
    public function create(array $data): GlobalSection
    {
        return DB::transaction(function () use ($data) {
            $section = GlobalSection::create([
                'type' => $data['type'],
                'name' => $data['name'],
                'is_default' => $data['is_default'] ?? false,
                'is_active' => true,
            ]);

            // Create initial version
            $version = $this->createInitialVersion($section, $data);

            return $section->fresh(['draftVersion', 'publishedVersion']);
        });
    }

    /**
     * Create initial version for a new section.
     */
    public function createInitialVersion(GlobalSection $section, array $content): GlobalSectionVersion
    {
        $version = GlobalSectionVersion::create([
            'section_id' => $section->id,
            'version_number' => 1,
            'widgets' => $content['widgets'] ?? [],
            'settings' => $content['settings'] ?? [],
            'change_note' => 'Initial version',
            'created_by' => $this->getCurrentUserId(),
        ]);

        $section->update(['draft_version_id' => $version->id]);

        CmsAuditLog::create([
            'auditable_type' => GlobalSectionVersion::class,
            'auditable_id' => $version->id,
            'action' => 'version_created',
            'new_values' => ['version_number' => 1, 'initial' => true],
            'user_id' => $this->getCurrentUserId(),
        ]);

        return $version;
    }

    /**
     * Create new version from edits (NEVER modifies existing).
     */
    public function createVersionFromEdit(
        GlobalSection $section,
        array $changes,
        ?string $note = null
    ): GlobalSectionVersion {
        return DB::transaction(function () use ($section, $changes, $note) {
            $currentDraft = $section->draftVersion;

            // Merge changes with current content
            $widgets = $changes['widgets'] ?? $currentDraft?->widgets ?? [];
            $settings = array_merge(
                $currentDraft?->settings ?? [],
                $changes['settings'] ?? []
            );

            $nextVersion = $section->getLatestVersionNumber() + 1;

            $version = GlobalSectionVersion::create([
                'section_id' => $section->id,
                'version_number' => $nextVersion,
                'widgets' => $widgets,
                'settings' => $settings,
                'change_note' => $note,
                'created_by' => $this->getCurrentUserId(),
            ]);

            $section->update(['draft_version_id' => $version->id]);

            CmsAuditLog::create([
                'auditable_type' => GlobalSectionVersion::class,
                'auditable_id' => $version->id,
                'action' => 'version_created',
                'new_values' => [
                    'version_number' => $nextVersion,
                    'based_on' => $currentDraft?->version_number,
                ],
                'user_id' => $this->getCurrentUserId(),
            ]);

            return $version;
        });
    }

    /**
     * Publish a version (switches pointer only).
     */
    public function publishVersion(GlobalSectionVersion $version): GlobalSectionVersion
    {
        return DB::transaction(function () use ($version) {
            $section = $version->section;

            // Update pointer
            $section->update(['published_version_id' => $version->id]);

            CmsAuditLog::create([
                'auditable_type' => GlobalSectionVersion::class,
                'auditable_id' => $version->id,
                'action' => 'version_published',
                'new_values' => ['version_number' => $version->version_number],
                'user_id' => $this->getCurrentUserId(),
            ]);

            // Invalidate cache on publish
            $this->invalidateSectionCache($section);

            return $version;
        });
    }

    /**
     * Unpublish a section.
     */
    public function unpublish(GlobalSection $section): void
    {
        $section->update(['published_version_id' => null]);

        CmsAuditLog::create([
            'auditable_type' => GlobalSection::class,
            'auditable_id' => $section->id,
            'action' => 'unpublished',
            'user_id' => $this->getCurrentUserId(),
        ]);

        // Invalidate cache on unpublish
        $this->invalidateSectionCache($section);
    }

    /**
     * Invalidate global section cache when publishing/unpublishing.
     */
    protected function invalidateSectionCache(GlobalSection $section): void
    {
        try {
            if (app()->bound(CmsCacheService::class)) {
                app(CmsCacheService::class)->invalidateGlobalSectionCache($section);
            }
        } catch (\Exception $e) {
            // Don't fail publish on cache issues
            \Log::warning("Cache invalidation failed for global section {$section->type}: " . $e->getMessage());
        }
    }

    /**
     * Rollback to a historical version (creates NEW version).
     */
    public function rollbackToVersion(
        GlobalSection $section,
        GlobalSectionVersion $targetVersion,
        ?string $reason = null
    ): GlobalSectionVersion {
        return DB::transaction(function () use ($section, $targetVersion, $reason) {
            if ($targetVersion->section_id !== $section->id) {
                throw new \InvalidArgumentException('Version does not belong to this section.');
            }

            $nextVersion = $section->getLatestVersionNumber() + 1;
            $content = $targetVersion->cloneContent();

            $version = GlobalSectionVersion::create([
                'section_id' => $section->id,
                'version_number' => $nextVersion,
                'widgets' => $content['widgets'],
                'settings' => $content['settings'],
                'change_note' => $reason ?? "Reverted to version {$targetVersion->version_number}",
                'created_by' => $this->getCurrentUserId(),
            ]);

            $section->update(['draft_version_id' => $version->id]);

            CmsAuditLog::create([
                'auditable_type' => GlobalSectionVersion::class,
                'auditable_id' => $version->id,
                'action' => 'version_reverted',
                'new_values' => [
                    'version_number' => $nextVersion,
                    'reverted_from' => $targetVersion->version_number,
                ],
                'user_id' => $this->getCurrentUserId(),
            ]);

            return $version;
        });
    }

    /**
     * Get version history for a section.
     */
    public function getHistory(GlobalSection $section, int $limit = 20): Collection
    {
        return $section->versions()
            ->with('creator:id,first_name,last_name')
            ->select([
                'id',
                'section_id',
                'version_number',
                'change_note',
                'created_by',
                'created_at',
            ])
            ->take($limit)
            ->get()
            ->map(function ($version) use ($section) {
                return [
                    'id' => $version->id,
                    'version_number' => $version->version_number,
                    'change_note' => $version->change_note,
                    'created_by' => $version->creator
                        ? trim("{$version->creator->first_name} {$version->creator->last_name}")
                        : 'Unknown',
                    'created_at' => $version->created_at,
                    'is_published' => $section->published_version_id === $version->id,
                    'is_draft' => $section->draft_version_id === $version->id,
                ];
            });
    }

    /**
     * Set as default section for its type.
     */
    public function setAsDefault(GlobalSection $section): void
    {
        DB::transaction(function () use ($section) {
            // Remove default from other sections of same type
            GlobalSection::where('type', $section->type)
                ->where('id', '!=', $section->id)
                ->update(['is_default' => false]);

            $section->update(['is_default' => true]);
        });
    }

    /**
     * Delete a section (only if not default and not in use).
     */
    public function delete(GlobalSection $section): void
    {
        if ($section->is_default) {
            throw new \RuntimeException('Cannot delete default section.');
        }

        // Check if used by any pages
        $pagesCount = \App\Models\Cms\Page::where('header_section_id', $section->id)
            ->orWhere('footer_section_id', $section->id)
            ->count();

        if ($pagesCount > 0) {
            throw new \RuntimeException("Cannot delete: Section is used by {$pagesCount} page(s).");
        }

        DB::transaction(function () use ($section) {
            $section->versions()->delete();
            $section->delete();
        });
    }

    /**
     * Get current user ID with fallback.
     */
    protected function getCurrentUserId(): int
    {
        if (auth()->id()) {
            return auth()->id();
        }

        static $defaultUserId = null;
        if ($defaultUserId === null) {
            $defaultUserId = User::query()->min('id') ?? 1;
        }

        return $defaultUserId;
    }
}
