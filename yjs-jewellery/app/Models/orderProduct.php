<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class orderProduct extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'order_products';

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'discount',
        'tax',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('orderProduct')
            ->logAll()                       // log all attributes
            ->logOnlyDirty()                 // log only changed fields
            ->dontSubmitEmptyLogs()          // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Order Product has been {$event}"
            );
    }

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
