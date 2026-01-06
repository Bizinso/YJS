<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'description' => 'nullable|string|max:5000',
            'category_id' => 'required|integer|exists:categories,id',
            'purity_id' => 'nullable|integer|exists:purities,id',
            'metal_weight' => 'nullable|numeric|min:0',
            'metal_rate' => 'nullable|numeric|min:0',
            'base_price' => 'required|numeric|min:0',
            'initial_stock' => 'required|integer|min:0',
            'is_featured' => 'nullable|boolean',
            'visibility' => 'nullable|in:visible,hidden,scheduled',
            'status' => 'nullable|in:active,inactive,draft',
            'main_image' => 'nullable|string|max:255',
            'tags_id' => 'nullable|array',
            'tags_id.*' => 'integer|exists:tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'name.max' => 'Product name cannot exceed 255 characters.',
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU already exists.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'base_price.required' => 'Base price is required.',
            'base_price.min' => 'Base price cannot be negative.',
            'initial_stock.required' => 'Initial stock is required.',
            'initial_stock.min' => 'Initial stock cannot be negative.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
