<?php

namespace App\Models\Cms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PageVersion Model - Immutable Content Container
 *
 * Once created, a version is NEVER modified. Edits create new versions.
 * This enables safe rollbacks and complete audit trails.
 *
 * @property int $id
 * @property int $page_id
 * @property int $version_number
 * @property string $title
 * @property array $widgets
 * @property array $layout_settings
 * @property array $data_bindings
 * @property array|null $custom_fields
 * @property array $seo
 * @property string $status
 * @property string|null $change_note
 * @property int $created_by
 * @property \Carbon\Carbon $created_at
 * @property string $content_hash
 */
class PageVersion extends Model
{
    const UPDATED_AT = null; // Versions are immutable, no updated_at

    protected $table = 'page_versions_kernel';

    protected $fillable = [
        'page_id',
        'version_number',
        'title',
        'widgets',
        'layout_settings',
        'data_bindings',
        'custom_fields',
        'seo',
        'status',
        'change_note',
        'created_by',
        'content_hash',
    ];

    protected $casts = [
        'widgets' => 'array',
        'layout_settings' => 'array',
        'data_bindings' => 'array',
        'custom_fields' => 'array',
        'seo' => 'array',
        'created_at' => 'datetime',
    ];

    // Prevent updates to preserve immutability
    public static function boot()
    {
        parent::boot();

        static::updating(function ($version) {
            // Only allow status changes (draft -> published -> archived)
            $dirty = $version->getDirty();
            $allowedChanges = ['status'];

            foreach ($dirty as $key => $value) {
                if (!in_array($key, $allowedChanges)) {
                    throw new \RuntimeException(
                        "PageVersion is immutable. Cannot modify '{$key}'. Create a new version instead."
                    );
                }
            }
        });

        static::creating(function ($version) {
            // Auto-generate content hash if not provided
            if (empty($version->content_hash)) {
                $version->content_hash = $version->generateContentHash();
            }
        });
    }

    // ============ RELATIONSHIPS ============

    /**
     * The page this version belongs to.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    /**
     * User who created this version.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Form submissions made on this version.
     */
    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'version_id');
    }

    // ============ SCOPES ============

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeForPage($query, int $pageId)
    {
        return $query->where('page_id', $pageId);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('version_number');
    }

    // ============ HELPERS ============

    /**
     * Generate SHA-256 hash of content for integrity verification.
     */
    public function generateContentHash(): string
    {
        $content = [
            'title' => $this->title,
            'widgets' => $this->widgets,
            'layout_settings' => $this->layout_settings,
            'data_bindings' => $this->data_bindings,
            'custom_fields' => $this->custom_fields,
            'seo' => $this->seo,
        ];

        return hash('sha256', json_encode($content, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Verify content integrity.
     */
    public function verifyIntegrity(): bool
    {
        return $this->content_hash === $this->generateContentHash();
    }

    /**
     * Check if this version is the published one.
     */
    public function isPublished(): bool
    {
        return $this->page->published_version_id === $this->id;
    }

    /**
     * Check if this version is the current draft.
     */
    public function isDraft(): bool
    {
        return $this->page->draft_version_id === $this->id;
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

    /**
     * Get widgets with resolved data bindings.
     * Note: Actual data resolution happens in DataBindingService.
     */
    public function getWidgets(): array
    {
        return $this->widgets ?? [];
    }

    /**
     * Get layout settings.
     */
    public function getLayoutSettings(): array
    {
        return $this->layout_settings ?? [];
    }

    /**
     * Get data bindings configuration.
     */
    public function getDataBindings(): array
    {
        return $this->data_bindings ?? [];
    }

    /**
     * Get custom field values.
     */
    public function getCustomFields(): array
    {
        return $this->custom_fields ?? [];
    }

    /**
     * Clone this version's content for creating a new version.
     */
    public function cloneContent(): array
    {
        return [
            'title' => $this->title,
            'widgets' => $this->widgets,
            'layout_settings' => $this->layout_settings,
            'data_bindings' => $this->data_bindings,
            'custom_fields' => $this->custom_fields,
            'seo' => $this->seo,
        ];
    }
}
