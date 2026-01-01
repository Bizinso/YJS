<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class blogCategory extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'blog_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'status',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('blogCategory')
            ->logAll()                     // Log all attributes
            ->logOnlyDirty()               // Only log changed fields
            ->dontSubmitEmptyLogs()        // Avoid empty logs
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Blog Category has been {$eventName}"
            );
    }
}
