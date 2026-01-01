<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
class Department extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'departments';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'branch_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Department')
            ->logAll()                      // log all fields
            ->logOnlyDirty()                // only changed columns
            ->dontSubmitEmptyLogs()         // avoid empty logs
            ->setDescriptionForEvent(fn (string $event) =>
                "Department has been {$event}"
            );
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $this->loadMissing('branch');

        $properties = $activity->properties->toArray();

        // New values
        if (isset($properties['attributes'])) {
            $properties['attributes'] = $this->replaceBranch(
                $properties['attributes']
            );
        }

        // Old values
        if (isset($properties['old'])) {
            $properties['old'] = $this->replaceBranch(
                $properties['old']
            );
        }

        $activity->properties = $properties;
    }

    /**
     * 🔹 Helper: branch_id → branch name
     */
    protected function replaceBranch(array $data): array
    {
        if (isset($data['branch_id'])) {
            $data['branch'] = $this->branch?->name;
            unset($data['branch_id']);
        }

        return $data;
    }
}
