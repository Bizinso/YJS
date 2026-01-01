<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class AttributeValue extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'attribute_values';

    protected $fillable = [
        'attribute_id',
        'value',
        'display_order',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Attribute Values')
            ->logAll()                      // log all columns
            ->logOnlyDirty()                // log only changed fields
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Attribute Value has been {$event}"
            );
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * 🔹 Convert attribute_id → attribute name in logs
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $this->loadMissing('attribute');

        $properties = $activity->properties->toArray();

        // NEW VALUES
        if (isset($properties['attributes'])) {
            $properties['attributes'] = $this->transformIdsToNames(
                $properties['attributes']
            );
        }

        // OLD VALUES
        if (isset($properties['old'])) {
            $properties['old'] = $this->transformIdsToNames(
                $properties['old']
            );
        }

        $activity->properties = $properties;
    }

    /**
     * 🔹 Helper: Replace IDs with Names
     */
    protected function transformIdsToNames(array $data): array
    {
        if (isset($data['attribute_id'])) {
            $data['attribute'] = $this->attribute?->name;
            unset($data['attribute_id']);
        }

        return $data;
    }
}
