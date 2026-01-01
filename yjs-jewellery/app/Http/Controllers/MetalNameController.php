<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MetalName;
use App\Models\MetalType;
use Illuminate\Support\Facades\Validator;
use Exception;

class MetalNameController extends Controller
{
    public function index(Request $request)
    {
        $metalNames = MetalName::where(function ($query) use ($request) {
                if (!empty($request->name)) {
                    $query->where('name', 'like', '%' . $request->name . '%');
                }
                if (!empty($request->globalSearch)) {
                    $query->where('name', 'like', '%' . $request->globalSearch . '%')
                        ->orWhere('description', 'like', '%' . $request->globalSearch . '%');
                }
            })
            ->orderBy('name', 'asc')
            ->get();

        return response([
            'data' => $metalNames,
            'status' => 'success'
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:metal_names,name,NULL,id,deleted_at,NULL',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }

            $metalName = new MetalName();
            $metalName->name = $request->name;
            $metalName->description = $request->description;
            $metalName->save();

            return response([
                'status' => 'success',
                'data' => $metalName,
                'message' => 'Metal name added successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response([
                'error' => 'Something went wrong. ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:metal_names,name,' . $id . ',id,deleted_at,NULL',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }

            $metalName = MetalName::find($id);

            if (!$metalName) {
                return response([
                    'error' => 'Metal name not found.',
                    'code' => 404
                ], 404);
            }
            
            $metalName->name = $request->name;
            $metalName->description = $request->description;
            $metalName->save();

        
            return response([
                'data' => $metalName,
                'status' => 'success',
                'message' => 'Metal name updated successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response([
                'error' => 'Something went wrong. ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    public function destroy($id)
    {
        $metalName = MetalName::findOrFail($id);
        $metalName->delete();
        
        MetalType::where('metal_name_id', $id)->delete();
        
        return response()->json([
            'status' => 'deleted', 
            'message' => 'Metal name deleted successfully!'
        ], 200);
    }

}
