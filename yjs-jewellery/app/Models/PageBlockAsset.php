<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PageBlockAsset extends Model
{
    protected $table = 'page_block_assets';

    protected $fillable = [
        'page_id',
        'type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $appends = ['url'];

    // ============ RELATIONSHIPS ============

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }

    // ============ ACCESSORS ============

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    // ============ SCOPES ============

    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    public function scopeForPage($query, int $pageId)
    {
        return $query->where('page_id', $pageId);
    }

    // ============ HELPERS ============

    public function deleteFile(): bool
    {
        if (Storage::disk('public')->exists($this->file_path)) {
            return Storage::disk('public')->delete($this->file_path);
        }
        return true;
    }
}
