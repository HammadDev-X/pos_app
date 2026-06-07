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
        ['Sales', $sales, 'bg-info', 'fa-shopping-cart'],
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
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Profit Breakdown</h3></div>
            <div class="card-body p-0">
                <table class="table">
                    <tr><th>Orders</th><td>{{ $ordersCount }}</td></tr>
                    <tr><th>Sales Revenue</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $sales, 2) }}</td></tr>
                    <tr><th>Estimated Product Cost</th><td>{{ config('settings.currency_symbol') }}{{ number_format((float) $cost, 2) }}</td></tr>
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
    </div>
</div>
@endsection
