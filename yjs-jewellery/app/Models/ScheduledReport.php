<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Scheduled Report Model
 *
 * Manages automated report scheduling.
 */
class ScheduledReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'filters',
        'columns',
        'frequency',
        'day_of_week',
        'day_of_month',
        'time_of_day',
        'recipients',
        'export_format',
        'is_active',
        'last_run_at',
        'next_run_at',
        'created_by',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    // Frequencies
    const FREQ_DAILY = 'daily';
    const FREQ_WEEKLY = 'weekly';
    const FREQ_MONTHLY = 'monthly';

    /**
     * Relationships
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class, 'scheduled_report_id');
    }

    /**
     * Calculate next run time.
     */
    public function calculateNextRun(): void
    {
        $now = now();

        $this->next_run_at = match ($this->frequency) {
            self::FREQ_DAILY => $now->addDay()->setTimeFromTimeString($this->time_of_day),
            self::FREQ_WEEKLY => $now->next($this->day_of_week)->setTimeFromTimeString($this->time_of_day),
            self::FREQ_MONTHLY => $now->addMonth()->day($this->day_of_month)->setTimeFromTimeString($this->time_of_day),
            default => $now->addDay(),
        };

        $this->save();
    }

    /**
     * Mark as run.
     */
    public function markRun(): void
    {
        $this->last_run_at = now();
        $this->calculateNextRun();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->active()
            ->where('next_run_at', '<=', now());
    }
}
