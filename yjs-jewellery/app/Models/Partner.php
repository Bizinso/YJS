<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class Partner extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'partners';

    protected $fillable = [
        'user_id',
        'business_name',
        'business_type',
        'phone_number',
        'gst_number',
        'address',
        'city',
        'state',
        'status',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Partner')
            ->logAll()                       // log all attributes
            ->logOnlyDirty()                 // log only changed attributes
            ->dontSubmitEmptyLogs()          // skip empty logs
            ->setDescriptionForEvent(fn(string $event) => "Partner has been {$event}");
    }
      public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
