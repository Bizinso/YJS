<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FormSubmission Model - Contact Form Submissions
 *
 * @property int $id
 * @property int $page_id
 * @property int|null $version_id
 * @property string $widget_id
 * @property array $form_data
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property bool $is_read
 * @property \Carbon\Carbon|null $read_at
 */
class FormSubmission extends Model
{
    protected $table = 'form_submissions';

    protected $fillable = [
        'page_id',
        'version_id',
        'widget_id',
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
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PageVersion::class, 'version_id');
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

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ============ HELPERS ============

    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

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
     * Get form data as key-value pairs.
     */
    public function getFormData(): array
    {
        return $this->form_data ?? [];
    }

    /**
     * Get a specific form field value.
     */
    public function getField(string $key, $default = null)
    {
        return $this->form_data[$key] ?? $default;
    }

    /**
     * Export submission as array for CSV.
     */
    public function toExportArray(): array
    {
        return array_merge(
            [
                'id' => $this->id,
                'page' => $this->page?->slug,
                'submitted_at' => $this->created_at->toDateTimeString(),
                'ip_address' => $this->ip_address,
                'is_read' => $this->is_read ? 'Yes' : 'No',
            ],
            $this->getFormData()
        );
    }
}
