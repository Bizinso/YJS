<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;

/**
 * UrlRedirect Model - URL Redirect Management
 *
 * @property int $id
 * @property string $old_url
 * @property string $new_url
 * @property string $type
 * @property int $hits
 * @property bool $is_active
 */
class UrlRedirect extends Model
{
    protected $table = 'url_redirects';

    protected $fillable = [
        'old_url',
        'new_url',
        'type',
        'hits',
        'is_active',
    ];

    protected $casts = [
        'hits' => 'integer',
        'is_active' => 'boolean',
    ];

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ============ HELPERS ============

    public function incrementHits(): void
    {
        $this->increment('hits');
    }

    public static function findByOldUrl(string $url): ?self
    {
        return static::active()->where('old_url', $url)->first();
    }
}
