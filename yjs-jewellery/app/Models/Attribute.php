<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Attribute extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'attributes';

    protected $fillable = [
        'name',
        'description',
        'data_type',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Attributes')
            ->logAll()                      // log all columns
            ->logOnlyDirty()                // log only changed fields
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Attribute has been {$event}"
            );
    }

    public function dataTypeOption()
    {
        return $this->belongsTo(AttributeOption::class, 'data_type');
    }

    /**
     * 🔹 Replace data_type ID with option name in activity logs
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $this->loadMissing('dataTypeOption');

        $properties = $activity->properties->toArray();

        // New values
        if (isset($properties['attributes'])) {
            $properties['attributes'] = $this->replaceDataType(
                $properties['attributes']
            );
        }

        // Old values
        if (isset($properties['old'])) {
            $properties['old'] = $this->replaceDataType(
                $properties['old']
            );
        }

        $activity->properties = $properties;
    }

    /**
     * 🔹 Helper to transform ID → Name
     */
    protected function replaceDataType(array $data): array
    {
        if (isset($data['data_type'])) {
            $data['data_type'] = $this->dataTypeOption?->name;
        }

        return $data;
    }
}
