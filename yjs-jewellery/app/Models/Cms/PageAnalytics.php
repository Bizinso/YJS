<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PageAnalytics Model - Page View Tracking
 *
 * @property int $id
 * @property int $page_id
 * @property int|null $version_id
 * @property \Carbon\Carbon $date
 * @property int $views
 * @property int $unique_visitors
 * @property int $avg_time_on_page
 * @property float $bounce_rate
 */
class PageAnalytics extends Model
{
    protected $table = 'page_analytics';

    protected $fillable = [
        'page_id',
        'version_id',
        'date',
        'views',
        'unique_visitors',
        'avg_time_on_page',
        'bounce_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'unique_visitors' => 'integer',
        'avg_time_on_page' => 'integer',
        'bounce_rate' => 'decimal:2',
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

    public function scopeForPage($query, int $pageId)
    {
        return $query->where('page_id', $pageId);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->where('date', now()->toDateString());
    }

    public function scopeLastDays($query, int $days)
    {
        return $query->where('date', '>=', now()->subDays($days)->toDateString());
    }

    // ============ HELPERS ============

    /**
     * Record a page view.
     */
    public static function recordView(Page $page, bool $isUnique = false): self
    {
        $analytics = static::firstOrCreate(
            [
                'page_id' => $page->id,
                'version_id' => $page->published_version_id,
                'date' => now()->toDateString(),
            ],
            [
                'views' => 0,
                'unique_visitors' => 0,
                'avg_time_on_page' => 0,
                'bounce_rate' => 0,
            ]
        );

        $analytics->increment('views');

        if ($isUnique) {
            $analytics->increment('unique_visitors');
        }

        return $analytics;
    }

    /**
     * Get total views for a page.
     */
    public static function getTotalViews(int $pageId, ?int $days = null): int
    {
        $query = static::forPage($pageId);

        if ($days) {
            $query->lastDays($days);
        }

        return $query->sum('views');
    }

    /**
     * Get popular pages.
     */
    public static function getPopularPages(int $limit = 10, int $days = 30): array
    {
        return static::query()
            ->selectRaw('page_id, SUM(views) as total_views, SUM(unique_visitors) as total_unique')
            ->lastDays($days)
            ->groupBy('page_id')
            ->orderByDesc('total_views')
            ->limit($limit)
            ->with('page')
            ->get()
            ->toArray();
    }

    /**
     * Get analytics summary for a page.
     */
    public static function getSummary(int $pageId, int $days = 30): array
    {
        $data = static::forPage($pageId)->lastDays($days)->get();

        return [
            'total_views' => $data->sum('views'),
            'unique_visitors' => $data->sum('unique_visitors'),
            'avg_time' => $data->avg('avg_time_on_page'),
            'avg_bounce_rate' => $data->avg('bounce_rate'),
            'daily' => $data->sortBy('date')->values()->toArray(),
        ];
    }
}
