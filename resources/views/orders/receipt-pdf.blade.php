<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { color: #1f2933; font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 0; }
        .receipt { padding: 12px; }
        .header { border-bottom: 1px solid #d7dee8; margin-bottom: 10px; padding-bottom: 8px; text-align: center; }
        .header h1 { font-size: 15px; margin: 0 0 3px; }
        .header p { color: #5f6b7a; margin: 1px 0; }
        .meta { margin-bottom: 10px; width: 100%; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .label { color: #5f6b7a; }
        .items { border-collapse: collapse; margin-bottom: 10px; width: 100%; }
        .items th { border-bottom: 1px solid #d7dee8; color: #5f6b7a; font-size: 8px; padding: 5px 2px; text-align: left; text-transform: uppercase; }
        .items td { border-bottom: 1px solid #edf1f5; padding: 5px 2px; vertical-align: top; }
        .right { text-align: right; }
        .totals { border-bottom: 1px solid #d7dee8; border-top: 1px solid #d7dee8; margin: 10px 0; padding: 6px 0; width: 100%; }
        .totals td { padding: 3px 0; }
        .payments { margin-top: 8px; width: 100%; }
        .payments td { padding: 3px 0; }
        .footer { color: #5f6b7a; margin-top: 14px; text-align: center; }
    </style>
</head>
<body>
@php
    $businessName = 'Musa Jan Frozen Foods';
    $gross = $order->grossTotal();
    $returned = $order->returnedAmount();
    $total = $order->total();
    $received = $order->receivedAmount();
    $balance = max($total - $received, 0);
    $customerBalance = $order->customer?->totalPendingBalance() ?? $balance;
    $balancePayment = \App\Models\AuditLog::query()
        ->where('action', 'customer.balance_payment_on_sale')
        ->where('auditable_type', \App\Models\Order::class)
        ->where('auditable_id', $order->id)
        ->latest()
        ->first()?->properties ?? [];
    $previousBalancePaid = (float) ($balancePayment['amount'] ?? 0);
    $paymentTotals = $order->payments
        ->groupBy(fn ($payment) => $payment->method ?? 'cash')
        ->map(fn ($payments): float => round((float) $payments->sum('amount'), 2));
    $paymentMethods = collect(\App\Models\Payment::METHOD_LABELS)
        ->map(fn (string $label, string $method): array => [
            'label' => $label,
            'amount' => (float) ($paymentTotals[$method] ?? 0),
        ])
        ->filter(fn (array $payment): bool => $payment['amount'] > 0);
@endphp
<main class="receipt">
    <header class="header">
        <h1>{{ $businessName }}</h1>
        <p>Sales Invoice #{{ $order->id }}</p>
        @if(config('settings.shop_address'))<p>{{ config('settings.shop_address') }}</p>@endif
        @if(config('settings.shop_phone'))<p>{{ config('settings.shop_phone') }}</p>@endif
    </header>

    <table class="meta">
        <tr><td class="label">Customer</td><td class="right">{{ $order->getCustomerName() }}</td></tr>
        <tr><td class="label">Mobile</td><td class="right">{{ $order->customer?->phone ?: 'N/A' }}</td></tr>
        <tr><td class="label">Cashier</td><td class="right">{{ optional($order->user)->name }}</td></tr>
        <tr><td class="label">Date</td><td class="right">{{ $order->created_at->format('M d, Y h:i A') }}</td></tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Rate</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product?->name ?? $item->custom_item_name ?? 'Product removed' }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ config('settings.currency_symbol') }} {{ number_format($item->unitPrice(), 2) }}</td>
                    <td class="right">{{ config('settings.currency_symbol') }} {{ number_format($item->subtotal(), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ config('settings.currency_symbol') }} {{ number_format($gross, 2) }}</td></tr>
        @if($returned > 0)
            <tr><td>Returned</td><td class="right">-{{ config('settings.currency_symbol') }} {{ number_format($returned, 2) }}</td></tr>
        @endif
        <tr><td><strong>Grand Total</strong></td><td class="right"><strong>{{ config('settings.currency_symbol') }} {{ number_format($total, 2) }}</strong></td></tr>
        <tr><td>Paid</td><td class="right">{{ config('settings.currency_symbol') }} {{ number_format($received, 2) }}</td></tr>
        <tr><td>Balance</td><td class="right">{{ config('settings.currency_symbol') }} {{ number_format($balance, 2) }}</td></tr>
        @if($previousBalancePaid > 0)
            <tr><td>Previous Balance Paid</td><td class="right">{{ config('settings.currency_symbol') }} {{ number_format($previousBalancePaid, 2) }}</td></tr>
        @endif
        <tr><td>Customer Balance</td><td class="right">{{ config('settings.currency_symbol') }} {{ number_format($customerBalance, 2) }}</td></tr>
    </table>

    <table class="payments">
        @foreach($paymentMethods as $payment)
            <tr><td>{{ $payment['label'] }}</td><td class="right">{{ config('settings.currency_symbol') }} {{ number_format($payment['amount'], 2) }}</td></tr>
        @endforeach
    </table>

    <footer class="footer">{{ config('settings.receipt_footer', 'Thank you for shopping with Musa Jan Frozen Foods.') }}</footer>
</main>
</body>
</html>
