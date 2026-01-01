<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AdditionalCharges extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'additional_charges';

    protected $fillable = [
        'name',
        'type',
        'value',
        'is_active',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Additional Charge')
            ->logAll()                     // log all attributes
            ->logOnlyDirty()               // only log changed fields
            ->dontSubmitEmptyLogs()        // do not store empty logs
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Additional Charge has been {$eventName}"
            );
    }

    public static function getAdditionalChargesInfo($chargesId)
    {
        $additionalCharges = AdditionalCharges::
        leftjoin('additional_charge_types','additional_charge_types.id','additional_charges.type_id')
        ->select(
            'additional_charge_types.name as Name',
            'additional_charges.charges_type as Charge Type',
            'additional_charges.amount as Amount',
            'additional_charges.description as Description',
            'additional_charges.created_at as Created At',
            'additional_charges.updated_at as Updated At',
        )
            ->where('additional_charges.id', $chargesId)
            ->first();

        if (! $additionalCharges) {
            return null;
        }
        $attributes = $additionalCharges->getAttributes();
        foreach ($attributes as $key => $value) {
            if (is_null($value)) {
                unset($attributes[$key]);
            }
        }

        return (object) $attributes;

    }
}
