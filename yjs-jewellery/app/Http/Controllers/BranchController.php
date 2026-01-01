<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        try {
            $sortBy = $request->get('sortBy');
            $direction = $request->get('sortDesc') === 'true' ? 'DESC' : 'ASC';
            $branches = Branch::
                when($request->has('name'), function ($query) use ($request) {

                    $query->where('name', 'LIKE', $request->name . '%');
                });

            if ($sortBy) {
                if ($sortBy === 'name') {
                    $branches->orderBy('branches.name', $direction);
                } else {
                    // Sorting for any other valid column in the users table
                    $branches->orderBy('branches.' . $sortBy, $direction);
                }
            } else {
                // Default sorting if no sortBy is provided
                $branches->orderBy('branches.id', 'DESC');
            }

            if ($request->filled('globalSearch')) {
                $searchTerm = '%'.$request->globalSearch.'%';
                $branches->where(function ($query) use ($searchTerm) {
                    $query->orWhere('branches.name', 'LIKE', $searchTerm);
                    
                });
            }

            $allbranch = $branches->get();
            $total = $branches->count();
            $branch = $allbranch->forPage($request->page, $request->perPage);

            return response()->json([
                'data' => $branch,
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

    public function branchOption()
    {
        try {
            $branch = Branch::select('id as value', 'name as label')->get();

            return response()->json([
                'data' => $branch,
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
       
        $slug = Str::slug($request->name);

        $validator = Validator::make(
            array_merge($request->all(), ['slug' => $slug]),
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^(?=.*[A-Za-z])[A-Za-z\s]+$/',
                ],
                'slug' => [
                    Rule::unique('branches')->whereNull('deleted_at'),
                ],
            ],
            [
                'name.required' => 'Branch is required.',
                'slug.unique' => 'This branch already exists.',
            ]
        );
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->messages(), 'code' => 422], 422);
        }
        try {
            $data = new Branch;
            $data->name = ucwords(strtolower($request->name));
            $data->slug = Str::slug($request->name);
            $data->save();
            
            $id = $data->id;
            // $departmentData = ActivityLog::getActivityInfo(
            //     Department::class,
            //     $id,
            //     ['departments.name as Name', 'departments.created_at as Created At', 'departments.updated_at as Updated At']
            // );
          
            // ActivityLog::logCreation($data, $departmentData);



            return response()->json(['message' => 'Branch created successfully', 'data' => $data], 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create Branch', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $id = (int) base64_decode($id, true) ?: $id;

        try {
            $branch = Branch::findOrFail($id);

            return response()->json($branch, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'branch not found', 'message' => $e->getMessage()], 404);
        }
    }

    public function edit($id)
    {

        try {
            $branch = Branch::findOrFail($id);

            return response()->json($branch, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Barnch
             not found for editing', 'message' => $e->getMessage()], 404);
        }
    }


    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('branches', 'name')
                    ->ignore($branch->id)
                    ->whereNull('deleted_at'),
                    'regex:/^(?=.*[A-Za-z])[A-Za-z\s]+$/',
                ],
            ],
            [
                'name.required' => 'Branch is required.',
                'name.unique' => 'The Branch name has already been taken.',
                'name.regex' => 'The Branch name may only contain letters and spaces, and no special characters.',
            ]
        );
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->messages(), 'code' => 422], 422);
        }
        try {
           
            $branch = Branch::findOrFail($id);
            $branch->name = ucwords(strtolower($request->name));
            $branch->slug = Str::slug($request->name);
            $branch->save();

            return response()->json(['message' => 'Branch updated successfully', 'data' => $branch], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update Branch', 'message' => $e->getMessage()], 500);
        }
    }

   
    public function destroy($id)
    {
        try {
            $branch = Branch::findOrFail($id);

            $departmentids = DB::table('departments')
            ->where('branch_id', $id)
            ->pluck('id');
            
            $employeeUserIds = DB::table('employees')
            ->where('branch_id', $id)
            ->pluck('user_id');

            DB::table('users')
                ->whereIn('id', $employeeUserIds)
                ->update(['deleted_at' => now()]);

            DB::table('employees')
                ->where('branch_id', $id)
                ->update(['deleted_at' => now()]);

            DB::table('roles')
            ->whereIn('department_id', $departmentids)
            ->update(['deleted_at' => now()]);

            DB::table('departments')
                ->whereIn('id', $departmentids)
                ->update(['deleted_at' => now()]);

            $branch->delete();


            return response(['status' => 'success', 'message' => 'Branch deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete Branch', 'message' => $e->getMessage()], 500);
        }
    }

    // public function export(Request $request)
    // {
    //     $fileName = 'Branch-' . now()->format('Y-m-d') . '.xlsx';
    //     return Excel::download(new DepartmentExport($request), $fileName);
    // }
    


    // public function downloadImportTemplateDepartment()
    // {
    //     return Excel::download(new DepartmentImportTemplateExport(), 'department_import_template.xlsx');
    // }


//     public function import(Request $request)
// {
//     $request->validate([
//         'file' => 'required|mimes:xlsx,xls,csv|max:2048'
//     ]);

//     try {
//         $import = new DepartmentImport;
//         Excel::import($import, $request->file('file'));
        
//         if ($import->getRowCount() === 0) {
//             return response()->json([
//                 'error' => 'No valid data found'
//             ], 422);
//         }

//         return response()->json([
//             'message' => $import->getRowCount() . ' departments imported successfully'
//         ]);
        
//     } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
//         $errors = collect($e->failures())->map(function($failure) {
//             return [
//                 'row' => $failure->row(),
//                 'attribute' => $failure->attribute(),
//                 'errors' => $failure->errors(),
//                 'values' => $failure->values(),
//             ];
//         });

//         return response()->json([
//             'error' => 'Validation errors in file',
//             'errors' => $errors
//         ], 422);
//     } catch (\Exception $e) {
//         return response()->json([
//             'error' => 'Import failed: ' . $e->getMessage()
//         ], 500);
//     }
// }

}
