<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class order extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'orders';

    protected $fillable = [
        'custom_order_code',
        'order_date',
        'customer_type',
        'customer_id',
        'email',
        'country_code',
        'country_id',
        'billing_address_id',
        'shipping_address_id',
        'shipping_method',
        'delivery_date',
        'shipping_charges',
        'notes',
        'order_status',
        'payment_status',
        'gst_applied',
        'gst_percentage',
        'coupon_code',
        'total_summary',
        'add_to_cart',
        'shipment_id',
        'shiprocket_order_id',
        'shipping_status',
        'awb_number',
        'pickup_scheduled_date',
        'courier_id',
        'courier_charges',
        'courier_name',
        'payment_method',
        'total_charges',
        'total_taxes',
        'order_subtotal',
        'order_total',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'pickup_scheduled_date' => 'datetime',
        'shipping_charges' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'total_summary' => 'decimal:2',
        'total_charges' => 'decimal:2',
        'total_taxes' => 'decimal:2',
        'order_subtotal' => 'decimal:2',
        'order_total' => 'decimal:2',
        'add_to_cart' => 'boolean',
    ];

    // Activity Log configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('order')
            ->logAll()                       // log all columns
            ->logOnlyDirty()                 // log only changed fields
            ->dontSubmitEmptyLogs()          // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Order has been {$event}"
            );
    }

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'billing_address_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'shipping_address_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }
}
