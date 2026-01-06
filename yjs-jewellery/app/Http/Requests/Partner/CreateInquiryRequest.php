<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expected_delivery_date' => 'required|date|after:today',
            'priority' => 'required|in:normal,high,urgent',
            'notes' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.specifications' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'expected_delivery_date.required' => 'Expected delivery date is required.',
            'expected_delivery_date.after' => 'Delivery date must be in the future.',
            'priority.required' => 'Priority is required.',
            'priority.in' => 'Priority must be normal, high, or urgent.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.product_id.required' => 'Product is required for each item.',
            'items.*.product_id.exists' => 'Selected product does not exist.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
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
