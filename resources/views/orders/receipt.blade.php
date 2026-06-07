<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt #{{ $order->id }}</title>
    @vite(['resources/sass/app.scss'])
</head>
<body class="receipt-page">
    @php
        $total = $order->total();
        $received = $order->receivedAmount();
        $balance = max($total - $received, 0);
    @endphp

    <main class="receipt">
        <header class="receipt-header">
            <h1>{{ config('settings.app_name', config('app.name')) }}</h1>
            @if(config('settings.shop_address'))
                <p>{{ config('settings.shop_address') }}</p>
            @endif
            @if(config('settings.shop_phone'))
                <p>{{ config('settings.shop_phone') }}</p>
            @endif
            @if(config('settings.tax_number'))
                <p>Tax No: {{ config('settings.tax_number') }}</p>
            @endif
        </header>

        <section class="receipt-meta">
            <div>
                <span>Receipt</span>
                <strong>#{{ $order->id }}</strong>
            </div>
            <div>
                <span>Cashier</span>
                <strong>{{ optional($order->user)->name }}</strong>
            </div>
            <div>
                <span>Customer</span>
                <strong>{{ $order->getCustomerName() }}</strong>
            </div>
            <div>
                <span>Date</span>
                <strong>{{ $order->created_at->format('M d, Y h:i A') }}</strong>
            </div>
        </section>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ optional($item->product)->name ?? 'Product removed' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ config('settings.currency_symbol') }} {{ number_format($item->unitPrice(), 2) }}</td>
                        <td>{{ config('settings.currency_symbol') }} {{ number_format($item->subtotal(), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <section class="receipt-totals">
            <div><span>Total</span><strong>{{ config('settings.currency_symbol') }} {{ number_format($total, 2) }}</strong></div>
            <div><span>Paid</span><strong>{{ config('settings.currency_symbol') }} {{ number_format($received, 2) }}</strong></div>
            <div><span>Balance</span><strong>{{ config('settings.currency_symbol') }} {{ number_format($balance, 2) }}</strong></div>
        </section>

        <section class="receipt-payments">
            <h2>Payments</h2>
            @foreach($order->payments as $payment)
                <div>
                    <span>{{ ucfirst(str_replace('_', ' ', $payment->method ?? 'cash')) }}</span>
                    <strong>{{ config('settings.currency_symbol') }} {{ $payment->formattedAmount() }}</strong>
                </div>
            @endforeach
        </section>

        <footer class="receipt-footer">
            <p>{{ config('settings.receipt_footer', 'Thank you for your purchase.') }}</p>
            <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
        </footer>
    </main>
</body>
</html>
