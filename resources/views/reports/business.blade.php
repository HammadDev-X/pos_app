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
        ['Received', $received, 'bg-success', 'fa-money-bill'],
        ['Customer Due', $due, 'bg-warning', 'fa-clock'],
        ['Net Profit', $netProfit, $netProfit >= 0 ? 'bg-primary' : 'bg-danger', 'fa-chart-line'],
    ] as [$label, $value, $color, $icon])
        <div class="col-md-3">
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
        <div class="col-md-3">
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
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Profit Breakdown</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    <tr><th>Orders</th><td>{{ $ordersCount }}</td></tr>
                    <tr><th>Gross Sales</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $grossSales, 2) }}</td></tr>
                    <tr><th>Sales Returns</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $salesReturns, 2) }}</td></tr>
                    <tr><th>Discounts</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $discounts, 2) }}</td></tr>
                    <tr><th>Net Sales</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $sales, 2) }}</td></tr>
                    <tr><th>Cost of Products Sold</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $cost, 2) }}</td></tr>
                    <tr><th>Gross Profit</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $grossProfit, 2) }}</td></tr>
                    <tr><th>Expenses</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $expenses, 2) }}</td></tr>
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
