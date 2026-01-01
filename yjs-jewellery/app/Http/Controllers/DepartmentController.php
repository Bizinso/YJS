<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $sortBy = $request->get('sortBy');
            $direction = $request->get('sortDesc') === 'true' ? 'DESC' : 'ASC';
            $departments = Department::select(
                    'departments.*',
                    'branches.name as branch_name'
                )
                ->leftjoin('branches','branches.id','branch_id')
                ->when($request->has('name'), function ($query) use ($request) {
                    $query->where('departments.name', 'LIKE', $request->name . '%');
                })
                ->when($request->filled('branch_id'), function ($query) use ($request) {
                    $query->where('branches.name',  'LIKE', $request->branch_id . '%');
                });

            if ($sortBy) {
                if ($sortBy === 'name') {
                    $departments->orderBy('departments.name', $direction);
                }elseif ($sortBy === 'branch_name') {
                    $departments->orderBy('branches.name', $direction);
                } else {
                    $departments->orderBy('departments.' . $sortBy, $direction);
                }
            } else {
                $departments->orderBy('departments.id', 'DESC');
            }

            if ($request->filled('globalSearch')) {
                $searchTerm = '%' . $request->globalSearch . '%';

                $departments->where(function ($query) use ($searchTerm) {
                    $query->orWhere('departments.name', 'LIKE', $searchTerm)
                        ->orWhere('branches.name', 'LIKE', $searchTerm);
                });
            }

            $alldepartment = $departments->get();
            $total = $departments->count();
            $department = $alldepartment->forPage($request->page, $request->perPage);

            return response()->json([
                'data' => $department,
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

    public function departmentOption()
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


    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('departments')->whereNull('deleted_at'),
                    'regex:/^(?=.*[A-Za-z])[A-Za-z\s]+$/'
                ],
                'branch_id' => 'required|exists:branches,id',
            ],
            [
                'name.required' => 'Department is required.',
                'name.unique' => 'The department name has already been taken.',
                'name.regex' => 'The department name may only contain letters and spaces.',
                'branch_id.required' => 'Branch is required.',
                'branch_id.exists' => 'Invalid branch selected.',
            ]
        );

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->messages(), 'code' => 422], 422);
        }

        try {
            $data = new Department;
            $data->name = ucwords(strtolower($request->name));
            $data->slug = Str::slug($request->name);
            $data->branch_id = $request->branch_id;
            $data->save();

            return response()->json(['message' => 'department created successfully', 'data' => $data], 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create department', 'message' => $e->getMessage()], 500);
        }
    }


    public function show($id)
    {
        $id = (int) base64_decode($id, true) ?: $id;

        try {
            $department = Department::findOrFail($id);

            return response()->json($department, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'department not found', 'message' => $e->getMessage()], 404);
        }
    }

    public function edit($id)
    {

        try {
            $department = Department::findOrFail($id);

            return response()->json($department, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'department not found for editing', 'message' => $e->getMessage()], 404);
        }
    }


    public function update(Request $request, $id)
    {
        $depart = Department::findOrFail($id);

        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('departments', 'name')
                        ->ignore($depart->id)
                        ->whereNull('deleted_at'),
                    'regex:/^(?=.*[A-Za-z])[A-Za-z\s]+$/',
                ],
                'branch_id' => 'required|exists:branches,id',
            ],
            [
                'name.required' => 'Department is required.',
                'name.unique' => 'The department name has already been taken.',
                'name.regex' => 'The department name may only contain letters and spaces.',
                'branch_id.required' => 'Branch is required.',
                'branch_id.exists' => 'Invalid branch selected.',
            ]
        );

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->messages(), 'code' => 422], 422);
        }

        try {
            $department = Department::findOrFail($id);
            $department->name = ucwords(strtolower($request->name));
            $department->slug = Str::slug($request->name);
            $department->branch_id = $request->branch_id;
            $department->save();

            return response()->json(['message' => 'department updated successfully', 'data' => $department], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update department', 'message' => $e->getMessage()], 500);
        }
    }


   
    public function destroy($id)
    {
        try {
            $department = Department::findOrFail($id);
            DB::table('roles')
            ->where('department_id', $id)
            ->update(['deleted_at' => now()]);

            $employeeUserIds = DB::table('employees')
            ->where('department_id', $id)
            ->pluck('user_id');

            DB::table('users')
                ->whereIn('id', $employeeUserIds)
                ->update(['deleted_at' => now()]);

            $department->delete();

            DB::table('employees')
            ->where('department_id', $id)
            ->update(['deleted_at' => now()]);


            return response(['status' => 'success', 'message' => 'Department deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete department', 'message' => $e->getMessage()], 500);
        }
    }
}
