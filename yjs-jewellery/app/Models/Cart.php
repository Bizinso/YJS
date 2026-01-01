<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Cart extends Model
{
    use SoftDeletes, LogsActivity;

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
