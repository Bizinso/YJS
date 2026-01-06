<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'billing_address_id' => 'required|integer|exists:customer_addresses,id',
            'shipping_address_id' => 'required|integer|exists:customer_addresses,id',
            'coupon_code' => 'nullable|string|max:50',
            'offer_id' => 'nullable|integer|exists:offers,id',
            'notes' => 'nullable|string|max:500',
            'use_loyalty_points' => 'nullable|boolean',
            'referral_code' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'billing_address_id.required' => 'Billing address is required.',
            'billing_address_id.exists' => 'Selected billing address does not exist.',
            'shipping_address_id.required' => 'Shipping address is required.',
            'shipping_address_id.exists' => 'Selected shipping address does not exist.',
            'coupon_code.max' => 'Coupon code is too long.',
            'offer_id.exists' => 'Selected offer does not exist.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
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
