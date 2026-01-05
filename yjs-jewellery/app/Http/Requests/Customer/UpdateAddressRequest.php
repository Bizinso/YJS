<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'alternate_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address_line1' => ['sometimes', 'required', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'landmark' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'state' => ['sometimes', 'required', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'required', 'string', 'max:20'],
            'country_id' => ['sometimes', 'required', 'integer', 'exists:countries,id'],
            'is_default' => ['sometimes', 'boolean'],
            'address_type' => ['sometimes', 'string', 'in:home,office,other'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Full name is required for the address.',
            'full_name.max' => 'Full name cannot exceed 100 characters.',
            'email.email' => 'Please provide a valid email address.',
            'phone.required' => 'Phone number is required for the address.',
            'address_line1.required' => 'Address line 1 is required.',
            'address_line1.max' => 'Address line 1 cannot exceed 255 characters.',
            'city.required' => 'City is required.',
            'state.required' => 'State is required.',
            'postal_code.required' => 'Postal code is required.',
            'postal_code.max' => 'Postal code cannot exceed 20 characters.',
            'country_id.required' => 'Country is required.',
            'country_id.exists' => 'Selected country is invalid.',
            'address_type.in' => 'Address type must be home, office, or other.',
        ];
    }
}
