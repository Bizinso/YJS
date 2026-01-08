<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    protected $table = 'form_submissions';

    protected $fillable = [
        'page_id',
        'block_id',
        'form_name',
        'form_data',
        'ip_address',
        'user_agent',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'form_data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // ============ RELATIONSHIPS ============

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }

    // ============ SCOPES ============

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeForPage($query, int $pageId)
    {
        return $query->where('page_id', $pageId);
    }

    public function scopeForBlock($query, string $blockId)
    {
        return $query->where('block_id', $blockId);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    // ============ HELPERS ============

    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsUnread(): bool
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Get a specific field from form data.
     */
    public function getField(string $name, $default = null)
    {
        return $this->form_data[$name] ?? $default;
    }
}
