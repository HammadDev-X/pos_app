<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class OrderStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment_method' => $this->input('payment_method', 'cash'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'discount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'due_date' => ['nullable', 'date'],
            'payment_method' => ['required', 'string', 'in:cash,card,bank_transfer,mobile_money'],
            'custom_items' => ['nullable', 'array'],
            'custom_items.*.name' => ['required_with:custom_items', 'string', 'max:255'],
            'custom_items.*.price' => ['required_with:custom_items', 'numeric', 'min:0.01', 'decimal:0,2'],
            'custom_items.*.quantity' => ['required_with:custom_items', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.exists' => __('order.validation.customer_not_found'),
            'amount.required' => __('order.validation.amount_required'),
            'amount.min' => __('order.validation.amount_min'),
            'amount.decimal' => __('order.validation.amount_decimal'),
            'payment_method.required' => __('order.validation.payment_method_required'),
            'payment_method.in' => __('order.validation.payment_method_invalid'),
        ];
    }
}
