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
        $businessName = 'Musa Jan Frozen Foods';
        $customerMobile = $order->customer?->phone ?: 'N/A';
        $whatsappPhone = preg_replace('/\D+/', '', (string) $order->customer?->phone);
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
        $receiptPdfPath = \Illuminate\Support\Facades\URL::signedRoute('orders.receipt-pdf', $order, null, false);
        $receiptPdfUrl = rtrim(config('app.receipt_public_url'), '/') . $receiptPdfPath;
        $paymentTotals = $order->payments
            ->groupBy(fn ($payment) => $payment->method ?? 'cash')
            ->map(fn ($payments): float => round((float) $payments->sum('amount'), 2));
        $paymentMethods = collect(\App\Models\Payment::METHOD_LABELS)
            ->map(fn (string $label, string $method): array => [
                'label' => $label,
                'amount' => (float) ($paymentTotals[$method] ?? 0),
                'used' => (float) ($paymentTotals[$method] ?? 0) > 0,
            ])
            ->filter(fn (array $payment): bool => $payment['used']);
        $itemLines = $order->items
            ->map(function ($item): string {
                $name = $item->product?->name ?? $item->custom_item_name ?? 'Product removed';
                $currency = config('settings.currency_symbol');

                return "- {$name}\n  Qty: {$item->quantity} | Rate: {$currency} " . number_format($item->unitPrice(), 2) . " | Discount: {$currency} " . number_format($item->discountAmount(), 2) . " | Total: {$currency} " . number_format($item->subtotal(), 2);
            })
            ->implode("\n");
        $paymentLines = $paymentMethods
            ->map(fn (array $payment): string => "- {$payment['label']}: " . config('settings.currency_symbol') . ' ' . number_format($payment['amount'], 2))
            ->implode("\n");
        $whatsappMessage = rawurlencode(
            "Assalam o Alaikum {$order->getCustomerName()}\n\n" .
            "{$businessName}\n" .
            "Sales Invoice #{$order->id}\n" .
            "Date & Time: {$order->created_at->format('M d, Y h:i A')}\n" .
            "Customer: {$order->getCustomerName()}\n" .
            "Mobile: {$customerMobile}\n" .
            "Cashier: " . optional($order->user)->name . "\n\n" .
            "Items:\n{$itemLines}\n\n" .
            "Subtotal: " . config('settings.currency_symbol') . ' ' . number_format($gross, 2) . "\n" .
            ($returned > 0 ? "Returned: -" . config('settings.currency_symbol') . ' ' . number_format($returned, 2) . "\n" : '') .
            "Grand Total: " . config('settings.currency_symbol') . ' ' . number_format($total, 2) . "\n" .
            "Paid Amount: " . config('settings.currency_symbol') . ' ' . number_format($received, 2) . "\n" .
            "Remaining Balance: " . config('settings.currency_symbol') . ' ' . number_format($balance, 2) . "\n\n" .
            ($previousBalancePaid > 0 ? "Previous Balance Paid: " . config('settings.currency_symbol') . ' ' . number_format($previousBalancePaid, 2) . "\n" : '') .
            "Customer Remaining Balance: " . config('settings.currency_symbol') . ' ' . number_format($customerBalance, 2) . "\n\n" .
            "Payment Methods:\n" . ($paymentLines ?: 'No payment recorded.') . "\n\n" .
            config('settings.receipt_footer', 'Thank you for shopping with Musa Jan Frozen Foods.')
        );
    @endphp

    <main class="receipt">
        <header class="receipt-header">
            <img src="{{ asset('images/logo.png') }}" alt="{{ $businessName }} logo" style="max-width: 72px; margin-bottom: 8px;">
            <h1>{{ $businessName }}</h1>
            <p>Sales Invoice</p>
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
                <span>Invoice</span>
                <strong>#{{ $order->id }}</strong>
            </div>
            <div>
                <span>Cashier</span>
                <strong>{{ optional($order->user)->name }}</strong>
            </div>
            <div>
                <span>Customer Name</span>
                <strong>{{ $order->getCustomerName() }}</strong>
            </div>
            <div>
                <span>Mobile Number</span>
                <strong>{{ $customerMobile }}</strong>
            </div>
            <div>
                <span>Date &amp; Time</span>
                <strong>{{ $order->created_at->format('M d, Y h:i A') }}</strong>
            </div>
        </section>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Rate</th>
                    <th>Discount</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product?->name ?? $item->custom_item_name ?? 'Product removed' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ config('settings.currency_symbol') }} {{ number_format($item->unitPrice(), 2) }}</td>
                        <td>{{ config('settings.currency_symbol') }} {{ number_format($item->discountAmount(), 2) }}</td>
                        <td>{{ config('settings.currency_symbol') }} {{ number_format($item->subtotal(), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <section class="receipt-totals">
            <div><span>Subtotal</span><strong>{{ config('settings.currency_symbol') }} {{ number_format($gross, 2) }}</strong></div>
            @if($returned > 0)
                <div><span>Returned</span><strong>-{{ config('settings.currency_symbol') }} {{ number_format($returned, 2) }}</strong></div>
            @endif
            <div><span>Grand Total</span><strong>{{ config('settings.currency_symbol') }} {{ number_format($total, 2) }}</strong></div>
            <div><span>Paid Amount</span><strong>{{ config('settings.currency_symbol') }} {{ number_format($received, 2) }}</strong></div>
            <div><span>Remaining Balance</span><strong>{{ config('settings.currency_symbol') }} {{ number_format($balance, 2) }}</strong></div>
            @if($previousBalancePaid > 0)
                <div><span>Previous Balance Paid</span><strong>{{ config('settings.currency_symbol') }} {{ number_format($previousBalancePaid, 2) }}</strong></div>
            @endif
            <div><span>Customer Remaining Balance</span><strong>{{ config('settings.currency_symbol') }} {{ number_format($customerBalance, 2) }}</strong></div>
        </section>

        <section class="receipt-payments">
            <h2>Payment Methods</h2>
            @forelse($paymentMethods as $payment)
                <div>
                    <span>
                        {{ $payment['label'] }}
                        <small class="receipt-payment-status is-used">
                            Used
                        </small>
                    </span>
                    <strong>{{ config('settings.currency_symbol') }} {{ number_format($payment['amount'], 2) }}</strong>
                </div>
            @empty
                <p class="text-muted mb-0">No payment recorded.</p>
            @endforelse
        </section>

        <footer class="receipt-footer">
            <p>{{ config('settings.receipt_footer', 'Thank you for shopping with Musa Jan Frozen Foods.') }}</p>
            <div class="receipt-actions">
                <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
                <a href="{{ $receiptPdfUrl }}" target="_blank" class="btn btn-default">
                    <i class="fas fa-file-pdf mr-1"></i>
                    Open PDF
                </a>
                @if($whatsappPhone)
                    <a href="https://wa.me/{{ $whatsappPhone }}?text={{ $whatsappMessage }}" target="_blank" class="btn btn-success">
                        <i class="fab fa-whatsapp mr-1"></i>
                        Send Details to WhatsApp
                    </a>
                @endif
            </div>
        </footer>
    </main>
</body>
</html>
