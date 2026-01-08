<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class PageAnalytics extends Model
{
    protected $table = 'page_analytics';

    protected $fillable = [
        'page_id',
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
        'avg_time_on_page' => 'decimal:2',
        'bounce_rate' => 'decimal:2',
    ];

    // ============ RELATIONSHIPS ============

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }

    // ============ SCOPES ============

    public function scopeForPage($query, int $pageId)
    {
        return $query->where('page_id', $pageId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeLastDays($query, int $days = 30)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }

    // ============ STATIC HELPERS ============

    /**
     * Record a page view.
     */
    public static function recordView(int $pageId, bool $isUnique = false): self
    {
        $today = now()->toDateString();

        $analytics = static::firstOrCreate(
            ['page_id' => $pageId, 'date' => $today],
            ['views' => 0, 'unique_visitors' => 0]
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
    public static function getTotalViews(int $pageId): int
    {
        return static::forPage($pageId)->sum('views');
    }

    /**
     * Get views for date range.
     */
    public static function getViewsForRange(int $pageId, $startDate, $endDate): array
    {
        return static::forPage($pageId)
            ->forDateRange($startDate, $endDate)
            ->orderBy('date')
            ->get()
            ->map(fn($a) => [
                'date' => $a->date->format('Y-m-d'),
                'views' => $a->views,
                'unique_visitors' => $a->unique_visitors,
            ])
            ->toArray();
    }

    /**
     * Get top pages by views.
     */
    public static function getTopPages(int $days = 30, int $limit = 10): array
    {
        return static::lastDays($days)
            ->selectRaw('page_id, SUM(views) as total_views, SUM(unique_visitors) as total_unique')
            ->groupBy('page_id')
            ->orderByDesc('total_views')
            ->limit($limit)
            ->with('page:id,title,slug')
            ->get()
            ->toArray();
    }
}
