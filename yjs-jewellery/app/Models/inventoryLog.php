<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class inventoryLog extends Model
{
    use SoftDeletes, LogsActivity;
    protected $table = 'inventory_logs';

    protected $fillable = [
        'product_id',
        'quantity',
        'action',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'created_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('inventoryLog')
            ->logAll()                      // log all fields
            ->logOnlyDirty()                // log only changed fields
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Inventory Log has been {$event}"
            );
    }

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
