<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class ActivityLogController extends Controller
{
    private const SELECT_USER_NAME = 'CONCAT(users.first_name, " ", users.last_name) as user_name';
    private const SELECT_DEVICE_INFO = "CONCAT(activity_log.device_platform, ' / ', activity_log.device_browser)  as device_browser";
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('perPage', 10);
        $user = Auth::guard('employee')->user();

        $userRole = Auth::guard('employee')->user()->employee->userrole->name;
        if ($userRole === 'Super Admin' || $userRole === 'Admin') {
             $reportingUserIds = User::pluck('id');
        } else {
            $user = User::find($user->id);
            $reportingUserIds = $user->getAllSubordinateUserIds();
        }
        $dataQuery = ActivityLog::leftJoin('users', 'users.id', 'activity_log.causer_id')
            ->whereIn('activity_log.causer_id', $reportingUserIds)
            ->when($request->name, function ($q) use ($request) {
                $q->where('activity_log.causer_id', $request->name);
            })
            ->when($request->description, function ($q) use ($request) {
                $description = strtolower($request->description);
                $q->whereRaw('LOWER(activity_log.properties) LIKE ?', ["%{$description}%"])
                  ->orWhereRaw('LOWER(activity_log.description) LIKE ?', ["%{$description}%"]);
            })            
            ->when($request->log_name, function ($q) use ($request) {
                $q->where('activity_log.log_name', $request->log_name);
            })
      
            ->when($request->created, function ($q) use ($request) {
                $q->whereDate('activity_log.created_at', '=', \Carbon\Carbon::parse($request->created)->toDateString());
            })
            ->select([
                'activity_log.id',
                'activity_log.log_name',
                'activity_log.description',
                'activity_log.subject_type',
                'activity_log.event',
                'activity_log.subject_id',
                'activity_log.causer_type',
                'activity_log.causer_id',
                'activity_log.properties',
                'activity_log.batch_uuid',
                'activity_log.created_at as created',
                'activity_log.updated_at',
                'activity_log.ip_address',
                DB::raw(self::SELECT_DEVICE_INFO),
                DB::raw(self::SELECT_USER_NAME),
                'users.email',
                'users.profile_image',
                DB::raw("CONCAT('".config('global.userPath')."', users.id, '/profileimages/original/', users.profile_image) AS profile_image"),
            ]);
        if ($request->sortBy) {
            $dataQuery->orderBy($request->sortBy, $request->sortDesc === 'true' ? 'DESC' : 'ASC');
        } else {
            $dataQuery->orderBy('activity_log.created_at', 'DESC');
        }
        if ($request->filled('globalSearch')) {
            $searchTerm = '%'.$request->globalSearch.'%';
            $lowerSearchTerm = strtolower($searchTerm);
        
            $dataQuery->where(function ($query) use ($lowerSearchTerm) {
                $query->whereRaw('LOWER(activity_log.log_name) LIKE ?', [$lowerSearchTerm])
                    ->orWhereRaw('LOWER(activity_log.properties) LIKE ?', [$lowerSearchTerm])
                    ->orWhereRaw('LOWER(activity_log.description) LIKE ?', [$lowerSearchTerm])
                    ->orWhereRaw('LOWER(activity_log.created_at) LIKE ?', [$lowerSearchTerm])
                    ->orWhereRaw('LOWER(users.first_name) LIKE ?', [$lowerSearchTerm]);
            });
        }
        
        $totalCount = $dataQuery->count();
        $data = $dataQuery->paginate($perPage, ['*'], 'page', $page);

        return response(['data' => $data, 'total' => $totalCount, 'status' => 'success'], 200);
    }

    public function getUser()
    {
        $user = Auth::guard('employee')->user();
        $user = User::find($user->id);
        $userRole = $user->employee->userrole->name;
    
        $reportingUserIds = in_array($userRole, ['Super Admin', 'Admin'])
            ? User::pluck('id')
            : $user->getAllSubordinateUserIds();
    
        $allUser = User::select(
            'id as value',
            DB::raw('CONCAT(users.first_name, " ", users.last_name) as label')
        )
            ->where('status', '!=', 'Inactive')
            ->whereNull('users.deleted_at')
            ->where('users.first_name', '!=', null)
            ->whereIn('id', $reportingUserIds)
            ->get();
    
        return response()->json([
            'alluser' => $allUser,
        ]);
    }

   
    public function getLogName(Request $request)
    {
        $logname = ActivityLog::select('log_name')->distinct()->orderBy('log_name')->get();

        return response()->json([
            'logname' => $logname,
            'status' => true,
        ]);
    }

    public function getActivityDetail($id)
    {
        $id = base64_decode($id);   
        $log = ActivityLog::leftJoin('users', 'users.id', '=', 'activity_log.causer_id')
            ->select([
                'activity_log.id',
                'activity_log.log_name',
                'activity_log.description',
                'activity_log.subject_type',
                'activity_log.subject_id',
                'activity_log.causer_type',
                'activity_log.causer_id',
                'activity_log.event',
                'activity_log.properties',
                'activity_log.batch_uuid',
                'activity_log.ip_address',
                DB::raw(self::SELECT_DEVICE_INFO),
                DB::raw(self::SELECT_USER_NAME),
                'users.email',
                'users.profile_image',
                DB::raw("CONCAT('".config('global.userPath')."', users.id, '/profileimages/original/', users.profile_image) AS profile_image_full"),
                'activity_log.created_at',
                'activity_log.updated_at'
            ])
            ->where('activity_log.id', $id)
            ->first();

        if (!$log) {
            return response([
                'status' => 'error',
                'message' => 'Activity Log not found.'
            ], 404);
        }

        // Decode properties JSON safely
        $properties = [];
        try {
            $properties = json_decode($log->properties, true) ?: [];
        } catch (\Exception $e) {
            $properties = [];
        }

        // NEW DATA
        $newData = $properties['attributes'] ?? [];

        // OLD DATA
        $oldData = $properties['old'] ?? [];

        return response([
            'status' => 'success',
            'data' => [
                'id' => $log->id,
                'log_name' => $log->log_name,
                'description' => $log->description,
                'event' => $log->event,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,

                // user info
                'user_name' => $log->user_name,
                'email' => $log->email,
                'profile_image' => $log->profile_image_full,

                // device + IP
                'device_browser' => $log->device_browser,
                'ip_address' => $log->ip_address,

                // dates
                'created_at' => $log->created_at,
                'updated_at' => $log->updated_at,

                // changes
                'new' => $newData,
                'old' => $oldData,
                'properties' => $properties
            ]
        ], 200);
    }

}
