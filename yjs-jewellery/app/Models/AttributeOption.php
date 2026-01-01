<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AttributeOption extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'attribute_options';

    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Attribute Options')
            ->logAll()                      // log all columns
            ->logOnlyDirty()                // log only changed fields
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Attribute Option has been {$event}"
            );
    }
}
