<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class blogs extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'blogs';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'image',
        'status',
        'category_id',
        'subcategory_id',
        'user_id',
        'seo_master_table_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('blogs')
            ->logAll()                          // log all fields
            ->logOnlyDirty()                    // only changed fields
            ->dontSubmitEmptyLogs()             // no empty logs
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Blog record has been {$eventName}"
            );
    }
}
