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
            'search' => ['nullable', 'string'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('search')) {
            $search = trim((string) $this->input('search'));
            $this->merge(['search' => $search === '' ? null : $search]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (!$this->filled('search') && !$this->filled('product_id')) {
                $validator->errors()->add('product_id', __('cart.validation.product_not_found'));
                return;
            }

            if ($this->filled('search')) {
                $term = $this->input('search');
                $exists = Product::query()
                    ->where(function ($query) use ($term): void {
                        $query->where('sku', $term)
                            ->orWhere('short_code', $term)
                            ->orWhere('id', $term)
                            ->orWhere('name', 'LIKE', "%{$term}%");
                    })
                    ->exists();

                if (!$exists) {
                    $validator->errors()->add('search', __('cart.validation.product_not_found'));
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
