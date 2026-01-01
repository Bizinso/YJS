<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class events extends Model
{
   use SoftDeletes, LogsActivity;

    protected $table = 'events';

    protected $fillable = [
        'title',
        'location',
        'start_date',
        'end_date',
        'description',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('events')
            ->logAll()                      // log all fields
            ->logOnlyDirty()                // only when fields change
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Event has been {$event}"
            );
    }
}
