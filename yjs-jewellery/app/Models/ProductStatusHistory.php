<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product Status History Model
 *
 * Tracks product status changes.
 */
class ProductStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'product_status_history';

    protected $fillable = [
        'product_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Log a status change.
     */
    public static function log(int $productId, ?string $fromStatus, string $toStatus, ?string $reason = null): self
    {
        return self::create([
            'product_id' => $productId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'changed_by' => auth()->id(),
        ]);
    }
}
