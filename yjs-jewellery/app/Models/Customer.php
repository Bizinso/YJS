<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasApiTokens,SoftDeletes, LogsActivity;

    protected $table = 'customers';

    protected $fillable = [
        'user_id',
        'gender',
        'dob',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Customer')
            ->logAll()                      // log all columns
            ->logOnlyDirty()                // log only changed fields
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Customer has been {$event}"
            );
    }
     public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
     public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Get the default address for the customer.
     */
    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
    }
}
