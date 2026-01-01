<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

class brochures extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'brochures';

    protected $fillable = [
        'name',
        'url',
        'slug',
        'title',
        'subtitle',
        'description',
        'images',
    ];

    protected $casts = [
        'images' => 'array',   // JSON cast
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('brochures')
            ->logAll()              // log all fields
            ->logOnlyDirty()        // only changed values
            ->dontSubmitEmptyLogs() // avoid empty logs
            ->setDescriptionForEvent(
                fn(string $eventName) =>
                    "Brochure record has been {$eventName}"
            );
    }
}
