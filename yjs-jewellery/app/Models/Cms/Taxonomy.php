<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Taxonomy Model - Categories, Tags, Custom Taxonomies
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $hierarchical
 * @property bool $is_active
 */
class Taxonomy extends Model
{
    protected $table = 'taxonomies';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'hierarchical',
        'is_active',
    ];

    protected $casts = [
        'hierarchical' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ============ RELATIONSHIPS ============

    public function terms(): HasMany
    {
        return $this->hasMany(TaxonomyTerm::class, 'taxonomy_id');
    }

    public function rootTerms(): HasMany
    {
        return $this->hasMany(TaxonomyTerm::class, 'taxonomy_id')
                    ->whereNull('parent_id')
                    ->orderBy('sort_order');
    }

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHierarchical($query)
    {
        return $query->where('hierarchical', true);
    }

    public function scopeFlat($query)
    {
        return $query->where('hierarchical', false);
    }

    // ============ HELPERS ============

    public function isHierarchical(): bool
    {
        return $this->hierarchical;
    }

    /**
     * Get terms as a tree structure.
     */
    public function getTermsTree(): array
    {
        if (!$this->isHierarchical()) {
            return $this->terms()->orderBy('sort_order')->get()->toArray();
        }

        return $this->buildTree($this->terms()->orderBy('sort_order')->get());
    }

    /**
     * Build hierarchical tree from flat collection.
     */
    protected function buildTree($terms, $parentId = null): array
    {
        $tree = [];

        foreach ($terms as $term) {
            if ($term->parent_id === $parentId) {
                $children = $this->buildTree($terms, $term->id);

                $item = $term->toArray();
                $item['children'] = $children;
                $tree[] = $item;
            }
        }

        return $tree;
    }
}
