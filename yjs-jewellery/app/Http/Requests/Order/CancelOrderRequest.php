<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:500',
            'reason_code' => 'nullable|string|in:changed_mind,found_better_price,ordered_by_mistake,taking_too_long,payment_issue,other',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Cancellation reason is required.',
            'reason.max' => 'Reason cannot exceed 500 characters.',
            'reason_code.in' => 'Invalid reason code.',
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
