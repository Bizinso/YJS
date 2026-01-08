<?php

namespace App\Models\Cms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GlobalSectionVersion Model - Immutable Header/Footer Content
 *
 * @property int $id
 * @property int $section_id
 * @property int $version_number
 * @property array $widgets
 * @property array $settings
 * @property string|null $change_note
 * @property int $created_by
 * @property \Carbon\Carbon $created_at
 */
class GlobalSectionVersion extends Model
{
    const UPDATED_AT = null;

    protected $table = 'global_section_versions';

    protected $fillable = [
        'section_id',
        'version_number',
        'widgets',
        'settings',
        'change_note',
        'created_by',
    ];

    protected $casts = [
        'widgets' => 'array',
        'settings' => 'array',
        'created_at' => 'datetime',
    ];

    // Prevent updates to preserve immutability
    public static function boot()
    {
        parent::boot();

        static::updating(function ($version) {
            throw new \RuntimeException(
                "GlobalSectionVersion is immutable. Create a new version instead."
            );
        });
    }

    // ============ RELATIONSHIPS ============

    public function section(): BelongsTo
    {
        return $this->belongsTo(GlobalSection::class, 'section_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============ SCOPES ============

    public function scopeForSection($query, int $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('version_number');
    }

    // ============ HELPERS ============

    public function isPublished(): bool
    {
        return $this->section->published_version_id === $this->id;
    }

    public function isDraft(): bool
    {
        return $this->section->draft_version_id === $this->id;
    }

    public function getWidgets(): array
    {
        return $this->widgets ?? [];
    }

    public function getSettings(): array
    {
        return $this->settings ?? [];
    }

    public function cloneContent(): array
    {
        return [
            'widgets' => $this->widgets,
            'settings' => $this->settings,
        ];
    }
}
