<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\ContentType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContentTypeController extends Controller
{
    /**
     * List all content types.
     */
    public function index(Request $request)
    {
        $query = ContentType::query();

        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        $contentTypes = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $contentTypes->map(fn($ct) => $this->transformContentType($ct)),
        ]);
    }

    /**
     * Get a single content type.
     */
    public function show($id)
    {
        $contentType = ContentType::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->transformContentType($contentType),
        ]);
    }

    /**
     * Create a new content type.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:content_types,slug',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'fields' => 'required|array',
            'fields.*.name' => 'required|string',
            'fields.*.type' => 'required|string',
            'fields.*.label' => 'required|string',
            'fields.*.required' => 'boolean',
            'fields.*.options' => 'nullable|array',
            'default_widgets' => 'nullable|array',
            'default_seo' => 'nullable|array',
            'supports_versioning' => 'boolean',
            'supports_scheduling' => 'boolean',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        // Ensure unique slug
        $originalSlug = $slug;
        $counter = 1;
        while (ContentType::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $contentType = ContentType::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? 'bi-file-earmark',
            'fields' => $validated['fields'],
            'default_widgets' => $validated['default_widgets'] ?? [],
            'default_seo' => $validated['default_seo'] ?? [],
            'supports_versioning' => $validated['supports_versioning'] ?? true,
            'supports_scheduling' => $validated['supports_scheduling'] ?? true,
            'is_system' => false,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Content type created successfully',
            'data' => $this->transformContentType($contentType),
        ], 201);
    }

    /**
     * Update a content type.
     */
    public function update(Request $request, $id)
    {
        $contentType = ContentType::findOrFail($id);

        // Prevent editing system content types
        if ($contentType->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify system content types',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'string|max:100',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'fields' => 'array',
            'fields.*.name' => 'required|string',
            'fields.*.type' => 'required|string',
            'fields.*.label' => 'required|string',
            'fields.*.required' => 'boolean',
            'fields.*.options' => 'nullable|array',
            'default_widgets' => 'nullable|array',
            'default_seo' => 'nullable|array',
            'supports_versioning' => 'boolean',
            'supports_scheduling' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $contentType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Content type updated successfully',
            'data' => $this->transformContentType($contentType->fresh()),
        ]);
    }

    /**
     * Delete a content type.
     */
    public function destroy($id)
    {
        $contentType = ContentType::findOrFail($id);

        // Prevent deleting system content types
        if ($contentType->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete system content types',
            ], 403);
        }

        // Check if any pages use this content type
        $pagesCount = $contentType->pages()->count();
        if ($pagesCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: {$pagesCount} page(s) use this content type",
            ], 422);
        }

        $contentType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Content type deleted successfully',
        ]);
    }

    /**
     * Get available field types.
     */
    public function fieldTypes()
    {
        $fieldTypes = [
            [
                'type' => 'text',
                'label' => 'Text',
                'icon' => 'bi-fonts',
                'description' => 'Single line text input',
                'options' => ['placeholder', 'max_length'],
            ],
            [
                'type' => 'textarea',
                'label' => 'Textarea',
                'icon' => 'bi-text-paragraph',
                'description' => 'Multi-line text input',
                'options' => ['placeholder', 'rows'],
            ],
            [
                'type' => 'richtext',
                'label' => 'Rich Text',
                'icon' => 'bi-file-richtext',
                'description' => 'WYSIWYG editor',
                'options' => [],
            ],
            [
                'type' => 'number',
                'label' => 'Number',
                'icon' => 'bi-123',
                'description' => 'Numeric input',
                'options' => ['min', 'max', 'step'],
            ],
            [
                'type' => 'select',
                'label' => 'Select',
                'icon' => 'bi-list',
                'description' => 'Dropdown selection',
                'options' => ['choices', 'multiple'],
            ],
            [
                'type' => 'checkbox',
                'label' => 'Checkbox',
                'icon' => 'bi-check-square',
                'description' => 'Boolean toggle',
                'options' => [],
            ],
            [
                'type' => 'date',
                'label' => 'Date',
                'icon' => 'bi-calendar',
                'description' => 'Date picker',
                'options' => ['min_date', 'max_date'],
            ],
            [
                'type' => 'datetime',
                'label' => 'Date & Time',
                'icon' => 'bi-calendar-event',
                'description' => 'Date and time picker',
                'options' => [],
            ],
            [
                'type' => 'image',
                'label' => 'Image',
                'icon' => 'bi-image',
                'description' => 'Image upload',
                'options' => ['max_size', 'allowed_types'],
            ],
            [
                'type' => 'file',
                'label' => 'File',
                'icon' => 'bi-file-earmark',
                'description' => 'File upload',
                'options' => ['max_size', 'allowed_types'],
            ],
            [
                'type' => 'url',
                'label' => 'URL',
                'icon' => 'bi-link',
                'description' => 'URL input',
                'options' => [],
            ],
            [
                'type' => 'email',
                'label' => 'Email',
                'icon' => 'bi-envelope',
                'description' => 'Email input',
                'options' => [],
            ],
            [
                'type' => 'color',
                'label' => 'Color',
                'icon' => 'bi-palette',
                'description' => 'Color picker',
                'options' => [],
            ],
            [
                'type' => 'repeater',
                'label' => 'Repeater',
                'icon' => 'bi-collection',
                'description' => 'Repeatable field group',
                'options' => ['fields', 'min', 'max'],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $fieldTypes,
        ]);
    }

    /**
     * Transform content type for API response.
     */
    protected function transformContentType(ContentType $contentType): array
    {
        return [
            'id' => $contentType->id,
            'name' => $contentType->name,
            'slug' => $contentType->slug,
            'description' => $contentType->description,
            'icon' => $contentType->icon,
            'fields' => $contentType->fields,
            'default_widgets' => $contentType->default_widgets,
            'default_seo' => $contentType->default_seo,
            'supports_versioning' => $contentType->supports_versioning,
            'supports_scheduling' => $contentType->supports_scheduling,
            'is_system' => $contentType->is_system,
            'is_active' => $contentType->is_active,
            'pages_count' => $contentType->pages()->count(),
            'created_at' => $contentType->created_at,
            'updated_at' => $contentType->updated_at,
        ];
    }
}
