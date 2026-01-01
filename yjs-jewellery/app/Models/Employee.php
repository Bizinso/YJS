<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Log;
class Employee extends Model
{   
    use HasApiTokens, SoftDeletes, LogsActivity;

    protected $fillable = [
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'user_id',
        'department_id',
        'branch_id',
        'role_id',
        'password',
        'status',
        'email_verified_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'employee_code',
                'first_name',
                'last_name',
                'email',
                'phone',
                'user_id',
                'department_id',
                'branch_id',
                'role_id',
                'status'
            ])
            ->useLogName('employee')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "Employee record has been {$eventName}";
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function userrole()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName)
    {
        $this->loadMissing(['user', 'department', 'branch', 'userrole']);

        $properties = $activity->properties->toArray();

        // NEW VALUES
        if (isset($properties['attributes'])) {
            $properties['attributes'] = $this->transformIdsToNames(
                $properties['attributes']
            );
        }

        // OLD VALUES
        if (isset($properties['old'])) {
            $properties['old'] = $this->transformIdsToNames(
                $properties['old']
            );
        }

        $activity->properties = $properties;
    }

    /**
     * 🔹 Convert IDs to Names
     */
    protected function transformIdsToNames(array $data): array
    {
        if (isset($data['user_id'])) {
            Log::info($data['user_id']);
            $data['user'] = $this->user?->first_name .' '.$this->user?->last_name;
            unset($data['user_id']);
        }

        if (isset($data['department_id'])) {
            $data['department'] = $this->department?->name;
            unset($data['department_id']);
        }

        if (isset($data['branch_id'])) {
            $data['branch'] = $this->branch?->name;
            unset($data['branch_id']);
        }

        if (isset($data['role_id'])) {
            $data['role'] = $this->userrole?->name;
            unset($data['role_id']);
        }

        return $data;
    }



}
