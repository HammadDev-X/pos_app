<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CustomerStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => $this->localPhoneDigits($this->input('phone')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:20'],
            'last_name' => ['required', 'string', 'max:20'],
            'customer_code' => ['required', 'string', 'max:30', 'unique:customers,customer_code'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'pending_amount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => __('customer.validation.first_name_required'),
            'first_name.max' => __('customer.validation.first_name_max'),
            'last_name.required' => __('customer.validation.last_name_required'),
            'last_name.max' => __('customer.validation.last_name_max'),
            'customer_code.required' => __('Customer code is required.'),
            'customer_code.unique' => __('This customer code is already in use.'),
            'email.email' => __('customer.validation.email_invalid'),
            'phone.regex' => __('Phone number must be exactly 10 digits after +92.'),
        ];
    }

    private function localPhoneDigits($phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($digits) === 12 && str_starts_with($digits, '92')) {
            return substr($digits, 2);
        }

        return $digits;
    }
}
