<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Exception;

class RoleController extends Controller
{
    public function index(Request $request)
    {

        try {
            $sortBy = $request->get('sortBy');
            $direction = $request->get('sortDesc') === 'true' ? 'DESC' : 'ASC';
            $roles = Role::leftJoin('departments', 'roles.department_id', '=', 'departments.id')
            ->select(
                'roles.*',
                'roles.name',
                'roles.created_at',
                'departments.name as department'
            )
            ->when($request->name, function ($query) use ($request) {
                $query->where('roles.name', 'like', '%'.$request->name.'%');
            })
            ->when($request->department, function ($query) use ($request) {
                $query->where('departments.name', 'like', '%'.$request->department.'%');
            });

            if ($sortBy) {
                if ($sortBy === 'name') {
                    $roles->orderBy('roles.name', $direction);
                } elseif ($sortBy === 'department') {
                    $roles->orderBy('departments.name', $direction);
                }else {
                    // Sorting for any other valid column in the users table
                    $roles->orderBy('roles.' . $sortBy, $direction);
                }
            } else {
                // Default sorting if no sortBy is provided
                $roles->orderBy('roles.id', 'DESC');
            }

            if ($request->filled('globalSearch')) {
                $searchTerm = '%'.$request->globalSearch.'%';
                $roles->where(function ($query) use ($searchTerm) {
                    $query->orWhere('roles.name', 'LIKE', $searchTerm)
                    ->orWhere('departments.name', 'LIKE', $searchTerm);
                   
                });
            }

            $allRole = $roles->get();
            $total = $roles->count();
            $role = $allRole->forPage($request->page, $request->perPage);

            return response()->json([
                'data' => $role,
                'total' => $total,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch Data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function roleOptions(Request $request)
{
    try {
        $query = Role::query();

        if ($request->has('department_id')) {
            $department = Department::where('name', $request->department_id)->first();
            if ($department) {
                $query->where('department_id', $department->id);
            }
        }        

        $roles = $query->select('id as value', 'name as label')->get();

        return response()->json([
            'data' => $roles,
            'status' => 200,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Unable to fetch Data',
            'message' => $e->getMessage(),
        ], 500);
    }
}


    public function roleDepartmentOptions()
    {
        try {
            $depart = Department::select('id as value', 'name as label')->get();

            return response()->json([
                'data' => $depart,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch Data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // Show the form for creating a new role
    public function create()
    {
        // This method can be used if you are creating a form for creating roles
    }

    // Store a newly created role in storage
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'regex:/^(?=.*[A-Za-z])[A-Za-z0-9\s\-_@.#]+$/',
                    Rule::unique('roles')->where(function ($query) use ($request) {
                        return $query->where('department_id', $request->department_id)
                        ->whereNull('deleted_at');
                    }),
                ],
                'department_id' => 'required',
            ],
            [
                'name.required' => 'Role is required.',
                'name.regex' => 'The role name must contain at least one letter',
                'department_id.required' => 'Department is required.',
                'name.unique' => 'Role already exists for this department.',
            ]
        );
        
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->messages(), 'code' => 422], 422);
        }
        try {
            $role = new Role;
            $role->name = ucwords(strtolower($request->name));
            $role->slug = Str::slug($request->name);
            $role->department_id = $request->department_id;
         
            $role->save();

            $id = $role->id;
           
            // $roleData = ActivityLog::getActivityInfo(
            //     Role::class,
            //     $id,
            //     [
            //         'roles.name as Name',
            //         'departments.name as Department',
            //         'roles.created_at as Created At',
            //         'roles.updated_at as Updated At'
            //     ],
            //     [
            //         ['departments', 'roles.department_id', '=', 'departments.id']
            //     ]
            // );
            // ActivityLog::logCreation($role, $roleData);


            return response()->json(['message' => 'role created successfully', 'data' => $role], 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create role', 'message' => $e->getMessage()], 500);
        }
    }

    // Display the specified role
  public function show($id)
{
    $id = (int) base64_decode($id, true) ?: $id;

    try {
        $role = Role::leftJoin('departments', 'roles.department_id', '=', 'departments.id')
            ->select(
                'roles.*',
                'roles.name',
                'roles.created_at',
                'departments.name as department'
            )
            ->findOrFail($id);

        return response()->json($role, 200);
    } catch (Exception $e) {
        return response()->json(['error' => 'role not found', 'message' => $e->getMessage()], 404);
    }
}

    // Show the form for editing the specified role
    public function edit($id)
    {
        try {
            $role = Role::findOrFail($id);

            return response()->json($role, 200); // You can return the role data to populate an edit form in your frontend
        } catch (Exception $e) {
            return response()->json(['error' => 'role not found', 'message' => $e->getMessage()], 404);
        }
    }

    // Update the specified role in storage
    public function update(Request $request, $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'regex:/^(?=.*[A-Za-z])[A-Za-z0-9\s\-_@.#]+$/',
                    Rule::unique('roles')
                        ->ignore($id)
                        ->where(function ($query) use ($request) {
                            return $query->where('department_id', $request->department_id)
                            ->whereNull('deleted_at');
                        }),
                ],
                'department_id' => 'required',
            ],
            [
                'name.required' => 'Role is required.',
                'name.regex' => 'The role name must contain at least one letter',
                'department_id.required' => 'Department is required.',
                'name.unique' => 'Role already exists for this department.',
            ]
        );
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->messages(), 'code' => 422], 422);
        }
        try {
          
            // $oldData = ActivityLog::getActivityInfo(
            //     Role::class,
            //     $id,
            //     [
            //         'roles.name as Name',
            //         'departments.name as Department',
            //         'roles.created_at as Created At',
            //         'roles.updated_at as Updated At'
            //     ],
            //     [
            //         ['departments', 'roles.department_id', '=', 'departments.id']
            //     ]
            // );

            $role = Role::findOrFail($id);
         
            $role->name = ucwords(strtolower($request->name));
            $role->slug = Str::slug($request->name);
            $role->department_id = $request->department_id;
         
            $role->save();

          
            // $newData = ActivityLog::getActivityInfo(
            //     Role::class,
            //     $id,
            //     [
            //         'roles.name as Name',
            //         'departments.name as Department',
            //         'roles.created_at as Created At',
            //         'roles.updated_at as Updated At'
            //     ],
            //     [
            //         ['departments', 'roles.department_id', '=', 'departments.id']
            //     ]
            // );
           
            // ActivityLog::logUpdate($role, $oldData, $newData);


            return response()->json(['message' => 'role updated successfully', 'data' => $role], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update role', 'message' => $e->getMessage()], 500);
        }
    }

    // Remove the specified role from storage
    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);

            DB::table('employees')
            ->where('role_id', $id)
            ->update(['deleted_at' => now()]);

            $role->delete();

         

            return response()->json(['message' => 'Role deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete role', 'message' => $e->getMessage()], 500);
        }
    }
}
