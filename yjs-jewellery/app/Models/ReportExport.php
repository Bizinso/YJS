<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Report Export Model
 *
 * Tracks report export history.
 */
class ReportExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'format',
        'filters',
        'file_path',
        'status',
        'error_message',
        'row_count',
        'file_size',
        'created_by',
        'scheduled_report_id',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // Formats
    const FORMAT_XLSX = 'xlsx';
    const FORMAT_CSV = 'csv';
    const FORMAT_PDF = 'pdf';

    /**
     * Relationships
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scheduledReport(): BelongsTo
    {
        return $this->belongsTo(ScheduledReport::class);
    }

    /**
     * Mark as processing.
     */
    public function markProcessing(): self
    {
        $this->status = self::STATUS_PROCESSING;
        $this->save();
        return $this;
    }

    /**
     * Mark as completed.
     */
    public function markCompleted(string $filePath, int $rowCount, int $fileSize): self
    {
        $this->status = self::STATUS_COMPLETED;
        $this->file_path = $filePath;
        $this->row_count = $rowCount;
        $this->file_size = $fileSize;
        $this->save();
        return $this;
    }

    /**
     * Mark as failed.
     */
    public function markFailed(string $error): self
    {
        $this->status = self::STATUS_FAILED;
        $this->error_message = $error;
        $this->save();
        return $this;
    }

    /**
     * Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }
}
