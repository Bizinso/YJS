<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use Illuminate\Database\Eloquent\Model;

class enquiry extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'enquiries';

    protected $fillable = [
        'partner_id',
        'assigned_to',
        'status',
        'remarks',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('enquiry')
            ->logAll()                      // log every field
            ->logOnlyDirty()                // only changed values
            ->dontSubmitEmptyLogs()         // ignore empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Enquiry has been {$event}"
            );
    }

    // Relationships
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

}
