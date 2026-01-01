<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Branch extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'branches';

    protected $fillable = [
        'name',
        'slug',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Branch')
            ->logAll()                      // log all columns
            ->logOnlyDirty()                // log only changed fields
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Branch has been {$event}"
            );
    }
}
