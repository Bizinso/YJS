<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class MetalName extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'metal_names';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('MetalName')
            ->logAll()                      // log all fields
            ->logOnlyDirty()                // log only changed fields
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Metal Name has been {$event}"
            );
    }
}
