<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Request Status History Model
 *
 * Unified status history tracking for returns, exchanges, and cancellations.
 *
 * @property int $id
 * @property string $request_type
 * @property int $request_id
 * @property string|null $from_status
 * @property string $to_status
 * @property string|null $notes
 * @property int|null $changed_by
 */
class RequestStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'request_status_history';

    protected $fillable = [
        'request_type',
        'request_id',
        'from_status',
        'to_status',
        'notes',
        'changed_by',
    ];

    const TYPE_RETURN = 'return';
    const TYPE_EXCHANGE = 'exchange';
    const TYPE_CANCELLATION = 'cancellation';

    /**
     * Get the user who made the change
     */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get the associated request based on type
     */
    public function getRequest()
    {
        switch ($this->request_type) {
            case self::TYPE_RETURN:
                return ReturnRequest::find($this->request_id);
            case self::TYPE_EXCHANGE:
                return ExchangeRequest::find($this->request_id);
            case self::TYPE_CANCELLATION:
                return CancellationRequest::find($this->request_id);
            default:
                return null;
        }
    }

    /**
     * Get status change description
     */
    public function getDescriptionAttribute(): string
    {
        $from = $this->from_status ? "from '{$this->from_status}'" : '';
        $to = "to '{$this->to_status}'";

        return trim("Status changed {$from} {$to}");
    }

    /**
     * Get changed by name
     */
    public function getChangedByNameAttribute(): string
    {
        if ($this->changedByUser) {
            return $this->changedByUser->name ?? $this->changedByUser->email;
        }
        return 'System';
    }

    /**
     * Scope for return requests
     */
    public function scopeForReturns($query)
    {
        return $query->where('request_type', self::TYPE_RETURN);
    }

    /**
     * Scope for exchange requests
     */
    public function scopeForExchanges($query)
    {
        return $query->where('request_type', self::TYPE_EXCHANGE);
    }

    /**
     * Scope for cancellation requests
     */
    public function scopeForCancellations($query)
    {
        return $query->where('request_type', self::TYPE_CANCELLATION);
    }

    /**
     * Scope for specific request
     */
    public function scopeForRequest($query, string $type, int $requestId)
    {
        return $query->where('request_type', $type)
            ->where('request_id', $requestId)
            ->orderBy('created_at', 'desc');
    }
}
