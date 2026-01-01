<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';

    protected $fillable = ['id', 'log_name', 'description', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'properties', 'event']; // phpcs:ignore

    public static function logCreation($model, $customData, $action = 'Created',)
    {
        $userId = Auth::guard('api')->user() ? Auth::guard('api')->user()->id : null;
        if ($userId === null) {
            Log::warning('User not authenticated for logging creation of '.class_basename($model));

            return;
        }
        $request = request();
        $ipAddress = $request->ip();
        $agent = new Agent;
        $browser = $agent->browser();
        $platform = $agent->platform();
        $device = $agent->device();
        $logEntry = new ActivityLog;
        $logEntry->log_name = class_basename($model);
        $logEntry->description = $action.' '.class_basename($model);
        $logEntry->subject_type = get_class($model);
        $logEntry->subject_id = $model->getKey();
        $logEntry->causer_type = 'App\Models\User';
        $logEntry->causer_id = $userId;
        $logEntry->event = strtolower($action);
        $logEntry->ip_address = $ipAddress;
        $logEntry->device_platform = $platform;
        $logEntry->device_type = $device;
        $logEntry->device_browser = $browser;
        $logEntry->properties = json_encode(['attributes' => (array) $customData]);
        $logEntry->created_at = Carbon::now();
        $logEntry->updated_at = Carbon::now();
        $logEntry->save();
    }

    public static function logUpdate($model, $oldData, $newData)
    {

        $request = request();
        $ipAddress = $request->ip();
        $agent = new Agent;
        $browser = $agent->browser();
        $platform = $agent->platform();
        $device = $agent->device();

        $userId = Auth::guard('api')->user()->id;
        $keyOld = 'old';
        $oldDataWithKey = [$keyOld => $oldData];
        $keyNew = 'attributes';
        $newDataWithKey = [$keyNew => $newData];
        $bothDataWithKey = $newDataWithKey + $oldDataWithKey;
        $jsonData = json_encode($bothDataWithKey);
        $logEntry = new ActivityLog;
        $logEntry->log_name = class_basename($model);
        $logEntry->description = 'Updated '.class_basename($model);
        $logEntry->subject_type = get_class($model);
        $logEntry->subject_id = $model->id;
        $logEntry->causer_type = 'App\Models\User';
        $logEntry->causer_id = $userId;
        $logEntry->properties = $jsonData;
        $logEntry->event = 'updated';
        $logEntry->ip_address = $ipAddress;
        $logEntry->device_platform = $platform;
        $logEntry->device_type = $device;
        $logEntry->device_browser = $browser;
        $logEntry->created_at = Carbon::now();
        $logEntry->updated_at = Carbon::now();
        $logEntry->save();
    }


    public static function getActivityInfo($modelClass, $id, $select = ['*'], $joins = [])
    {
        if (!class_exists($modelClass)) {
            throw new \InvalidArgumentException("Model class $modelClass does not exist.");
        }

        $modelInstance = new $modelClass;
        $primaryKey = $modelInstance->getKeyName();
        $tableName = $modelInstance->getTable();

        $query = $modelClass::query();


        foreach ($joins as $join) {
            [$table, $first, $operator, $second, $type] = array_pad($join, 5, 'inner');
            $query->join($table, $first, $operator, $second, $type);
        }


        $query->select($select);


        $record = $query->where("$tableName.$primaryKey", $id)->first();

        if (!$record) {
            return null;
        }

        $attributes = $record->getAttributes();
        foreach ($attributes as $key => $value) {
            if (is_null($value)) {
                unset($attributes[$key]);
            }
        }

        return (object) $attributes;
    }

    public static function logDeletion($model, $customData = [])
    {
        $userId = Auth::guard('api')->user() ? Auth::guard('api')->user()->id : null;

        if ($userId === null) {
            Log::warning('User not authenticated for logging deletion of ' . class_basename($model));
            return;
        }

        $logEntry = new ActivityLog;
        $logEntry->log_name = class_basename($model);
        $logEntry->description = 'Deleted ' . class_basename($model);
        $logEntry->subject_type = get_class($model);
        $logEntry->subject_id = $model->getKey();
        $logEntry->causer_type = 'App\Models\User';
        $logEntry->causer_id = $userId;
        $logEntry->event = 'deleted';

        $logEntry->properties = json_encode(['attributes' => (array) $customData]);

        $logEntry->created_at = Carbon::now();
        $logEntry->updated_at = Carbon::now();
        $logEntry->save();
    }
}
