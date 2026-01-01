<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'sku',
        'slug',
        'description',
        'category_id',
        'purity_id',
        'gemstone_id',
        'metal_weight',
        'metal_rate',
        'metal_value',
        'base_price',
        'subtotal',
        'final_price',
        'initial_stock',
        'available_stock',
        'is_featured',
        'visibility',
        'views_count',
        'collection',
        'tags_id',
        'attributes',
        'main_image',
        'other_images',
        'video_url',
        'seo_title',
        'meta_description',
        'meta_slug_url',
        'status',
    ];

     protected $casts = [
        'variant_options' => 'array',
        'variant_attribute_id' => 'array',
        'tags_id' => 'array',
        'related_product_ids' => 'array',
        'you_may_like_product_ids' => 'array',
        'visible_partner_ids' => 'array',
    ];

    /**
     * Spatie activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Product')
            ->logAll()                       // log all attributes
            ->logOnlyDirty()                 // only log changes
            ->dontSubmitEmptyLogs()          // skip empty logs
            ->setDescriptionForEvent(fn(string $event) => "Product has been {$event}");
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function purity()
    {
        return $this->belongsTo(Purity::class, 'purity_id');
    }

    public function taxCharges()
    {
        return $this->hasMany(ProductTax::class);
    }

    public function charges()
    {   
        return $this->hasMany(ProductCharges::class);
    }

    public function media()
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function childrenproducts()
    {
        return $this->hasMany(Product::class, 'parent_id')
                    ->with('attributes'); // eager load attributes
    }

    public function attributes()
    {
        return $this->hasMany(ProductVariant::class, 'product_id'); // product_variants table
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'sub_category_id', 'id');
    }

    public function variant()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
