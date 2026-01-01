<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\EmployeeAddress;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Exception;

class EmployeeController extends Controller
{
    public function addAddress(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'address' => [
                    'required',
                    'string',
                    'max:500',
                    'regex:/^[a-zA-Z0-9\s,.\-\/#]+$/',
                ],
                'country' => 'required|integer|exists:countries,id',
                'state'   => 'required|integer|exists:states,id',
                'city'    => 'required|integer|exists:cities,id',
                'pincode' => 'required|string|max:10',
            ]
        );


        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()->messages(),
                'code' => 422
            ], 422);
        }

        $address = EmployeeAddress::updateOrCreate(
            ['user_id' => $request->user_id],
            [
                'country_id'  => $request->country,
                'state'       => $request->state,     // column name is state
                'city'        => $request->city,      // column name is city
                'address_line'=> $request->address,   // correct column name
                'postal_code' => $request->pincode,   // correct column name
            ]
        );


        return response()->json([
            'message' => 'Address saved successfully',
            'data' => $address
        ]);
    }

    public function getAddress($user_id)
    {
        $id = base64_decode($user_id);
        $address = EmployeeAddress::where('user_id', $id)->first();
        return response()->json(['data' => $address]);
    }

    public function changePassword(Request $request){
        $user = Auth::guard('employee')->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'new_password_confirmation' => 'required|same:new_password',
        ], [
            'current_password.required' => 'The Current Password field is required.',
            'new_password.required' => 'The New Password field is required.',
            'new_password.string' => 'The New Password must be a string.',
            'new_password.min' => 'The New Password must be at least 8 characters.',
            'new_password_confirmation.required' => 'The Confirm Password field is required.',
            'new_password_confirmation.same' => 'The Confirm Password and New Password must match.',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->messages(), 'code' => 422], 422);
        }

     

        if (! (Hash::check($request->get('current_password'), $user->password))) {
            return response()->json([
                'errors' => [
            'current_password' => ['Your Current Password does not match with the password you provided.']],
                'code' => 422
            ], 422);
        }

   

        if (strcmp($request->get('current_password'), $request->get('new_password')) == 0) {
            return response()->json([
            'errors' => [
            'new_password_confirmation' => ['New Password cannot be the same as your Current Password.'],
            'new_password' => ['New Password cannot be the same as your Current Password.']
            ],
            'code' => 422
            ], 422);
        }

        
        $user = User::find($user->id);
        $user->password = bcrypt($request->get('new_password'));
        $user->save();

        return response()->json([
            'data' => [],
            'status' => 'success',
            'message' => 'Password changed successfully!',
        ], 200);
    }

    public function index(Request $request)
    {
        try {
            $sortBy = $request->get('sortBy');
            $direction = $request->get('sortDesc') === 'true' ? 'DESC' : 'ASC';
    
            $departments = collect($request->departmnt)->map(fn($id) => (int) $id)->toArray();
            
            $user = User::leftjoin('employees','employees.user_id','users.id')
                ->leftJoin('roles', 'employees.role_id', '=', 'roles.id')
                ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
                ->leftJoin('users as reporting_users', 'users.reporting_head', '=', 'reporting_users.id')
                ->select(
                    'users.*',
                    'employees.employee_code',
                    'employees.branch_id',
                    'employees.department_id',
                    'employees.role_id',
                    'roles.name as role',
                    'departments.name as department',
                    DB::raw('CONCAT(users.first_name, " ", users.last_name) as name'),
                    DB::raw('CONCAT(reporting_users.first_name, " ", reporting_users.last_name) as reporting_head_name')
                )
                ->where('users.user_type', 'employee')
                ->when($request->has('name'), function ($query) use ($request) {
                    $query->where(function ($q) use ($request) {
                        $q->where('users.first_name', 'LIKE', $request->name . '%')
                            ->orWhere('users.last_name', 'LIKE', $request->name . '%');
                    });
                })
                ->when($request->searchempCode, function ($query) use ($request) {
                    $query->whereIn('employees.employee_code', $request->searchempCode);
                })
                ->when($request->departmnt, function ($query) use ($departments) {
                    $query->whereIn('employees.department_id', $departments);
                })
                ->when($request->email, function ($query) use ($request) {
                    $query->where('users.email', $request->email);
                })
                ->when($request->rolee, function ($query) use ($request) {
                    $query->whereIn('employees.role_id', $request->rolee);
                })
                ->when($request->has('searchname'), function ($query) use ($request) {
                    $query->whereRaw('CONCAT(users.first_name, " ", users.last_name) LIKE ?', [$request->searchname . '%']);
                })
                ->when($request->reporting_to, function ($query) use ($request) {
                    $query->where('users.reporting_head', $request->reporting_to);
                });

            // Sorting Logic
            if ($sortBy) {
                if ($sortBy === 'first_name') {
                    $user->orderBy('users.first_name', $direction);
                } elseif ($sortBy === 'department') {
                    $user->orderBy('departments.name', $direction);
                } elseif ($sortBy === 'role') {
                    $user->orderBy('roles.name', $direction);
                } elseif ($sortBy === 'reporting_head_name') {
                    $user->orderByRaw('CONCAT(reporting_users.first_name, " ", reporting_users.last_name) ' . $direction);
                } else {
                    $user->orderBy('users.' . $sortBy, $direction);
                }
            } else {
                $user->orderBy('users.id', 'DESC');
            }

            if ($request->filled('globalSearch')) {
                $searchTerm = '%' . $request->globalSearch . '%';
                $user->where(function ($query) use ($searchTerm) {
                    $query->orWhereRaw('CONCAT(users.first_name, " ", users.last_name) LIKE ?', [$searchTerm])
                        ->orWhere('employees.employee_code', 'LIKE', $searchTerm)
                        ->orWhere('roles.name', 'LIKE', $searchTerm)
                        ->orWhere('departments.name', 'LIKE', $searchTerm);
                });
            }

            $users = $user->get();
            $total = $users->count();
            $user = $users->forPage($request->page, $request->perPage);

            return response()->json([
                'data' => $user,
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
    
    public function employeeOption()
    {
        
        try {
            $emp = User::select('id as value', DB::raw('CONCAT(first_name, " ", last_name) as label'))
            ->where('users.user_type', 'employee')->whereNull('deleted_at')->get();

            return response()->json([
                'data' => $emp,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch Data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function employeeCode()
    {
        
        try {
            $empCode = Employee::where('employee_code', '!=', '')->select('id as value', 'employee_code as label')->get();

            return response()->json([
                'data' => $empCode,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch Data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function reportingToOption()
    {
        try {
            $users = User::select('id as value', DB::raw('CONCAT(first_name, " ", last_name) as label'))
            ->where('users.user_type', 'employee')->whereNull('deleted_at')->get();

            return response()->json([
                'data' => $users,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function departmentEmpOption()
    {
        
        try {
            $empDepartment = Department::select('id as value', 'name as label')
                            ->get();


            return response()->json([
                'data' => $empDepartment,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch Data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function roleEmpOption()
    {
        
        try {
            $empRole = Role::select('id as value', 'name as label')->get();


            return response()->json([
                'data' => $empRole,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch Data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function changeStatus($id)
    {
        $user = User::findOrFail($id);
        $user->status = $user->status === 'A' ? 'I' : 'A';
        $user->save();

        return response()->json([
            'message' => 'Status changed to ' . ucfirst($user->status),
            'status' => 'success'
        ]);
    }

    public function roleOptions($id)
    {
      
        try {
            $role = Role::select('id as value', 'name as label')->where('department_id', $id)->get();

            return response()->json([
                'data' => $role,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unable to fetch Data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'first_name' => 'required',
                'last_name' => 'required',

                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->whereNull('deleted_at'),
                    Rule::unique('employees', 'email')->whereNull('deleted_at'),
                ],

                'phone' => [
                    'required',
                    Rule::unique('users', 'phone')->whereNull('deleted_at'),
                    Rule::unique('employees', 'phone')->whereNull('deleted_at'),
                ],

                'role_id' => 'required',
                'department_id' => 'required',
                'reporting_head' => 'nullable|exists:users,id',
            ],
            [
                'role_id.required' => 'Role is required.',
                'department_id.required' => 'Department is required.',
            ]
        );

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()->messages(),
                'code' => 422
            ], 422);
        }

        try {

            /* ---------------------------------------------
                1️⃣ CREATE USER RECORD
            ----------------------------------------------*/
            $user = new User();
            $user->first_name = ucwords(strtolower($request->first_name));
            $user->last_name = ucwords(strtolower($request->last_name));
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->mobile_code = $request->mobile_code ?? '+91';
            $user->user_type = 'employee';
            $user->reporting_head = $request->reporting_head;
            $user->status = 'A';
            $user->save();

            /* ---------------------------------------------
                2️⃣ GENERATE EMPLOYEE CODE
            ----------------------------------------------*/
            $count = Employee::withTrashed()->count() + 1;
            $employeeCode = 'EMP' . str_pad($count, 3, '0', STR_PAD_LEFT);

            /* ---------------------------------------------
                3️⃣ CREATE EMPLOYEE PROFILE RECORD
            ----------------------------------------------*/
            $employee = new Employee();
            $employee->employee_code = $employeeCode;
            $employee->first_name = $user->first_name;
            $employee->last_name = $user->last_name;
            $employee->email = $user->email;
            $employee->phone = $user->phone;
            $employee->user_id = $user->id;
            $employee->department_id = $request->department_id;
            $employee->branch_id = $request->branch_id ?? null;
            $employee->role_id = $request->role_id;
            $employee->status = 'A';
            $employee->save();

            /* ---------------------------------------------
                4️⃣ SAVE ADDRESS (IF YOU HAVE address TABLE)
            ----------------------------------------------*/
            if ($request->address) {
                EmployeeAddress::updateOrCreate(
                    ['employee_id' => $employee->id],
                    ['address' => $request->address]
                );
            }

            return response()->json([
                'status' => 200,
                'message' => 'Employee created successfully',
                'data' => [
                    'user' => $user,
                    'employee' => $employee,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $employee = Employee::where('user_id', $id)->firstOrFail();

        $validator = Validator::make(
            $request->all(),
            [
                'first_name' => 'required',
                'last_name' => 'required',

                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')
                        ->ignore($user->id)
                        ->whereNull('deleted_at'),
                    Rule::unique('employees', 'email')
                        ->ignore($employee->id)
                        ->whereNull('deleted_at'),
                ],

                'phone' => [
                    'required',
                    Rule::unique('users', 'phone')
                        ->ignore($user->id)
                        ->whereNull('deleted_at'),
                    Rule::unique('employees', 'phone')
                        ->ignore($employee->id)
                        ->whereNull('deleted_at'),
                ],

                'role_id' => 'required',
                'department_id' => 'required',
                'reporting_head' => 'nullable|exists:users,id',
            ],
            [
                'role_id.required' => 'Role is required.',
                'department_id.required' => 'Department is required.',
            ]
        );

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()->messages(),
                'code' => 422
            ], 422);
        }

        try {

            /* --------------------------------------------------------
                1️⃣ UPDATE USER TABLE
            ---------------------------------------------------------*/
            $user->first_name = ucwords(strtolower($request->first_name));
            $user->last_name = ucwords(strtolower($request->last_name));
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->reporting_head = $request->reporting_head;
            $user->status = $request->status ?? $user->status;
            $user->save();

            /* --------------------------------------------------------
                2️⃣ UPDATE EMPLOYEE TABLE
            ---------------------------------------------------------*/
            $employee->first_name = $user->first_name;
            $employee->last_name = $user->last_name;
            $employee->email = $user->email;
            $employee->phone = $user->phone;
            $employee->role_id = $request->role_id;
            $employee->department_id = $request->department_id;
            $employee->branch_id = $request->branch_id ?? $employee->branch_id;
            $employee->save();

            /* --------------------------------------------------------
                3️⃣ UPDATE ADDRESS TABLE (if exists)
            ---------------------------------------------------------*/
            if ($request->address) {
                EmployeeAddress::updateOrCreate(
                    ['employee_id' => $employee->id],
                    ['address' => $request->address]
                );
            }

            return response()->json([
                'message' => 'Employee updated successfully',
                'data' => [
                    'user' => $user,
                    'employee' => $employee,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $id = (int) base64_decode($id, true) ?: $id;
            // Load employee with related user, department, branch, role
            $employee = Employee::with([
                'user:id,first_name,last_name,email,phone,user_type,status,reporting_head',
                'user.reportingHead:id,first_name,last_name',
                'department:id,name',
                'branch:id,name',
                'userrole:id,name'
            ])->where('user_id', $id)->first();
            
            if (!$employee) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Employee not found'
                ], 404);
            }
            
            $employee->department_name = $employee->department?->name;
            $employee->branch_name = $employee->branch?->name;
            $employee->role_name = $employee->userrole?->name;
            $employee->reporting_head_name =
            $employee->user?->reportingHead
                ? ($employee->user->reportingHead->first_name . ' ' . $employee->user->reportingHead->last_name)
                : null;

            return response()->json([
                'status' => 200,
                'message' => 'Employee details fetched successfully',
                'data' => $employee
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $employee = Employee::where('user_id', $id)->first();
        if ($employee) {
            $employee->delete();
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'Employee deleted successfully'
        ], 200);
    }


}
