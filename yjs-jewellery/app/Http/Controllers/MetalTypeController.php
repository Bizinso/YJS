<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MetalType;
use App\Models\Purity;
use Illuminate\Support\Facades\Validator;
use Exception;

class MetalTypeController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sortBy');
        $sortDesc = $request->get('sortDesc') === 'true';
        $direction = $sortDesc ? 'DESC' : 'ASC';

        $sortableColumns = [
            'id' => 'metal_types.id',
            'name' => 'metal_types.name',
            'metal_name' => 'metal_names.name',
            'purity_id' => 'purities.name',
            'price_per_gram' => 'metal_types.price_per_gram',
        ];

        $sortColumn = $sortableColumns[$sortBy] ?? 'metal_types.id';

        /**
         * Subquery to get latest metal_type id
         * per (metal_name_id + purity_id)
         */
        $latestMetalTypes = MetalType::selectRaw('MAX(id) as id')
            ->whereNull('deleted_at')
            ->groupBy('metal_name_id', 'purity_id');

        $metalType = MetalType::joinSub($latestMetalTypes, 'latest', function ($join) {
                $join->on('metal_types.id', '=', 'latest.id');
            })
            ->leftJoin('purities', function ($join) {
                $join->on('purities.id', '=', 'metal_types.purity_id')
                    ->whereNull('purities.deleted_at');
            })
            ->leftJoin('metal_names', 'metal_names.id', '=', 'metal_types.metal_name_id')
            ->select(
                'metal_types.*',
                'purities.name as purity_id',
                'metal_names.name as metal_name'
            )
            ->where(function ($query) use ($request) {
                if (!empty($request->name)) {
                    $query->where('metal_names.name', 'like', '%' . $request->name . '%');
                }

                if (!empty($request->purity_id)) {
                    $query->where('purities.name', 'like', '%' . $request->purity_id . '%');
                }

                if (!empty($request->color)) {
                    $query->where('metal_types.color', 'like', '%' . $request->color . '%');
                }

                if (!empty($request->price_per_gram)) {
                    $query->where('metal_types.price_per_gram', 'like', '%' . $request->price_per_gram . '%');
                }

                if (!empty($request->description)) {
                    $query->where('metal_types.description', 'like', '%' . $request->description . '%');
                }

                if (!empty($request->globalSearch)) {
                    $query->where(function ($q) use ($request) {
                        $q->where('metal_names.name', 'like', '%' . $request->globalSearch . '%')
                        ->orWhere('purities.name', 'like', '%' . $request->globalSearch . '%')
                        ->orWhere('metal_types.color', 'like', '%' . $request->globalSearch . '%')
                        ->orWhere('metal_types.description', 'like', '%' . $request->globalSearch . '%')
                        ->orWhere('metal_types.price_per_gram', 'like', '%' . $request->globalSearch . '%');
                    });
                }
            })
            ->orderBy($sortColumn, $direction);

        // Total count AFTER grouping
        $totalCount = $metalType->count();

        // Pagination
        if ($request->has('page') && $request->has('perPage')) {
            $page = (int) $request->page;
            $perPage = (int) $request->perPage;

            $data = $metalType->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();
        } else {
            $data = $metalType->get();
        }

        return response([
            'data' => $data,
            'status' => 'success',
            'total' => $totalCount,
        ], 200);
    }


    public function getPurityOptions()
    {
        $data = Purity::where('status', 'A')
            ->selectRaw('MIN(id) as id, name, ANY_VALUE(percentage) as percentage, status')
            ->groupBy('name', 'status')
            ->get();

        return response(['data' => $data, 'status' => 'success'], 200);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'metal_name_id' => 'required|exists:metal_names,id',
                    'purity_id' => 'required|exists:purities,id',
                    'price_per_gram' => 'required|numeric|min:0',
                ],
                [
                    'metal_name_id.required' => 'The metal name field is required.',
                    'metal_name_id.exists' => 'The selected metal name is invalid.',
                    'purity_id.required' => 'The purity field is required.',
                    'price_per_gram.required' => 'The price per gram field is required.',
                    'price_per_gram.numeric' => 'The price per gram must be a number.',
                ]
            );

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }

            $metalType = new MetalType();
            $metalType->name = $request->name;
            $metalType->metal_name_id = $request->metal_name_id;
            $metalType->purity_id = $request->purity_id;
            $metalType->description = $request->description;
            $metalType->color = $request->color;
            $metalType->density = $request->density;
            $metalType->price_per_gram = $request->price_per_gram;
            $status = strtolower($request->status ?? 'active');
            $metalType->status = in_array($status, ['active', 'inactive']) ? $status : 'active';
            $metalType->save();


            return response([
                'status' => 'success',
                'data' => 'Metal type added successfully!'
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
         $metalType = MetalType::leftJoin('purities', 'purities.id', '=', 'metal_types.purity_id')
        ->leftJoin('metal_names', 'metal_names.id', '=', 'metal_types.metal_name_id')
        ->select(
            'metal_types.*',
            'purities.name as purity_name',
            'metal_names.name as metal_name'
            )
        ->where('metal_types.id', $id)
        ->firstOrFail();

    return $metalType;
    }

    public function update(Request $request, $id)
    {
       
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'metal_name_id'  => 'required|exists:metal_names,id',
                    'purity_id'      => 'required|exists:purities,id',
                    'price_per_gram' => 'required|numeric|min:0',
                ],
                [
                    'metal_name_id.required' => 'The metal name field is required.',
                ]
            );


            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }
            $oldData = MetalType::getMetalTypeInfo($id);
            $metalType = MetalType::find($id);

            if (!$metalType) {
                return response([
                    'error' => 'Metal type not found.',
                    'code' => 404
                ], 404);
            }

            $metalType->name = $request->name;
            $metalType->metal_name_id = $request->metal_name_id;
            $metalType->purity_id = $request->purity_id;
            $metalType->description = $request->description;
            $metalType->color = $request->color;
            $metalType->density = $request->density;
            $metalType->price_per_gram = $request->price_per_gram;
            $status = strtolower($request->status ?? 'active');
            $metalType->status = in_array($status, ['active', 'inactive']) ? $status : 'active';
            $metalType->save();
        
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
        $metalType = MetalType::findOrFail($id);
        $metalType->delete();
        return response()->json(['status' => 'deleted', 'message' => 'Deleted Successfully!'], 200);
    }
}
