<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function scopePermanent($query)
    {
        return $query->where('type', '301');
    }

    public function scopeTemporary($query)
    {
        return $query->where('type', '302');
    }

    // ============ HELPERS ============

    public function incrementHits(): bool
    {
        return $this->increment('hits');
    }

    public function getHttpStatusCode(): int
    {
        return (int) $this->type;
    }

    /**
     * Find redirect for a given URL path.
     */
    public static function findForUrl(string $url): ?self
    {
        // Normalize URL (remove trailing slash, etc.)
        $url = rtrim($url, '/');

        return static::active()
            ->where('old_url', $url)
            ->orWhere('old_url', $url . '/')
            ->first();
    }

    /**
     * Create redirect when page slug changes.
     */
    public static function createForSlugChange(string $oldSlug, string $newSlug): self
    {
        return static::updateOrCreate(
            ['old_url' => '/' . ltrim($oldSlug, '/')],
            [
                'new_url' => '/' . ltrim($newSlug, '/'),
                'type' => '301',
                'is_active' => true,
            ]
        );
    }
}
