<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class offerTypeMaster extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'offer_type_masters';

    protected $fillable = [
        'offer_type',
        'offer_type_option',
        'description',
        'apply_to',
        'apply_to_option',
        'status',
    ];

    protected $casts = [
        'offer_type_option' => 'array',  // store JSON as array
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('offerTypeMaster')
            ->logAll()                       // log all fields
            ->logOnlyDirty()                 // log only changed fields
            ->dontSubmitEmptyLogs()          // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Offer Type Master has been {$event}"
            );
    }
}
