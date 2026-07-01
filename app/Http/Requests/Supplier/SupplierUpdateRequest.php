<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class SupplierUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:suppliers,email,' . $this->supplier->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:png,jpg,jpeg|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => __('supplier.validation.first_name_required'),
            'first_name.max' => __('supplier.validation.first_name_max'),
            'last_name.required' => __('supplier.validation.last_name_required'),
            'last_name.max' => __('supplier.validation.last_name_max'),
            'email.email' => __('supplier.validation.email_invalid'),
            'phone.max' => __('supplier.validation.phone_max'),
            'avatar.image' => __('supplier.validation.avatar_image'),
            'avatar.mimes' => __('The avatar must be a PNG or JPG file.'),
            'avatar.max' => __('The avatar must not be larger than 10MB.'),
        ];
    }
}
