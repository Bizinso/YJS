<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoAudit extends Model
{
    protected $table = 'seo_audits';

    protected $fillable = [
        'page_id',
        'score',
        'issues',
        'suggestions',
        'analysis_data',
        'last_audit_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'issues' => 'array',
        'suggestions' => 'array',
        'analysis_data' => 'array',
        'last_audit_at' => 'datetime',
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

    public function scopeGoodScore($query, int $minScore = 70)
    {
        return $query->where('score', '>=', $minScore);
    }

    public function scopeNeedsImprovement($query)
    {
        return $query->where('score', '<', 70);
    }

    // ============ HELPERS ============

    /**
     * Get score grade (A, B, C, D, F).
     */
    public function getGrade(): string
    {
        return match (true) {
            $this->score >= 90 => 'A',
            $this->score >= 80 => 'B',
            $this->score >= 70 => 'C',
            $this->score >= 60 => 'D',
            default => 'F',
        };
    }

    /**
     * Get score color for UI.
     */
    public function getScoreColor(): string
    {
        return match (true) {
            $this->score >= 80 => 'success',
            $this->score >= 60 => 'warning',
            default => 'danger',
        };
    }

    /**
     * Check if audit is recent (within last 24 hours).
     */
    public function isRecent(): bool
    {
        if (!$this->last_audit_at) {
            return false;
        }

        return $this->last_audit_at->isAfter(now()->subDay());
    }

    /**
     * Get critical issues count.
     */
    public function getCriticalIssuesCount(): int
    {
        $issues = $this->issues ?? [];

        return collect($issues)->filter(fn($i) => ($i['severity'] ?? '') === 'critical')->count();
    }

    /**
     * Get issues grouped by severity.
     */
    public function getIssuesBySeverity(): array
    {
        $issues = $this->issues ?? [];

        return collect($issues)->groupBy('severity')->toArray();
    }

    /**
     * Run SEO audit for a page.
     */
    public static function auditPage(CmsPage $page): self
    {
        $analyzer = new \App\Services\Seo\SeoAnalyzer($page);
        $results = $analyzer->analyze();

        return static::updateOrCreate(
            ['page_id' => $page->id],
            [
                'score' => $results['score'],
                'issues' => $results['issues'],
                'suggestions' => $results['suggestions'],
                'analysis_data' => $results['data'],
                'last_audit_at' => now(),
            ]
        );
    }
}
