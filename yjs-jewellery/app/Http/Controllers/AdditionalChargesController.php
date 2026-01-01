<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdditionalCharges;
use App\Models\AdditionalChargeType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdditionalChargesController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sortBy');
        $sortDesc = $request->get('sortDesc') === 'true';
        $direction = $sortDesc ? 'DESC' : 'ASC';

        $sortBy = !empty($sortBy) ? $sortBy : 'id';
        $additionalCharges = AdditionalCharges::leftjoin(
            'additional_charge_types',
            'additional_charge_types.id',
            'additional_charges.type_id'
        )
            ->select(
                'additional_charges.*',
                'additional_charge_types.name as charges_type_name',
                DB::raw('count(*) OVER() AS total_row_count')
            )
            ->where(function ($query) use ($request) {

                if (!empty($request->type_id)) {
                    $query->where('additional_charge_types.id', 'like', '%' . $request->type_id . '%');
                }
                if (!empty($request->globalSearch)) {
                    $query->where('additional_charge_types.name', 'like', '%' . $request->globalSearch . '%')
                    ->orWhere('additional_charges.charges_type', 'like', '%' . $request->globalSearch . '%')
                    ->orWhere('additional_charges.amount', 'like', '%' . $request->globalSearch . '%')
                    ->orWhere('additional_charges.description', 'like', '%' . $request->globalSearch . '%');
                }
            })
            ->orderBy($sortBy, $direction);

        if ($request->has('page') && $request->has('perPage')) {
            $page = (int)$request->page;
            $perPage = (int)$request->perPage;

            $data = $additionalCharges->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();
        } else {
            $data = $additionalCharges->get();
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
                    'charges_type_name' => [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('additional_charge_types', 'name')->whereNull('deleted_at'),
                    ],
                ],
                [
                    'charges_type_name.required' => 'Additional Charges Type is required.',
                    'charges_type_name.unique'   => 'The Additional Charges Type has already been taken.',
                ]
            );


            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }

            $chargesType = new AdditionalChargeType();
            $chargesType->name = $request->charges_type_name;
            $chargesType->save();

            $additionalCharges = new AdditionalCharges();
            $additionalCharges->type_id = $chargesType->id;
            $additionalCharges->amount = $request->amount ?: 0;
            $additionalCharges->description = $request->description;
            $additionalCharges->save();
           
            return response([
                'data' => 'Additional Charge Added Successfully!',
                'status' => 'success'
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
        $additionalCharges = AdditionalCharges::where('additional_charges.id', $id)
            ->leftJoin('additional_charge_types', 'additional_charges.type_id', '=', 'additional_charge_types.id')
            ->select(
                'additional_charges.*',
                'additional_charge_types.name as type_name'
            )
            ->first();

        if (!$additionalCharges) {
            return response(['message' => 'Record not found', 'status' => 'error'], 404);
        }

        return response(['data' => $additionalCharges, 'status' => 'success'], 200);
    }


    public function update(Request $request, $id)
    {
            $addiCharges = AdditionalChargeType::find($request->type_id);
        try {
            $validator = Validator::make($request->all(), [
                'charges_type_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('additional_charge_types', 'name')
                    ->ignore($addiCharges->id)
                    ->whereNull('deleted_at'),
                ],
            ], [
                'charges_type_name.required' => 'Additional Charges Type is required.',
                'charges_type_name.unique' => 'The Additional Charges Type has already been taken.',
            ]);


            if ($validator->fails()) {
                return response([
                    'errors' => $validator->errors()->messages(),
                    'code' => 422
                ], 422);
            }
            $oldData = AdditionalCharges::getAdditionalChargesInfo($id);
            $additionalCharges = AdditionalCharges::find($id);



            if ($additionalCharges->type_id) {
                $chargesType = AdditionalChargeType::find($additionalCharges->type_id);
                if ($chargesType) {
                    $chargesType->name = $request->charges_type_name;
                    $chargesType->save();
                } else {
                    $chargesType = new AdditionalChargeType();
                    $chargesType->name = $request->charges_type_name;
                    $chargesType->save();
                }
            } else {
                $chargesType = new AdditionalChargeType();
                $chargesType->name = $request->charges_type_name;
                $chargesType->save();
            }

          
            $additionalCharges->type_id = $chargesType->id;
            $additionalCharges->amount = $request->amount ?: 0;
            $additionalCharges->description = $request->description;
            $additionalCharges->save();

            return response([
                'data' => 'Additional Charge Updated Successfully!',
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
        $additionalCharges = AdditionalCharges::findOrFail($id);
        $additionalChargesType = AdditionalChargeType::find($additionalCharges->type_id);
        if ($additionalChargesType) {
            $additionalChargesType->delete();
        }

        $additionalCharges->delete();

        return response()->json([
            'status' => 'deleted',
            'message' => 'Additional Charge Deleted Successfully!'
        ], 200);
    }

    public function additionalChargesTypesOptions()
    {
        $data = AdditionalChargeType::get();
        return response(['data' => $data, 'status' => 'success'], 200);
    }
}
