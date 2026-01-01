<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attribute;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\AttributeValue;
use App\Models\AttributeOption;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sortBy');
        $sortDesc = $request->get('sortDesc') === 'true';
        $direction = $sortDesc ? 'DESC' : 'ASC';

        $sortBy = !empty($sortBy) ? $sortBy : 'id';
        $attribute = Attribute::leftjoin('attribute_values', 'attribute_values.attribute_id', 'attributes.id')
            ->leftjoin('attribute_options', 'attribute_options.id', 'attributes.data_type')
            ->select(
                'attributes.*',
                'attribute_values.value as value',
                'attribute_values.display_order as display_order',
                'attribute_options.name as data_type',
                DB::raw('count(*) OVER() AS total_row_count')
            )
            ->where(function ($query) use ($request) {
                if (!empty($request->name)) {
                    $query->where('attributes.name', 'like', '%' . $request->name . '%');
                }
                if (!empty($request->globalSearch)) {
                    $query->where('attributes.name', 'like', '%' . $request->globalSearch . '%')
                        ->orWhere('attribute_values.value', 'like', '%' . $request->globalSearch . '%')
                        ->orWhere('attribute_values.display_order', 'like', '%' . $request->globalSearch . '%');
                }
            })->orderBy($sortBy, $direction);

        if ($request->has('page') && $request->has('perPage')) {
            $page = (int)$request->page;
            $perPage = (int)$request->perPage;

            $data = $attribute->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();
        } else {
            $data = $attribute->get();
        }

        return response([
            'data' => $data,
            'status' => 'success'
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            $existing = Attribute::where('name', $request->name)
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
                    'regex:/^[A-Za-z ]+$/',
                    Rule::unique('attributes', 'name')->whereNull('deleted_at'),
                ],
                'value' => 'required',
            ], [
                'name.required' => 'Please enter attribute name.',
                'name.regex' => 'Attribute name must contain alphabets only.',
                'name.unique' => 'The name has already been taken.',
                'value.required' => 'Please enter option value.',
            ]);

            if ($validator->fails()) {
                return response(['errors' => $validator->errors()->messages(), 'code' => 422], 422);
            }

        
            $attribute = new Attribute();
            $attribute->name = $request->name;
            $attribute->description = $request->description;
            $attribute->data_type = $request->data_type ?? '';
            $attribute->status = $request->status ?? 'A';
            $attribute->save();

            $attributeValue = new AttributeValue();
            $attributeValue->attribute_id = $attribute->id;
            $attributeValue->display_order = $request->display_order ?? 0;
            $attributeValue->value = $request->value;
            $attributeValue->save();

            return response(['data' => 'Added Successfully!', 'status' => 'success'], 200);
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
        return Attribute::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        try {
            $existing = Attribute::where('name', $request->name)
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
                    'regex:/^[A-Za-z ]+$/',
                    Rule::unique('attributes', 'name')
                        ->ignore($id)
                        ->whereNull('deleted_at'),
                ],
                'display_order' => 'required|integer',
                'value' => 'required',
            ], [
                'name.required' => 'Please enter attribute name.',
                'name.regex' => 'Attribute name must contain alphabets only.',
                'name.unique' => 'The name has already been taken.',
            ]);

            if ($validator->fails()) {
                return response(['errors' => $validator->errors()->messages(), 'code' => 422], 422);
            }

            $attribute = Attribute::find($id);
            if (!$attribute) {
                return response(['message' => 'Attribute not found', 'status' => 'error'], 404);
            }

            $existingAttributeValue = AttributeValue::where('attribute_id', $attribute->id)->first();

            $attribute->name = $request->name;
            $attribute->description = $request->description;
            $attribute->data_type = $request->data_type ?? '';
            $attribute->status = $request->status ?? 'A';
            $attribute->save();

            if ($existingAttributeValue) {
                $existingAttributeValue->display_order = $request->display_order ?? 0;
                $existingAttributeValue->value = $request->value;
                $existingAttributeValue->save();
            } else {
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'display_order' => $request->display_order,
                    'value' => $request->value,
                ]);
            }

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
        $attribute = Attribute::findOrFail($id);
        $attribute->delete();
        return response()->json(['status' => 'deleted', 'message' => 'Deleted Successfully!'], 200);
    }

    public function attributeDataTypeOption()
    {
        $data = AttributeOption::get();
        return response(['data' => $data, 'status' => 'success'], 200);
    }

    public function changeStatus($id)
    {
        $attribute = Attribute::findOrFail($id);
        $attribute->status = $attribute->status === 'A' ? 'I' : 'A';
        $attribute->save();
        $statusText = $attribute->status === 'A' ? 'Active' : 'Inactive';
        return response()->json([
            'message' => 'Status changed to ' . $statusText,
            'status' => 'success'
        ]);
    }
}
