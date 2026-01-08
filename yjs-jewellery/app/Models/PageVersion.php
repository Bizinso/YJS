<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageVersion extends Model
{
    protected $table = 'page_versions';

    protected $fillable = [
        'page_id',
        'version',
        'blocks',
        'page_settings',
        'change_note',
        'created_by',
    ];

    protected $casts = [
        'blocks' => 'array',
        'page_settings' => 'array',
    ];

    // ============ RELATIONSHIPS ============

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============ SCOPES ============

    public function scopeForPage($query, int $pageId)
    {
        return $query->where('page_id', $pageId);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('version');
    }
}
