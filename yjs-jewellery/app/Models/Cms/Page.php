<?php

namespace App\Models\Cms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Page Model - Identity Layer Only
 *
 * Pages are identity containers with pointers to immutable versions.
 * Content lives in PageVersion, not here.
 *
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property int|null $content_type_id
 * @property int|null $published_version_id
 * @property int|null $draft_version_id
 * @property int|null $scheduled_version_id
 * @property \Carbon\Carbon|null $scheduled_at
 * @property int|null $header_section_id
 * @property int|null $footer_section_id
 * @property int $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Page extends Model
{
    use SoftDeletes;

    protected $table = 'pages';

    protected $fillable = [
        'uuid',
        'slug',
        'content_type_id',
        'published_version_id',
        'draft_version_id',
        'scheduled_version_id',
        'scheduled_at',
        'header_section_id',
        'footer_section_id',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->uuid)) {
                $page->uuid = Str::uuid()->toString();
            }
        });
    }

    // ============ RELATIONSHIPS ============

    /**
     * Content type defines the page's field schema.
     */
    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class, 'content_type_id');
    }

    /**
     * The currently published version (may be null if unpublished).
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(PageVersion::class, 'published_version_id');
    }

    /**
     * The current draft version being edited.
     */
    public function draftVersion(): BelongsTo
    {
        return $this->belongsTo(PageVersion::class, 'draft_version_id');
    }

    /**
     * Version scheduled for future publish.
     */
    public function scheduledVersion(): BelongsTo
    {
        return $this->belongsTo(PageVersion::class, 'scheduled_version_id');
    }

    /**
     * All versions of this page (immutable history).
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class, 'page_id')->orderByDesc('version_number');
    }

    /**
     * User who created this page.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Header section assigned to this page.
     */
    public function headerSection(): BelongsTo
    {
        return $this->belongsTo(GlobalSection::class, 'header_section_id');
    }

    /**
     * Footer section assigned to this page.
     */
    public function footerSection(): BelongsTo
    {
        return $this->belongsTo(GlobalSection::class, 'footer_section_id');
    }

    /**
     * Taxonomy terms associated with this page.
     */
    public function taxonomyTerms(): BelongsToMany
    {
        return $this->belongsToMany(
            TaxonomyTerm::class,
            'page_taxonomy_terms',
            'page_id',
            'term_id'
        );
    }

    /**
     * Form submissions for this page.
     */
    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'page_id');
    }

    /**
     * Analytics records for this page.
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(PageAnalytics::class, 'page_id');
    }

    // ============ SCOPES ============

    /**
     * Pages that have a published version.
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_version_id');
    }

    /**
     * Pages that only have drafts.
     */
    public function scopeDraft($query)
    {
        return $query->whereNull('published_version_id');
    }

    /**
     * Pages with scheduled publish.
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_version_id')
                     ->whereNotNull('scheduled_at')
                     ->where('scheduled_at', '>', now());
    }

    /**
     * Pages due for scheduled publish.
     */
    public function scopeDueForPublish($query)
    {
        return $query->whereNotNull('scheduled_version_id')
                     ->whereNotNull('scheduled_at')
                     ->where('scheduled_at', '<=', now());
    }

    /**
     * Filter by content type.
     */
    public function scopeOfType($query, string $typeSlug)
    {
        return $query->whereHas('contentType', function ($q) use ($typeSlug) {
            $q->where('slug', $typeSlug);
        });
    }

    // ============ HELPERS ============

    /**
     * Check if page is currently published.
     */
    public function isPublished(): bool
    {
        return $this->published_version_id !== null;
    }

    /**
     * Check if page has scheduled publish.
     */
    public function isScheduled(): bool
    {
        return $this->scheduled_version_id !== null
            && $this->scheduled_at !== null
            && $this->scheduled_at->isFuture();
    }

    /**
     * Check if draft differs from published.
     */
    public function hasPendingChanges(): bool
    {
        if (!$this->published_version_id) {
            return true;
        }

        return $this->draft_version_id !== $this->published_version_id;
    }

    /**
     * Get the version to display (published for frontend, draft for admin).
     */
    public function getDisplayVersion(bool $preview = false): ?PageVersion
    {
        if ($preview) {
            return $this->draftVersion;
        }

        return $this->publishedVersion;
    }

    /**
     * Get the current title from draft version.
     */
    public function getTitleAttribute(): ?string
    {
        return $this->draftVersion?->title;
    }

    /**
     * Get the latest version number.
     */
    public function getLatestVersionNumber(): int
    {
        return $this->versions()->max('version_number') ?? 0;
    }

    /**
     * Get status string for UI.
     */
    public function getStatusAttribute(): string
    {
        if ($this->isScheduled()) {
            return 'scheduled';
        }

        if ($this->isPublished()) {
            return $this->hasPendingChanges() ? 'modified' : 'published';
        }

        return 'draft';
    }
}
