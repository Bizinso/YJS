<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CmsPage extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'cms_pages';

    protected $fillable = [
        'title',
        'slug',
        'type',
        'blocks',
        'page_settings',
        'seo',
        'template_id',
        'status',
        'published_at',
        'scheduled_for',
        'created_by',
        'updated_by',
        'version',
    ];

    protected $casts = [
        'blocks' => 'array',
        'page_settings' => 'array',
        'seo' => 'array',
        'published_at' => 'datetime',
        'scheduled_for' => 'datetime',
    ];

    // ============ RELATIONSHIPS ============

    public function template(): BelongsTo
    {
        return $this->belongsTo(PageTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class, 'page_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(PageBlockAsset::class, 'page_id');
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(PageAnalytics::class, 'page_id');
    }

    public function seoAudit(): HasOne
    {
        return $this->hasOne(SeoAudit::class, 'page_id')->latestOfMany();
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'page_id');
    }

    // ============ SCOPES ============

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->where(function ($q) {
                         $q->whereNull('published_at')
                           ->orWhere('published_at', '<=', now());
                     });
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
                     ->whereNotNull('scheduled_for')
                     ->where('scheduled_for', '>', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeHomepage($query)
    {
        return $query->where('type', 'homepage');
    }

    // ============ HELPERS ============

    /**
     * Get blocks with hydrated e-commerce data.
     */
    public function getBlocksWithData(): array
    {
        $blocks = $this->blocks ?? [];

        foreach ($blocks as &$block) {
            $block = $this->hydrateBlock($block);
        }

        return $blocks;
    }

    /**
     * Hydrate a single block with live data.
     */
    protected function hydrateBlock(array $block): array
    {
        switch ($block['type'] ?? '') {
            case 'productShowcase':
                $block['_products'] = $this->fetchProductsForBlock($block);
                break;

            case 'categoryGrid':
                $block['_categories'] = $this->fetchCategoriesForBlock($block);
                break;

            case 'offerBanner':
                if (!empty($block['data']['offerId'])) {
                    $block['_offer'] = offers::find($block['data']['offerId']);
                }
                break;
        }

        return $block;
    }

    /**
     * Fetch products for ProductShowcase block.
     */
    protected function fetchProductsForBlock(array $block): Collection
    {
        $data = $block['data'] ?? [];
        $limit = $data['limit'] ?? 8;

        $query = Product::query()
            ->where('status', 'active')
            ->where('visibility', 1);

        switch ($data['sourceType'] ?? 'featured') {
            case 'featured':
                $query->where('is_featured', true);
                break;

            case 'category':
                if (!empty($data['categoryId'])) {
                    $query->where('category_id', $data['categoryId']);
                }
                break;

            case 'manual':
                if (!empty($data['productIds'])) {
                    $query->whereIn('id', $data['productIds']);
                }
                break;

            case 'bestsellers':
                $query->orderByDesc('views_count');
                break;

            case 'new':
                $query->orderByDesc('created_at');
                break;
        }

        return $query->limit($limit)->get();
    }

    /**
     * Fetch categories for CategoryGrid block.
     */
    protected function fetchCategoriesForBlock(array $block): Collection
    {
        $categoryIds = $block['data']['categories'] ?? [];

        if (empty($categoryIds)) {
            return Category::where('status', 'A')
                          ->whereNull('parent_id')
                          ->withCount('products')
                          ->get();
        }

        return Category::whereIn('id', $categoryIds)
                       ->withCount('products')
                       ->get();
    }

    /**
     * Check if page is currently live.
     */
    public function isLive(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        if ($this->published_at && $this->published_at->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * Create a new version snapshot.
     */
    public function createVersion(?string $note = null): PageVersion
    {
        return $this->versions()->create([
            'version' => $this->version,
            'blocks' => $this->blocks,
            'page_settings' => $this->page_settings,
            'change_note' => $note,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Restore from a specific version.
     */
    public function restoreVersion(PageVersion $version): bool
    {
        // Save current state first
        $this->createVersion('Auto-saved before restore');

        return $this->update([
            'blocks' => $version->blocks,
            'page_settings' => $version->page_settings,
            'version' => $this->version + 1,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Get SEO meta array for frontend.
     */
    public function getSeoMeta(): array
    {
        $seo = $this->seo ?? [];

        return [
            'title' => $seo['meta_title'] ?? $this->title,
            'description' => $seo['meta_description'] ?? null,
            'og_title' => $seo['og_title'] ?? $seo['meta_title'] ?? $this->title,
            'og_description' => $seo['og_description'] ?? $seo['meta_description'] ?? null,
            'og_image' => $seo['og_image'] ?? null,
            'canonical' => $seo['canonical_url'] ?? null,
            'robots' => $seo['robots'] ?? 'index, follow',
            'schema' => $seo['schema_markup'] ?? null,
        ];
    }

    // ============ ACTIVITY LOG ============

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('CmsPage')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $event) => "CMS Page '{$this->title}' has been {$event}");
    }
}
