<?php

namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class AddToPurchaseCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => __('Product is required'),
            'product_id.exists' => __('Product not found'),
        ];
    }
}
