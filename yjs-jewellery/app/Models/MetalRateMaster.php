<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MetalRateMaster extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'metal_rate_masters';

    protected $fillable = [
        'metal_type_id',
        'rate_per_gram',
        'effective_date_from',
        'effective_date_to',
    ];

    protected $casts = [
        'rate_per_gram' => 'decimal:2',
        'effective_date_from' => 'date',
        'effective_date_to' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('MetalRateMaster')
            ->logAll()                      // log all fields
            ->logOnlyDirty()                // log only changed fields
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Metal Rate Master has been {$event}"
            );
    }
}
