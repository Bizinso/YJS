<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentTracking extends Model
{
    protected $fillable = [
        'order_id',
        'shiprocket_order_id',
        'shipment_id',
        'awb_code',
        'courier_company_id',
        'courier_name',
        'current_status',
        'current_status_id',
        'current_location',
        'activities',
        'etd',
        'is_delivered',
        'is_rto',
        'pickup_scheduled_date',
        'last_synced_at',
    ];

    protected $casts = [
        'activities' => 'array',
        'is_delivered' => 'boolean',
        'is_rto' => 'boolean',
        'pickup_scheduled_date' => 'date',
        'last_synced_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getFormattedActivitiesAttribute(): array
    {
        return collect($this->activities ?? [])->map(function ($activity) {
            return [
                'date' => $activity['date'] ?? '',
                'status' => $activity['status'] ?? '',
                'location' => $activity['location'] ?? '',
            ];
        })->toArray();
    }
}
