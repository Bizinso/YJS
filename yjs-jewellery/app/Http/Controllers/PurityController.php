<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Purity;
use Illuminate\Support\Facades\DB;
use Exception;


class PurityController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sortBy');
        $sortDesc = $request->get('sortDesc') === 'true';
        $direction = $sortDesc ? 'DESC' : 'ASC';

        $sortBy = !empty($sortBy) ? $sortBy : 'id';

        $query = Purity::select(
            'purities.*',
            DB::raw('count(*) OVER() AS total_row_count')
        )
            ->when($request->name, function ($q) use ($request) {
                $q->where('purities.name', 'like', '%' . $request->name . '%');
            })
            ->when($request->percentage, function ($q) use ($request) {
                $q->where('purities.percentage', 'like', '%' . $request->percentage . '%');
            })
            ->when($request->description, function ($q) use ($request) {
                $q->where('purities.description', 'like', '%' . $request->description . '%');
            })
            ->when($request->globalSearch, function ($q) use ($request) {
                $q->where(function ($subQuery) use ($request) {
                    $subQuery->where('name', 'like', '%' . $request->globalSearch . '%')
                        ->orWhere('percentage', 'like', '%' . $request->globalSearch . '%')
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
            $validator = Validator::make(
                $request->all(),
                [
                    'name'        => 'required|unique:purities,name,NULL,id,deleted_at,NULL',
                    'percentage'  => 'required|numeric|min:0|max:100',
                ],
                [
                    'name.required'       => 'The Karat Value field is required.',
                    'percentage.required' => 'The Purity% field is required.',
                    'percentage.numeric'  => 'The Purity% must be a number.',
                    'percentage.min'      => 'The Purity% must be at least 0.',
                    'percentage.max'      => 'The Purity% may not be greater than 100.',
                ],
                [
                    'name'       => 'Karat Value',
                    'percentage' => 'Purity%',
                ]
            );

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors(),
                    'code'   => 422
                ], 422);
            }

            $purity = new Purity();
            $purity->name        = $request->name;
            $purity->karat_value = $request->karat_value;
            $purity->percentage  = $request->percentage;
            $purity->description = $request->description;
            $purity->status      = $request->status ?? 'A';
            $purity->save();

            return response([
                'status'  => 'success',
                'message' => 'Purity added successfully!',
                'data'    => $purity,
            ], 200);
        } catch (\Exception $e) {
            return response([
                'error' => 'Something went wrong. ' . $e->getMessage(),
                'code'  => 500
            ], 500);
        }
    }

    public function show($id)
    {
        $id = (int) base64_decode($id, true) ?: $id;
        return Purity::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'name'        => 'required|unique:purities,name,' . $id . ',id,deleted_at,NULL',
                    'percentage'  => 'required|numeric|min:0|max:100',
                ],
                [
                    'name.required'       => 'The Karat Value field is required.',
                    'percentage.required' => 'The Purity% field is required.',
                    'percentage.numeric'  => 'The Purity% must be a number.',
                    'percentage.min'      => 'The Purity% must be at least 0.',
                    'percentage.max'      => 'The Purity% may not be greater than 100.',
                ],
                [
                    'name'       => 'Karat Value',
                    'percentage' => 'Purity%',
                ]
            );

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors(),
                    'code'   => 422
                ], 422);
            }

            $purity = Purity::find($id);
            if (!$purity) {
                return response([
                    'error' => 'Purity not found.',
                    'code'  => 404
                ], 404);
            }

            $purity->name        = $request->name;
            $purity->karat_value = $request->karat_value;
            $purity->percentage  = $request->percentage;
            $purity->description = $request->description;
            $purity->status      = $request->status ?? 'A';
            $purity->save();

            return response([
                'data'    => $purity,
                'message' => 'Purity Updated Successfully!',
                'status'  => 'success'
            ], 200);
        } catch (\Exception $e) {
            return response([
                'error' => 'Something went wrong. ' . $e->getMessage(),
                'code'  => 500
            ], 500);
        }
    }

    public function destroy($id)
    {
        $purity = Purity::findOrFail($id);
        $purity->delete();
        return response()->json(['status' => 'deleted', 'message' => 'Deleted Successfully!'], 200);
    }

    public function changeStatus($id)
    {
        $purity = Purity::findOrFail($id);
        $purity->status = $purity->status === 'A' ? 'I' : 'A';
        $purity->save();
        $statusText = $purity->status === 'A' ? 'Active' : 'Inactive';
        return response()->json([
            'message' => 'Status changed to ' . $statusText,
            'status' => 'success'
        ]);
    }
}
