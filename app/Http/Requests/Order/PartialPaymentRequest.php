<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class PartialPaymentRequest extends FormRequest
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
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'payment_method' => ['required', 'string', 'in:cash,card,bank_transfer,mobile_money,jazzcash,easypaisa,account,credit'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => __('order.validation.order_id_required'),
            'order_id.exists' => __('order.validation.order_not_found'),
            'amount.required' => __('order.validation.amount_required'),
            'amount.min' => __('order.validation.amount_min'),
            'amount.decimal' => __('order.validation.amount_decimal'),
            'payment_method.required' => __('order.validation.payment_method_required'),
            'payment_method.in' => __('order.validation.payment_method_invalid'),
        ];
    }
}
