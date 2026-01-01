<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tax;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sortBy');
        $sortDesc = $request->get('sortDesc') === 'true';
        $direction = $sortDesc ? 'DESC' : 'ASC';

        $sortBy = !empty($sortBy) ? $sortBy : 'id';

        $query = Tax::select(
            'taxes.*',
            DB::raw('count(*) OVER() AS total_row_count')
        )
            ->when($request->tax_name, function ($q) use ($request) {
                $q->where('taxes.tax_name', 'like', '%' . $request->tax_name . '%');
            })
            ->when($request->tax_rate, function ($q) use ($request) {
                $q->where('taxes.tax_rate', 'like', '%' . $request->tax_rate . '%');
            })
            ->when($request->globalSearch, function ($q) use ($request) {
                $q->where('taxes.tax_name', 'like', '%' . $request->globalSearch . '%')
                ->orWhere('taxes.tax_rate', 'like', '%' . $request->globalSearch . '%')
                ->orWhere('taxes.description', 'like', '%' . $request->globalSearch . '%');
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
            $validator = Validator::make($request->all(), [
                'tax_name' => [
                    'required',
                    'regex:/^[a-zA-Z0-9 ]+$/',
                    'unique:taxes,tax_name,NULL,id,deleted_at,NULL'
                ],
                'tax_rate' => [
                    'required',
                    'numeric',
                    'gt:0'
                ],
            ], [
                'tax_name.regex' => 'Tax name must contain only letters and numbers.',
                'tax_rate.gt' => 'Tax rate must be greater than 0.',
            ]);

            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }

            $tax = new Tax();
            $tax->tax_name = $request->tax_name;
            $tax->tax_rate = $request->tax_rate;
            $tax->description = $request->description;
            $tax->save();

            return response(['status' => 'success', 'message' => 'Tax added successfully!'], 200);
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
        return Tax::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'tax_name' => [
                    'required',
                    'regex:/^[a-zA-Z0-9 ]+$/',
                    Rule::unique('taxes', 'tax_name')
                        ->ignore($id)
                        ->whereNull('deleted_at'),
                ],
                'tax_rate' => [
                    'required',
                    'numeric',
                    'gt:0'
                ],
            ], [
                'tax_name.regex' => 'Tax name must contain only letters and numbers.',
                'tax_rate.gt' => 'Tax rate must be greater than 0.',
            ]);
            
            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }

            $tax = Tax::find($id);

            if (!$tax) {
                return response(['error' => 'Tax not found.', 'code' => 404], 404);
            }

            $tax->tax_name = $request->tax_name;
            $tax->tax_rate = $request->tax_rate;
            $tax->description = $request->description;
            $tax->save();

            return response(['data' => 'Updated Successfully!', 'status' => 'success'], 200);
        } catch (\Exception $e) {
            return response([
                'error' => 'Something went wrong. ' . $e->getMessage(),
                'code' => 500
            ], 500);
        }
    }


    public function destroy($id)
    {
        $tax = Tax::findOrFail($id);
        $tax->delete();
        return response()->json(['status' => 'deleted', 'message' => 'Deleted Successfully!'], 200);
    }
}
