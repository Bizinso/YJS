<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class Purity extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'purities';

    protected $fillable = [
        'name',
        'karat_value',
        'percentage',
        'description',
        'status',
    ];

    /**
     * Spatie activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Purity')
            ->logAll()                       // log all attributes
            ->logOnlyDirty()                 // only log changes
            ->dontSubmitEmptyLogs()          // skip empty logs
            ->setDescriptionForEvent(fn(string $event) => "Purity has been {$event}");
    }
}
