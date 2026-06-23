@extends('layouts.admin')

@section('title', 'Business Reports')
@section('content-header', 'Business Reports')

@section('content')
<form method="GET" action="{{ route('reports.business') }}" class="card card-body mb-3">
    <div class="row">
        <div class="col-md-4">
            <label>Date From</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label>Date To</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-primary" type="submit"><i class="fas fa-chart-line"></i> Run Report</button>
        </div>
    </div>
</form>

<div class="row">
    @foreach([
        ['Net Sales', $sales, 'bg-info', 'fa-shopping-cart'],
        ['Cash Sales', $cashSales, 'bg-success', 'fa-money-bill-wave'],
        ['Account Sales', $accountSales, 'bg-secondary', 'fa-wallet'],
        ['Credit Sales', $creditSales, 'bg-warning', 'fa-hand-holding-usd'],
        ['Received', $received, 'bg-success', 'fa-money-bill'],
        ['Customer Due', $due, 'bg-warning', 'fa-clock'],
        ['Net Profit', $netProfit, $netProfit >= 0 ? 'bg-primary' : 'bg-danger', 'fa-chart-line'],
    ] as [$label, $value, $color, $icon])
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="small-box {{ $color }}">
                <div class="inner">
                    <h3>{{ config('settings.currency_symbol') }}{{ number_format((float) $value, 2) }}</h3>
                    <p>{{ $label }}</p>
                </div>
                <div class="icon"><i class="fas {{ $icon }}"></i></div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    @foreach([
        ['Inventory Qty', number_format((float) $inventoryQuantity, 2), 'bg-secondary', 'fa-boxes', false],
        ['Inventory Cost Value', $inventoryCostValue, 'bg-info', 'fa-warehouse', true],
        ['Inventory Selling Value', $inventorySellingValue, 'bg-success', 'fa-tags', true],
        ['Estimated Margin', $inventoryEstimatedMargin, $inventoryEstimatedMargin >= 0 ? 'bg-primary' : 'bg-danger', 'fa-percentage', true],
    ] as [$label, $value, $color, $icon, $money])
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="small-box {{ $color }}">
                <div class="inner">
                    <h3>{{ $money ? config('settings.currency_symbol') . number_format((float) $value, 2) : $value }}</h3>
                    <p>{{ $label }}</p>
                </div>
                <div class="icon"><i class="fas {{ $icon }}"></i></div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    @foreach([
        ['Daily Report', $dailyReport, 'fa-calendar-day'],
        ['Weekly Report', $weeklyReport, 'fa-calendar-week'],
        ['Monthly Report', $monthlyReport, 'fa-calendar-alt'],
    ] as [$label, $report, $icon])
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas {{ $icon }} mr-1"></i> {{ $label }}</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tr><th>Period</th><td>{{ $report['date_from'] }} to {{ $report['date_to'] }}</td></tr>
                        <tr><th>Orders</th><td>{{ $report['orders_count'] }}</td></tr>
                        <tr><th>Sales</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $report['sales'], 2) }}</td></tr>
                        <tr><th>Received</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $report['received'], 2) }}</td></tr>
                        <tr><th>Credit</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $report['credit'], 2) }}</td></tr>
                        <tr><th>Expenses</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $report['expenses'], 2) }}</td></tr>
                        <tr><th>Net Profit</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $report['net_profit'], 2) }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    @foreach([
        ['Available Stock', $inventoryReport['available_stock_count'], 'bg-success', 'fa-box-open'],
        ['Low Stock Items', $inventoryReport['low_stock_count'], 'bg-warning', 'fa-exclamation-triangle'],
        ['Out-of-stock Items', $inventoryReport['out_of_stock_count'], 'bg-danger', 'fa-ban'],
        ['Expiring Soon Items', $inventoryReport['expiring_soon_count'], 'bg-info', 'fa-hourglass-half'],
    ] as [$label, $value, $color, $icon])
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="small-box {{ $color }}">
                <div class="inner">
                    <h3>{{ $value }}</h3>
                    <p>{{ $label }}</p>
                </div>
                <div class="icon"><i class="fas {{ $icon }}"></i></div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Inventory Report</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    <tr><th>Available Stock</th><td>{{ number_format((float) $inventoryReport['stock_quantity'], 2) }}</td></tr>
                    <tr><th>Stock Cost Value</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $inventoryReport['stock_cost_value'], 2) }}</td></tr>
                    <tr><th>Stock Selling Value</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $inventoryReport['stock_selling_value'], 2) }}</td></tr>
                    <tr><th>Low Stock Items</th><td>{{ $inventoryReport['low_stock_count'] }}</td></tr>
                    <tr><th>Out-of-stock Items</th><td>{{ $inventoryReport['out_of_stock_count'] }}</td></tr>
                    <tr><th>Expiring Soon Items</th><td>{{ $inventoryReport['expiring_soon_count'] }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Expiring Soon Items</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Product</th><th>Category</th><th>Purchased Qty</th><th>Expiry Date</th></tr></thead>
                    <tbody>
                    @forelse($expiringSoonItems as $item)
                        <tr>
                            <td>{{ $item->product?->name ?? 'Deleted product' }}</td>
                            <td>{{ $item->product?->category?->name ?? 'Uncategorized' }}</td>
                            <td>{{ number_format((float) $item->quantity, 2) }}</td>
                            <td>{{ $item->expiry_date?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No expiring soon items</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Profit Breakdown</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    <tr><th>Orders</th><td>{{ $ordersCount }}</td></tr>
                    <tr><th>Gross Sales</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $grossSales, 2) }}</td></tr>
                    <tr><th>Total Sales</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $totalSales, 2) }}</td></tr>
                    <tr><th>Cash Sales / Account Sales</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $cashSales, 2) }} / {{ config('settings.currency_symbol') }}{{ number_format((float) $accountSales, 2) }}</td></tr>
                    <tr><th>Credit Sales</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $creditSales, 2) }}</td></tr>
                    <tr><th>Sales Returns</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $salesReturns, 2) }}</td></tr>
                    <tr><th>Discounts</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $discounts, 2) }}</td></tr>
                    <tr><th>Net Sales</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $sales, 2) }}</td></tr>
                    <tr><th>Cost of Products Sold</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $cost, 2) }}</td></tr>
                    <tr><th>Gross Profit Formula</th><td>Gross Profit = Net Sales - Cost of Products Sold</td></tr>
                    <tr><th>Gross Profit</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $grossProfit, 2) }}</td></tr>
                    <tr><th>Expenses</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $expenses, 2) }}</td></tr>
                    <tr><th>Net Profit Formula</th><td>Net Profit = Gross Profit - Operating Expenses</td></tr>
                    <tr><th>Purchases</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $purchaseTotal, 2) }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Payment Breakdown</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    @forelse($paymentBreakdown as $method => $amount)
                        <tr>
                            <th>{{ ucwords(str_replace('_', ' ', $method)) }}</th>
                            <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td class="text-muted">No payments in this range</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Profit Reports</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    <tr><th>Daily Gross Profit</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $dailyGrossProfit, 2) }}</td></tr>
                    <tr><th>Daily Net Profit</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $dailyNetProfit, 2) }}</td></tr>
                    <tr><th>Monthly Gross Profit</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $monthlyGrossProfit, 2) }}</td></tr>
                    <tr><th>Monthly Net Profit</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $monthlyNetProfit, 2) }}</td></tr>
                    <tr><th>Yearly Profit</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $yearlyProfit['net_profit'], 2) }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Credit Sales History</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Customer</th><th>Due Date</th><th>Balance</th></tr></thead>
                    <tbody>
                    @forelse($creditSalesHistory as $row)
                        <tr>
                            <td><a href="{{ route('orders.show', $row['order_id']) }}">#{{ $row['order_id'] }}</a> {{ $row['customer'] }}</td>
                            <td>{{ $row['due_date'] ?: 'No due date' }}</td>
                            <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No customer-wise credit sales history in this range</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Recovery / Payment Entry</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Customer Name</th><th>Date</th><th>Amount Received via Cash / Accounts</th></tr></thead>
                    <tbody>
                    @forelse($recoveryEntries as $row)
                        <tr>
                            <td>{{ $row['customer'] }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['amount'], 2) }} via {{ ucwords(str_replace('_', ' ', $row['method'])) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No recovery payments in this range</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Product-wise Profit</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    @forelse($productWiseProfit as $row)
                        <tr><th>{{ $row['label'] }}</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['gross_profit'], 2) }}</td></tr>
                    @empty
                        <tr><td class="text-muted">No product profit in this range</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Category-wise Profit</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    @forelse($categoryWiseProfit as $row)
                        <tr><th>{{ $row['label'] }}</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['gross_profit'], 2) }}</td></tr>
                    @empty
                        <tr><td class="text-muted">No category profit in this range</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Customer-wise Profit</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    @forelse($customerWiseProfit as $row)
                        <tr><th>{{ $row['customer'] }}</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['gross_profit'], 2) }}</td></tr>
                    @empty
                        <tr><td class="text-muted">No customer profit in this range</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Recovery Alerts</h3></div>
    <div class="card-body p-0">
        <table class="table">
            <thead><tr><th>Customer</th><th>Due Date</th><th>Balance</th><th>WhatsApp/SMS Reminder</th></tr></thead>
            <tbody>
            @forelse($recoveryAlerts as $row)
                @php
                    $phone = preg_replace('/\D+/', '', (string) $row['phone']);
                    $message = rawurlencode("Payment reminder: invoice #{$row['order_id']} has pending balance " . config('settings.currency_symbol') . number_format((float) $row['balance'], 2) . '.');
                @endphp
                <tr>
                    <td>{{ $row['customer'] }}</td>
                    <td>{{ $row['due_date'] ?: 'No due date' }}</td>
                    <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['balance'], 2) }}</td>
                    <td>
                        @if($phone)
                            <a href="https://wa.me/{{ $phone }}?text={{ $message }}" target="_blank" class="btn btn-sm btn-success">WhatsApp</a>
                            <a href="sms:{{ $phone }}?body={{ $message }}" class="btn btn-sm btn-secondary">SMS</a>
                        @else
                            <span class="text-muted">No phone number</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">No recovery alerts</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Product-wise Stock Value</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Cost Value</th>
                            <th>Selling Value</th>
                            <th>Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productStockValues as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ number_format((float) $row['quantity'], 2) }}</td>
                                <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['cost_value'], 2) }}</td>
                                <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['selling_value'], 2) }}</td>
                                <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['estimated_margin'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No stock value available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Category-wise Stock Value</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Cost</th>
                            <th>Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categoryStockValues as $row)
                            <tr>
                                <td>{{ $row['category'] }}</td>
                                <td>{{ number_format((float) $row['quantity'], 2) }}</td>
                                <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['cost_value'], 2) }}</td>
                                <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['estimated_margin'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No category value available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Best Selling Products</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Product</th><th>Qty Sold</th><th>Sales</th></tr></thead>
                    <tbody>
                        @forelse($topProducts as $row)
                            <tr>
                                <td>{{ $row->product?->name ?? 'Deleted product' }}</td>
                                <td>{{ $row->total_quantity }}</td>
                                <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row->total_sales, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">No sales in this range</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Low Stock Watchlist</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    @forelse($lowStockProducts as $product)
                        <tr>
                            <th>{{ $product->name }}</th>
                            <td><span class="badge badge-danger">{{ $product->quantity }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="text-muted">No low stock products</td></tr>
                    @endforelse
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Open Item Sales</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    <thead><tr><th>Item</th><th>Qty</th><th>Sales</th></tr></thead>
                    <tbody>
                        @forelse($openItems as $row)
                            <tr>
                                <td>{{ $row->custom_item_name }}</td>
                                <td>{{ $row->total_quantity }}</td>
                                <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row->total_sales, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">No open item sales in this range</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
