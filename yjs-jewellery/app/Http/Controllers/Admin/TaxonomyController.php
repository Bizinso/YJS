<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Taxonomy;
use App\Models\Cms\TaxonomyTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaxonomyController extends Controller
{
    /**
     * List all taxonomies.
     */
    public function index(Request $request)
    {
        $query = Taxonomy::query()->withCount('terms');

        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        $taxonomies = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $taxonomies->map(fn($t) => $this->transformTaxonomy($t)),
        ]);
    }

    /**
     * Get a single taxonomy with its terms.
     */
    public function show($id)
    {
        $taxonomy = Taxonomy::with(['terms' => function ($query) {
            $query->orderBy('sort_order')->orderBy('name');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->transformTaxonomy($taxonomy, true),
        ]);
    }

    /**
     * Create a new taxonomy.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:taxonomies,slug',
            'description' => 'nullable|string',
            'hierarchical' => 'boolean',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        // Ensure unique slug
        $originalSlug = $slug;
        $counter = 1;
        while (Taxonomy::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $taxonomy = Taxonomy::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'hierarchical' => $validated['hierarchical'] ?? false,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Taxonomy created successfully',
            'data' => $this->transformTaxonomy($taxonomy),
        ], 201);
    }

    /**
     * Update a taxonomy.
     */
    public function update(Request $request, $id)
    {
        $taxonomy = Taxonomy::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:100',
            'description' => 'nullable|string',
            'hierarchical' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $taxonomy->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Taxonomy updated successfully',
            'data' => $this->transformTaxonomy($taxonomy->fresh()),
        ]);
    }

    /**
     * Delete a taxonomy.
     */
    public function destroy($id)
    {
        $taxonomy = Taxonomy::findOrFail($id);

        // Check if taxonomy has terms
        $termsCount = $taxonomy->terms()->count();
        if ($termsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: Taxonomy has {$termsCount} term(s). Delete terms first.",
            ], 422);
        }

        $taxonomy->delete();

        return response()->json([
            'success' => true,
            'message' => 'Taxonomy deleted successfully',
        ]);
    }

    // ============ TERMS ============

    /**
     * List terms for a taxonomy.
     */
    public function terms($taxonomyId, Request $request)
    {
        $taxonomy = Taxonomy::findOrFail($taxonomyId);

        $query = $taxonomy->terms();

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        $terms = $query->orderBy('sort_order')->orderBy('name')->get();

        // If hierarchical, build tree structure
        if ($taxonomy->hierarchical && !$request->has('flat')) {
            $terms = $this->buildTermTree($terms);
        }

        return response()->json([
            'success' => true,
            'data' => $terms,
        ]);
    }

    /**
     * Create a term.
     */
    public function storeTerm(Request $request, $taxonomyId)
    {
        $taxonomy = Taxonomy::findOrFail($taxonomyId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:taxonomy_terms,id',
            'meta' => 'nullable|array',
            'sort_order' => 'integer',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        // Ensure unique slug within taxonomy
        $originalSlug = $slug;
        $counter = 1;
        while (TaxonomyTerm::where('taxonomy_id', $taxonomyId)->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        // Validate parent belongs to same taxonomy
        if (!empty($validated['parent_id'])) {
            $parent = TaxonomyTerm::find($validated['parent_id']);
            if ($parent->taxonomy_id !== (int) $taxonomyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent term must belong to the same taxonomy',
                ], 422);
            }
        }

        $term = TaxonomyTerm::create([
            'taxonomy_id' => $taxonomyId,
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'meta' => $validated['meta'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Term created successfully',
            'data' => $this->transformTerm($term),
        ], 201);
    }

    /**
     * Update a term.
     */
    public function updateTerm(Request $request, $taxonomyId, $termId)
    {
        $term = TaxonomyTerm::where('taxonomy_id', $taxonomyId)
            ->where('id', $termId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:taxonomy_terms,id',
            'meta' => 'nullable|array',
            'sort_order' => 'integer',
        ]);

        // Validate parent belongs to same taxonomy
        if (isset($validated['parent_id']) && $validated['parent_id']) {
            $parent = TaxonomyTerm::find($validated['parent_id']);
            if ($parent->taxonomy_id !== (int) $taxonomyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent term must belong to the same taxonomy',
                ], 422);
            }

            // Prevent circular reference
            if ($validated['parent_id'] == $termId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Term cannot be its own parent',
                ], 422);
            }
        }

        $term->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Term updated successfully',
            'data' => $this->transformTerm($term->fresh()),
        ]);
    }

    /**
     * Delete a term.
     */
    public function destroyTerm($taxonomyId, $termId)
    {
        $term = TaxonomyTerm::where('taxonomy_id', $taxonomyId)
            ->where('id', $termId)
            ->firstOrFail();

        // Check for children
        $childrenCount = TaxonomyTerm::where('parent_id', $termId)->count();
        if ($childrenCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: Term has {$childrenCount} child term(s)",
            ], 422);
        }

        // Check for page associations
        $pagesCount = $term->pages()->count();
        if ($pagesCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: Term is used by {$pagesCount} page(s)",
            ], 422);
        }

        $term->delete();

        return response()->json([
            'success' => true,
            'message' => 'Term deleted successfully',
        ]);
    }

    /**
     * Reorder terms.
     */
    public function reorderTerms(Request $request, $taxonomyId)
    {
        $validated = $request->validate([
            'terms' => 'required|array',
            'terms.*.id' => 'required|exists:taxonomy_terms,id',
            'terms.*.sort_order' => 'required|integer',
            'terms.*.parent_id' => 'nullable|exists:taxonomy_terms,id',
        ]);

        foreach ($validated['terms'] as $termData) {
            TaxonomyTerm::where('id', $termData['id'])
                ->where('taxonomy_id', $taxonomyId)
                ->update([
                    'sort_order' => $termData['sort_order'],
                    'parent_id' => $termData['parent_id'] ?? null,
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Terms reordered successfully',
        ]);
    }

    /**
     * Build hierarchical tree from flat terms.
     */
    protected function buildTermTree($terms, $parentId = null): array
    {
        $tree = [];

        foreach ($terms as $term) {
            if ($term->parent_id === $parentId) {
                $node = $this->transformTerm($term);
                $node['children'] = $this->buildTermTree($terms, $term->id);
                $tree[] = $node;
            }
        }

        return $tree;
    }

    /**
     * Transform taxonomy for API response.
     */
    protected function transformTaxonomy(Taxonomy $taxonomy, bool $includeTerms = false): array
    {
        $data = [
            'id' => $taxonomy->id,
            'name' => $taxonomy->name,
            'slug' => $taxonomy->slug,
            'description' => $taxonomy->description,
            'hierarchical' => $taxonomy->hierarchical,
            'is_active' => $taxonomy->is_active,
            'terms_count' => $taxonomy->terms_count ?? $taxonomy->terms()->count(),
            'created_at' => $taxonomy->created_at,
            'updated_at' => $taxonomy->updated_at,
        ];

        if ($includeTerms && $taxonomy->relationLoaded('terms')) {
            if ($taxonomy->hierarchical) {
                $data['terms'] = $this->buildTermTree($taxonomy->terms);
            } else {
                $data['terms'] = $taxonomy->terms->map(fn($t) => $this->transformTerm($t));
            }
        }

        return $data;
    }

    /**
     * Transform term for API response.
     */
    protected function transformTerm(TaxonomyTerm $term): array
    {
        return [
            'id' => $term->id,
            'taxonomy_id' => $term->taxonomy_id,
            'parent_id' => $term->parent_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'meta' => $term->meta,
            'sort_order' => $term->sort_order,
            'pages_count' => $term->pages()->count(),
            'created_at' => $term->created_at,
            'updated_at' => $term->updated_at,
        ];
    }
}
