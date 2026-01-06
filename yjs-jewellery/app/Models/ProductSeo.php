<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product SEO Model
 *
 * SEO metadata for products.
 */
class ProductSeo extends Model
{
    use HasFactory;

    protected $table = 'product_seo';

    protected $fillable = [
        'product_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
        'robots',
        'schema_markup',
    ];

    protected $casts = [
        'schema_markup' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Generate default SEO from product data.
     */
    public static function generateFromProduct(Product $product): self
    {
        return self::updateOrCreate(
            ['product_id' => $product->id],
            [
                'meta_title' => substr($product->product_title, 0, 70),
                'meta_description' => substr(strip_tags($product->short_description ?? $product->product_title), 0, 160),
                'og_title' => $product->product_title,
                'og_description' => substr(strip_tags($product->short_description ?? ''), 0, 200),
            ]
        );
    }
}
