<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\GlobalSection;
use App\Models\Cms\GlobalSectionVersion;
use App\Services\Cms\GlobalSectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * GlobalSectionController - Manages Header/Footer with Immutable Versioning
 *
 * Same versioning principles as pages:
 * - Versions are NEVER modified after creation
 * - Edits create NEW versions
 * - Publishing switches pointers, not content
 */
class GlobalSectionController extends Controller
{
    public function __construct(
        protected GlobalSectionService $sectionService
    ) {}

    /**
     * List all global sections (headers and footers).
     */
    public function index(): JsonResponse
    {
        $sections = GlobalSection::with(['publishedVersion', 'draftVersion.creator'])
            ->orderBy('type')
            ->orderBy('is_default', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'header' => $this->formatSection($sections->where('type', 'header')->where('is_default', true)->first()),
                'footer' => $this->formatSection($sections->where('type', 'footer')->where('is_default', true)->first()),
                'headers' => $sections->where('type', 'header')->values()->map(fn($s) => $this->formatSection($s)),
                'footers' => $sections->where('type', 'footer')->values()->map(fn($s) => $this->formatSection($s)),
            ],
        ]);
    }

    /**
     * Get a specific global section with its draft version.
     */
    public function show(GlobalSection $globalSection): JsonResponse
    {
        $globalSection->load(['draftVersion.creator', 'publishedVersion']);

        return response()->json([
            'success' => true,
            'data' => $this->formatSection($globalSection),
        ]);
    }

    /**
     * Create a new global section with initial version.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:header,footer',
            'name' => 'required|string|max:100',
            'widgets' => 'array',
            'settings' => 'array',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $section = $this->sectionService->create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully',
            'data' => $this->formatSection($section),
        ], 201);
    }

    /**
     * Update section (creates new version - NEVER modifies existing).
     */
    public function update(Request $request, GlobalSection $globalSection): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'widgets' => 'sometimes|array',
            'settings' => 'sometimes|array',
            'change_note' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Update section metadata
        if (isset($data['name']) || isset($data['is_active'])) {
            $globalSection->update([
                'name' => $data['name'] ?? $globalSection->name,
                'is_active' => $data['is_active'] ?? $globalSection->is_active,
            ]);
        }

        // Create new version from changes (immutable)
        if (isset($data['widgets']) || isset($data['settings'])) {
            $version = $this->sectionService->createVersionFromEdit(
                $globalSection,
                [
                    'widgets' => $data['widgets'] ?? null,
                    'settings' => $data['settings'] ?? null,
                ],
                $data['change_note'] ?? null
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Section updated (new version created)',
            'data' => $this->formatSection($globalSection->fresh(['draftVersion', 'publishedVersion'])),
        ]);
    }

    /**
     * Update by type (header/footer) - convenience endpoint.
     */
    public function updateByType(Request $request, string $type): JsonResponse
    {
        if (!in_array($type, ['header', 'footer'])) {
            return response()->json(['success' => false, 'message' => 'Invalid type'], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'widgets' => 'sometimes|array',
            'blocks' => 'sometimes|array', // Alias for widgets
            'settings' => 'sometimes|array',
            'change_note' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Get or create default section
        $section = GlobalSection::where('type', $type)->where('is_default', true)->first();

        if (!$section) {
            $section = $this->sectionService->create([
                'type' => $type,
                'name' => $data['name'] ?? ucfirst($type),
                'widgets' => $data['widgets'] ?? $data['blocks'] ?? [],
                'settings' => $data['settings'] ?? [],
                'is_default' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Section created',
                'data' => $this->formatSection($section),
            ], 201);
        }

        // Update section metadata
        if (isset($data['name']) || isset($data['is_active'])) {
            $section->update([
                'name' => $data['name'] ?? $section->name,
                'is_active' => $data['is_active'] ?? $section->is_active,
            ]);
        }

        // Create new version (immutable)
        $widgets = $data['widgets'] ?? $data['blocks'] ?? null;
        if ($widgets !== null || isset($data['settings'])) {
            $this->sectionService->createVersionFromEdit(
                $section,
                [
                    'widgets' => $widgets,
                    'settings' => $data['settings'] ?? null,
                ],
                $data['change_note'] ?? null
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Section updated (new version created)',
            'data' => $this->formatSection($section->fresh(['draftVersion', 'publishedVersion'])),
        ]);
    }

    /**
     * Publish a specific version.
     */
    public function publishVersion(GlobalSectionVersion $version): JsonResponse
    {
        $this->sectionService->publishVersion($version);

        return response()->json([
            'success' => true,
            'message' => "Version {$version->version_number} published",
            'data' => $this->formatSection($version->section->fresh(['draftVersion', 'publishedVersion'])),
        ]);
    }

    /**
     * Publish current draft version.
     */
    public function publish(GlobalSection $globalSection): JsonResponse
    {
        if (!$globalSection->draft_version_id) {
            return response()->json([
                'success' => false,
                'message' => 'No draft version to publish',
            ], 400);
        }

        $version = $globalSection->draftVersion;
        $this->sectionService->publishVersion($version);

        return response()->json([
            'success' => true,
            'message' => "Version {$version->version_number} published",
            'data' => $this->formatSection($globalSection->fresh(['draftVersion', 'publishedVersion'])),
        ]);
    }

    /**
     * Unpublish a section.
     */
    public function unpublish(GlobalSection $globalSection): JsonResponse
    {
        $this->sectionService->unpublish($globalSection);

        return response()->json([
            'success' => true,
            'message' => 'Section unpublished',
        ]);
    }

    /**
     * Get version history for a section.
     */
    public function versions(GlobalSection $globalSection): JsonResponse
    {
        $history = $this->sectionService->getHistory($globalSection, 50);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get a specific version.
     */
    public function showVersion(GlobalSectionVersion $version): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $version->id,
                'section_id' => $version->section_id,
                'version_number' => $version->version_number,
                'widgets' => $version->widgets,
                'settings' => $version->settings,
                'change_note' => $version->change_note,
                'created_by' => $version->creator
                    ? trim("{$version->creator->first_name} {$version->creator->last_name}")
                    : 'Unknown',
                'created_at' => $version->created_at,
            ],
        ]);
    }

    /**
     * Rollback to a previous version (creates NEW version).
     */
    public function rollback(Request $request, GlobalSection $globalSection): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'version_id' => 'required|exists:global_section_versions,id',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $targetVersion = GlobalSectionVersion::findOrFail($request->version_id);

        $newVersion = $this->sectionService->rollbackToVersion(
            $globalSection,
            $targetVersion,
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => "Rolled back to version {$targetVersion->version_number} (created new version {$newVersion->version_number})",
            'data' => $this->formatSection($globalSection->fresh(['draftVersion', 'publishedVersion'])),
        ]);
    }

    /**
     * Set section as default for its type.
     */
    public function setDefault(GlobalSection $globalSection): JsonResponse
    {
        $this->sectionService->setAsDefault($globalSection);

        return response()->json([
            'success' => true,
            'message' => 'Section set as default',
        ]);
    }

    /**
     * Delete a section.
     */
    public function destroy(GlobalSection $globalSection): JsonResponse
    {
        try {
            $this->sectionService->delete($globalSection);

            return response()->json([
                'success' => true,
                'message' => 'Section deleted',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Format section for API response.
     */
    protected function formatSection(?GlobalSection $section): ?array
    {
        if (!$section) {
            return null;
        }

        $draft = $section->draftVersion;
        $published = $section->publishedVersion;

        return [
            'id' => $section->id,
            'type' => $section->type,
            'name' => $section->name,
            'is_default' => $section->is_default,
            'is_active' => $section->is_active,

            // Current draft content
            'widgets' => $draft?->widgets ?? [],
            'blocks' => $draft?->widgets ?? [], // Alias for backward compatibility
            'settings' => $draft?->settings ?? [],

            // Version info
            'draft_version_id' => $section->draft_version_id,
            'published_version_id' => $section->published_version_id,
            'draft_version_number' => $draft?->version_number,
            'published_version_number' => $published?->version_number,

            // Status
            'has_unpublished_changes' => $section->draft_version_id !== $section->published_version_id,
            'is_published' => $section->published_version_id !== null,

            // Metadata
            'last_modified_by' => $draft?->creator
                ? trim("{$draft->creator->first_name} {$draft->creator->last_name}")
                : null,
            'last_modified_at' => $draft?->created_at,
            'created_at' => $section->created_at,
            'updated_at' => $section->updated_at,
        ];
    }
}
