<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Product;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'], // 2MB max
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($this->product)
            ],
            'short_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'short_code')->ignore($this->product)
            ],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock_level' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'in:pack,kg,piece,carton'],
            'track_stock' => ['nullable', 'boolean'],
            'is_quick_item' => ['nullable', 'boolean'],
            
            'status' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => $this->cleanString($this->input('sku')),
            'short_code' => $this->cleanString($this->input('short_code')),
            'unit' => $this->cleanString($this->input('unit')),
        ]);

        if (!$this->input('sku')) {
            $this->merge(['sku' => Product::generateSku()]);
        }

        if (!$this->filled('short_code')) {
            $this->merge(['short_code' => null]);
        }
        if (!$this->has('unit')) {
            $this->merge(['unit' => 'piece']);
        }
        if (!$this->has('track_stock')) {
            $this->merge(['track_stock' => true]);
        }
        if (!$this->has('is_quick_item')) {
            $this->merge(['is_quick_item' => false]);
        }
    }

    private function cleanString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function messages(): array
    {
        return [
            'name.required' => __('product.validation.name_required'),
            'price.required' => __('product.validation.price_required'),
            'price.decimal' => __('product.validation.price_decimal'),
            'quantity.required' => __('product.validation.quantity_required'),
            'quantity.min' => __('product.validation.quantity_min'),
            'image.max' => __('product.validation.image_max'),
        ];
    }
}
