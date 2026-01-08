<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * TaxonomyTerm Model - Individual Terms Within Taxonomies
 *
 * @property int $id
 * @property int $taxonomy_id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property array|null $meta
 * @property int $sort_order
 */
class TaxonomyTerm extends Model
{
    protected $table = 'taxonomy_terms';

    protected $fillable = [
        'taxonomy_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'meta',
        'sort_order',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    // ============ RELATIONSHIPS ============

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'taxonomy_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TaxonomyTerm::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(TaxonomyTerm::class, 'parent_id')->orderBy('sort_order');
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(
            Page::class,
            'page_taxonomy_terms',
            'term_id',
            'page_id'
        );
    }

    // ============ SCOPES ============

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // ============ HELPERS ============

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get all ancestor terms.
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parent;

        while ($current) {
            array_unshift($ancestors, $current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get all descendant terms.
     */
    public function getDescendants(): array
    {
        $descendants = [];

        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->getDescendants());
        }

        return $descendants;
    }

    /**
     * Get full path (breadcrumb).
     */
    public function getPath(string $separator = ' / '): string
    {
        $ancestors = $this->getAncestors();
        $names = array_map(fn($t) => $t->name, $ancestors);
        $names[] = $this->name;

        return implode($separator, $names);
    }
}
