<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
class MetalType extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'metal_types';

    protected $fillable = [
        'metal_name_id',
        'name',
        'purity_id',
        'description',
        'color',
        'density',
        'price_per_gram',
        'status',
    ];

    protected $casts = [
        'density' => 'decimal:3',
        'price_per_gram' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('MetalType')
            ->logAll()                      // log all fields
            ->logOnlyDirty()                // log only changed fields
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Metal Type has been {$event}"
            );
    }

    public static function getMetalTypeInfo($metalTypeId)
    {
        $metalType = MetalType::leftJoin('purities', 'purities.id', '=', 'metal_types.purity_id')
            ->select(
                'metal_types.name as Name',
                'metal_types.purity_id',
                'purities.name as purity_name',
                'metal_types.color as Color',
                'metal_types.density as Density',
                'metal_types.price_per_gram as Price Per Gram',
                'metal_types.status as Status',
                'metal_types.description as Description',
                'metal_types.created_at as Created At',
                'metal_types.updated_at as Updated At',
            )
            ->where('metal_types.id', $metalTypeId)
            ->first();

        if (! $metalType) {
            return null;
        }
        $attributes = $metalType->getAttributes();
        foreach ($attributes as $key => $value) {
            if (is_null($value)) {
                unset($attributes[$key]);
            }
        }

        return (object) $attributes;
    }

    public function metalName()
    {
        return $this->belongsTo(MetalName::class, 'metal_name_id');
    }

    public function purity()
    {
        return $this->belongsTo(Purity::class, 'purity_id');
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $this->loadMissing(['metalName', 'purity']);

        $properties = $activity->properties->toArray();

        foreach (['attributes', 'old'] as $key) {
            if (isset($properties[$key])) {
                $properties[$key] = $this->mapReadableValues($properties[$key]);
            }
        }

        $activity->properties = $properties;
    }

    protected function mapReadableValues(array $data): array
    {
        if (isset($data['metal_name_id'])) {
            $data['metal_name'] = $this->metalName?->name;
            unset($data['metal_name_id']);
        }

        if (isset($data['purity_id'])) {
            $data['purity'] = $this->purity?->name;
            unset($data['purity_id']);
        }

        return $data;
    }

}
