<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class pages extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'pages';

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
        'images' => 'array', // cast JSON to array
    ];

   
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('pages')
            ->logAll()                       // log all attributes
            ->logOnlyDirty()                 // log only changed attributes
            ->dontSubmitEmptyLogs()          // skip empty logs
            ->setDescriptionForEvent(fn(string $event) => "Page has been {$event}");
    }
}
