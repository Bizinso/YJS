<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AdditionalChargeType extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'additional_charge_types';

    protected $fillable = [
        'name',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Additional Charge Type')
            ->logAll()                      // log all columns
            ->logOnlyDirty()                // log only changed fields
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Additional Charge Type has been {$event}"
            );
    }
}
