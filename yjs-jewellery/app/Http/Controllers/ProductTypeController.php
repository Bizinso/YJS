<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class ProductTypeController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sortBy');
        $sortDesc = $request->get('sortDesc') === 'true';
        $direction = $sortDesc ? 'DESC' : 'ASC';

        $sortBy = !empty($sortBy) ? $sortBy : 'id';

        $query = ProductType::select(
            'product_types.*',
            DB::raw('count(*) OVER() AS total_row_count')
        )
            ->when($request->name, function ($q) use ($request) {
                $q->where('product_types.name', 'like', '%' . $request->name . '%');
            })
            ->when($request->globalSearch, function ($q) use ($request) {
                $q->where(function ($subQuery) use ($request) {
                    $subQuery->where('name', 'like', '%' . $request->globalSearch . '%')
                        ->orWhere('description', 'like', '%' . $request->globalSearch . '%');
                });
            })
            ->orderBy($sortBy, $direction);

        if ($request->has('page') && $request->has('perPage')) {
            $page = (int)$request->page;
            $perPage = (int)$request->perPage;

            $data = $query->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();
        } else {
            $data = $query->get();
        }

        return response([
            'data' => $data,
            'status' => 'success'
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            $existing = ProductType::where('name', $request->name)
                ->whereNull('deleted_at')
                ->exists();

            if ($existing) {
                return response([
                    'errors' => ['name' => ['The name has already been taken.']],
                    'code' => 422
                ], 422);
            }
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                ],
            ], [
                'name.regex' => 'The name cannot contain numbers.',
            ]);

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }

            $productType = new ProductType();
            $productType->name = $request->name;
            $productType->description = $request->description;
            $productType->save();

            return response([
                'status' => 'success',
                'data' => 'Product type added successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response([
                'error' => 'Something went wrong. ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }

    public function show($id)
    {
        $id = (int) base64_decode($id, true) ?: $id;
        return ProductType::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        try {
            $existing = ProductType::where('name', $request->name)
                ->where('id', '!=', $id)
                ->whereNull('deleted_at')
                ->exists();

            if ($existing) {
                return response([
                    'errors' => ['name' => ['The name has already been taken.']],
                    'code' => 422
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                ],
            ], [
                'name.regex' => 'The name cannot contain numbers.',
            ]);

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }
            
            $productType = ProductType::find($id);
            if (!$productType) {
                return response([
                    'error' => 'Product type not found.',
                    'code' => 404
                ], 404);
            }

            $productType->name = $request->name;
            $productType->description = $request->description;
            $productType->save();
            
            return response([
                'data' => 'Updated Successfully!',
                'status' => 'success'
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
        $productType = ProductType::findOrFail($id);
        $productType->delete();
        return response()->json(['status' => 'deleted', 'message' => 'Deleted Successfully!'], 200);
    }

    public function changeStatus($id)
    {
        $productType = ProductType::findOrFail($id);
        $productType->status = $productType->status === 'A' ? 'I' : 'A';
        $productType->save();
        $statusText = $productType->status === 'A' ? 'Active' : 'Inactive';
        return response()->json([
            'message' => 'Status changed to ' . $statusText,
            'status' => 'success'
        ]);
    }
}
