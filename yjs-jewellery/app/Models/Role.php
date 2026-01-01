<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class Role extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'department_id',
    ];

    /**
     * Spatie activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Role')
            ->logAll()                       // log all attributes
            ->logOnlyDirty()                 // only log changed fields
            ->dontSubmitEmptyLogs()          // skip empty logs
            ->setDescriptionForEvent(fn(string $event) => "Role has been {$event}");
    }
}
