<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $customerRule = Rule::exists('customers', 'id');

        if ($this->user()?->isSalesman()) {
            $customerRule->where('is_visible_to_salesman', true);
        }

        return [
            'customer_id' => ['nullable', 'integer', $customerRule],
            'amount' => ['required_without:payments', 'numeric', 'min:0', 'decimal:0,2'],
            'due_date' => ['nullable', 'date'],
            'payment_method' => ['required', 'string', 'in:cash,card,bank_transfer,mobile_money,jazzcash,easypaisa,account,credit,cash_account,loan'],
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', 'string', 'in:cash,card,bank_transfer,mobile_money,jazzcash,easypaisa,account,credit'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0', 'decimal:0,2'],
            'item_discounts' => ['nullable', 'array'],
            'item_discounts.*' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'custom_items' => ['nullable', 'array'],
            'custom_items.*.name' => ['required_with:custom_items', 'string', 'max:255'],
            'custom_items.*.price' => ['required_with:custom_items', 'numeric', 'min:0.01', 'decimal:0,2'],
            'custom_items.*.quantity' => ['required_with:custom_items', 'integer', 'min:1'],
            'custom_items.*.discount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
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
