<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product Import Job Model
 *
 * Tracks bulk product import operations.
 */
class ProductImportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'success_count',
        'error_count',
        'errors',
        'options',
        'created_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'options' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function start(): self
    {
        $this->status = self::STATUS_PROCESSING;
        $this->started_at = now();
        $this->save();
        return $this;
    }

    public function complete(): self
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->save();
        return $this;
    }

    public function fail(array $errors = []): self
    {
        $this->status = self::STATUS_FAILED;
        $this->errors = array_merge($this->errors ?? [], $errors);
        $this->completed_at = now();
        $this->save();
        return $this;
    }

    public function incrementProgress(bool $success = true): void
    {
        $this->processed_rows++;
        if ($success) {
            $this->success_count++;
        } else {
            $this->error_count++;
        }
        $this->save();
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->total_rows === 0) return 0;
        return round(($this->processed_rows / $this->total_rows) * 100, 1);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }
}
