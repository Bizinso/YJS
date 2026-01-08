<?php

namespace App\Models\Cms;

use Database\Factories\Cms\GlobalSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GlobalSection Model - Header/Footer with Versioning
 *
 * Global sections use the same versioning pattern as pages.
 *
 * @property int $id
 * @property string $type
 * @property string $name
 * @property int|null $published_version_id
 * @property int|null $draft_version_id
 * @property bool $is_default
 * @property bool $is_active
 */
class GlobalSection extends Model
{
    use HasFactory;

    protected $table = 'global_sections';

    protected static function newFactory()
    {
        return GlobalSectionFactory::new();
    }

    protected $fillable = [
        'type',
        'name',
        'published_version_id',
        'draft_version_id',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ============ RELATIONSHIPS ============

    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(GlobalSectionVersion::class, 'published_version_id');
    }

    public function draftVersion(): BelongsTo
    {
        return $this->belongsTo(GlobalSectionVersion::class, 'draft_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(GlobalSectionVersion::class, 'section_id')->orderByDesc('version_number');
    }

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHeaders($query)
    {
        return $query->where('type', 'header');
    }

    public function scopeFooters($query)
    {
        return $query->where('type', 'footer');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ============ HELPERS ============

    public function isPublished(): bool
    {
        return $this->published_version_id !== null;
    }

    public function hasPendingChanges(): bool
    {
        if (!$this->published_version_id) {
            return true;
        }

        return $this->draft_version_id !== $this->published_version_id;
    }

    public function getLatestVersionNumber(): int
    {
        return $this->versions()->max('version_number') ?? 0;
    }

    /**
     * Get default header.
     */
    public static function getDefaultHeader(): ?self
    {
        return static::headers()->default()->active()->first();
    }

    /**
     * Get default footer.
     */
    public static function getDefaultFooter(): ?self
    {
        return static::footers()->default()->active()->first();
    }
}
