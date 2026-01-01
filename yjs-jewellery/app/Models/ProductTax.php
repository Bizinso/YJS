<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
class ProductTax extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'product_taxes';

    protected $fillable = [
        'product_id',
        'tax_application',
        'type',
        'value',
        'amount',
        'primary_cost',
        'description'
    ];

    /**
     * Configure Spatie activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('ProductTax')
            ->logAll()                       // log all attributes
            ->logOnlyDirty()                 // log only changed attributes
            ->dontSubmitEmptyLogs()          // skip empty logs
            ->setDescriptionForEvent(fn(string $event) => "Product tax has been {$event}");
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
            $data['product_name'] = $this->product?->name; // show product name
            unset($data['product_id']);
        }

        return $data;
    }

}
