<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Cart Model
 *
 * Represents a shopping cart item with product, quantity,
 * pricing calculations, and offer tracking.
 *
 * @package App\Models
 */
class Cart extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'product_base_price',
        'charges_total',
        'tax_total',
        'total_discount',
        'final_price',
        'cart_total',
        'applied_offers',
        'selected_free_products',
        'estimated_delivery',
    ];

    protected $table = 'carts';

    protected $casts = [
        'applied_offers' => 'array',
        'selected_free_products' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Cart')
            ->logAll()                       // log all attributes
            ->logOnlyDirty()                 // only log changes
            ->dontSubmitEmptyLogs()          // skip empty logs
            ->setDescriptionForEvent(fn(string $event) => "Cart has been {$event}");
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }


}
