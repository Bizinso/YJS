<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class EmployeeAddress extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'employee_addresses';

    protected $fillable = [
        'user_id',
        'address_line',
        'city',
        'state',
        'postal_code',
        'country_id',
        'is_default',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('EmployeeAddress')
            ->logAll()                      // log all fields
            ->logOnlyDirty()                // only changed fields
            ->dontSubmitEmptyLogs()         // avoid empty log entries
            ->setDescriptionForEvent(fn (string $event) =>
                "Employee Address has been {$event}"
            );
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city');
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $this->loadMissing([
            'user',
            'country',
            'stateRelation',
            'cityRelation'
        ]);

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
        if (isset($data['user_id'])) {
            $data['user'] = $this->user?->name;
            unset($data['user_id']);
        }

        if (isset($data['country_id'])) {
            $data['country'] = $this->country?->name;
            unset($data['country_id']);
        }

        if (isset($data['state'])) {
            $data['state'] = $this->state?->name;
        }

        if (isset($data['city'])) {
            $data['city'] = $this->city?->name;
        }

        if (isset($data['is_default'])) {
            $data['is_default'] = $data['is_default'] ? 'Yes' : 'No';
        }

        return $data;
    }
}
