<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class offers extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'offers';

    protected $fillable = [
        'title',
        'description',
        'offer_type_id',
        'offer_type_option',
        'discount_type',
        'discount_amount',
        'discount_percent',
        'max_discount_amount',
        'details',
        'apply_on',
        'apply_on_value',
        'valid_from',
        'valid_to',
        'status',
        'coupon_code',
        'created_by',
    ];

    protected $casts = [
        'details' => 'array',
        'apply_on_value' => 'array',
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('offers')
            ->logAll()                       // log all fields
            ->logOnlyDirty()                 // log only changed fields
            ->dontSubmitEmptyLogs()          // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Offer has been {$event}"
            );
    }

}
