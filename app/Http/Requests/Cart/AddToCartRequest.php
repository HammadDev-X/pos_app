<?php

namespace App\Http\Requests\Cart;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barcode' => ['nullable', 'string'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('barcode')) {
            $barcode = trim((string) $this->input('barcode'));
            $this->merge(['barcode' => $barcode === '' ? null : $barcode]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (!$this->filled('barcode') && !$this->filled('product_id')) {
                $validator->errors()->add('barcode', __('cart.validation.product_not_found'));
                return;
            }

            if ($this->filled('barcode')) {
                $term = $this->input('barcode');
                $exists = Product::query()
                    ->where(function ($query) use ($term): void {
                        $query->where('barcode', $term)
                            ->orWhere('sku', $term)
                            ->orWhere('short_code', $term)
                            ->orWhere('id', $term)
                            ->orWhere('name', 'LIKE', "%{$term}%");
                    })
                    ->exists();

                if (!$exists) {
                    $validator->errors()->add('barcode', __('cart.validation.product_not_found'));
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => __('cart.validation.product_not_found'),
        ];
    }
}
