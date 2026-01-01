<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class ProductMedia extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'product_media';

    protected $fillable = [
        'product_id', 
        'media_type',
        'file_url',
        'product_variant_id',
    ];

   
    protected $casts = [
        'media_type' => 'string',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ProductMedia')
            ->logAll()                       // log all attributes
            ->logOnlyDirty()                 // only log changes
            ->dontSubmitEmptyLogs()          // skip empty logs
            ->setDescriptionForEvent(fn(string $event) => "ProductMedia has been {$event}");
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $this->loadMissing('product');

        $properties = $activity->properties->toArray();

        foreach (['attributes', 'old'] as $key) {
            if (isset($properties[$key])) {
                $properties[$key] = $this->mapReadableValues($properties[$key]);
            }
        }

        $activity->properties = $properties;
    }

    protected function mapReadableValues(array $data): array
    {
        if (isset($data['product_id'])) {
            $data['product_name'] = $this->product?->name; // replace ID with name
            unset($data['product_id']);
        }

        return $data;
    }

}
