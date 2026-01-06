<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saved Report Model
 *
 * Stores saved report configurations.
 */
class SavedReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'filters',
        'columns',
        'format',
        'is_public',
        'created_by',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'is_public' => 'boolean',
    ];

    // Report Types
    const TYPE_SALES = 'sales';
    const TYPE_ORDERS = 'orders';
    const TYPE_INVENTORY = 'inventory';
    const TYPE_CUSTOMERS = 'customers';
    const TYPE_PRODUCTS = 'products';
    const TYPE_FINANCE = 'finance';
    const TYPE_RETURNS = 'returns';
    const TYPE_PARTNERS = 'partners';

    /**
     * Relationships
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopePublicReports($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('created_by', $userId)->orWhere('is_public', true);
        });
    }
}
