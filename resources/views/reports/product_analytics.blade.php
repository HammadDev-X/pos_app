@extends('layouts.admin')

@section('title', 'Product Analytics')
@section('content-header', 'Product Analytics')

@php
    $currency = config('settings.currency_symbol');
    $percent = fn ($value) => number_format((float) $value, 1) . '%';
    $money = fn ($value) => $currency . number_format((float) $value, 2);
    $qty = fn ($value) => number_format((float) $value, 2);
    $topSoldMax = max(1, (float) ($topSoldProducts->first()->total_sold ?? 0));
    $topRevenueMax = max(1, (float) ($topRevenueProducts->first()->revenue ?? 0));
@endphp

@section('content-actions')
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-boxes"></i> Products
    </a>
@endsection

@section('content')
<form method="GET" action="{{ route('reports.product-analytics') }}" class="card card-body mb-3">
    <div class="row align-items-end">
        <div class="col-md-4">
            <label>Date From</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label>Date To</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
        </div>
        <div class="col-md-4">
            <button class="btn btn-primary" type="submit">
                <i class="fas fa-filter"></i> Apply Filter
            </button>
            <a href="{{ route('reports.product-analytics') }}" class="btn btn-light">Reset</a>
        </div>
    </div>
</form>

<div class="row">
    @foreach([
        ['Total Revenue', $money($summary['total_revenue']), 'bg-info', 'fa-chart-line'],
        ['Units Sold', $qty($summary['total_units_sold']), 'bg-success', 'fa-shopping-basket'],
        ['Gross Profit', $money($summary['total_profit']), $summary['total_profit'] >= 0 ? 'bg-primary' : 'bg-danger', 'fa-coins'],
        ['Products With Sales', $summary['products_with_sales'] . ' / ' . $summary['total_products'], 'bg-secondary', 'fa-tags'],
    ] as [$label, $value, $color, $icon])
        <div class="col-lg-3 col-md-6">
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
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header border-0">
                <h3 class="card-title">Most Selling Product</h3>
            </div>
            <div class="card-body">
                @if($mostSellingProduct)
                    <div class="d-flex align-items-center">
                        <img src="{{ $mostSellingProduct->image_url }}" alt="{{ $mostSellingProduct->name }}" class="rounded mr-3" style="width:82px;height:82px;object-fit:cover;border:1px solid #e5e7eb;">
                        <div>
                            <h4 class="mb-1">{{ $mostSellingProduct->name }}</h4>
                            <div class="text-muted">{{ $mostSellingProduct->category_name ?: 'Uncategorized' }}</div>
                            <div class="mt-2">
                                <span class="badge badge-success">{{ $qty($mostSellingProduct->total_sold) }} {{ $mostSellingProduct->unit }} sold</span>
                                <span class="badge badge-light">{{ $money($mostSellingProduct->revenue) }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0">No products found.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header border-0">
                <h3 class="card-title">Revenue Leader</h3>
            </div>
            <div class="card-body">
                @if($highestRevenueProduct)
                    <h4 class="mb-1">{{ $highestRevenueProduct->name }}</h4>
                    <div class="text-muted mb-3">{{ $highestRevenueProduct->orders_count }} orders in selected range</div>
                    <table class="table table-sm mb-0">
                        <tr><th>Revenue</th><td class="text-right">{{ $money($highestRevenueProduct->revenue) }}</td></tr>
                        <tr><th>Avg Unit Price</th><td class="text-right">{{ $money($highestRevenueProduct->average_unit_price) }}</td></tr>
                        <tr><th>Revenue Share</th><td class="text-right">{{ $summary['total_revenue'] > 0 ? $percent(($highestRevenueProduct->revenue / $summary['total_revenue']) * 100) : '0.0%' }}</td></tr>
                    </table>
                @else
                    <p class="text-muted mb-0">No revenue yet.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header border-0">
                <h3 class="card-title">Inventory Health</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th>Retail Stock Value</th><td class="text-right">{{ $money($summary['inventory_value']) }}</td></tr>
                    <tr><th>Cost Stock Value</th><td class="text-right">{{ $money($summary['inventory_cost_value']) }}</td></tr>
                    <tr><th>Low Stock</th><td class="text-right"><span class="badge badge-warning">{{ $summary['low_stock_count'] }}</span></td></tr>
                    <tr><th>Out Of Stock</th><td class="text-right"><span class="badge badge-danger">{{ $summary['out_of_stock_count'] }}</span></td></tr>
                    <tr><th>No Sales In Range</th><td class="text-right"><span class="badge badge-secondary">{{ $summary['dead_stock_count'] }}</span></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">Top Products By Quantity</h3>
            </div>
            <div class="card-body">
                @forelse($topSoldProducts as $product)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <strong>{{ $product->name }}</strong>
                            <span>{{ $qty($product->total_sold) }} {{ $product->unit }}</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-success" style="width: {{ min(100, ((float) $product->total_sold / $topSoldMax) * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No product sales in this date range.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">Top Products By Revenue</h3>
            </div>
            <div class="card-body">
                @forelse($topRevenueProducts as $product)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <strong>{{ $product->name }}</strong>
                            <span>{{ $money($product->revenue) }}</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-info" style="width: {{ min(100, ((float) $product->revenue / $topRevenueMax) * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No product revenue in this date range.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">Every Product Performance</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-right">Sold</th>
                                <th class="text-right">Revenue</th>
                                <th class="text-right">Profit</th>
                                <th class="text-right">Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded mr-2" style="width:38px;height:38px;object-fit:cover;border:1px solid #e5e7eb;">
                                            <div>
                                                <strong>{{ $product->name }}</strong>
                                                <div class="text-muted small">{{ $product->category_name ?: 'Uncategorized' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $product->sku ?: '-' }}</td>
                                    <td class="text-right">{{ $qty($product->total_sold) }}</td>
                                    <td class="text-right">{{ $money($product->revenue) }}</td>
                                    <td class="text-right {{ $product->profit < 0 ? 'text-danger' : 'text-success' }}">{{ $money($product->profit) }}</td>
                                    <td class="text-right">{{ $product->track_stock ? $qty($product->stock) . ' ' . $product->unit : 'Not tracked' }}</td>
                                    <td>
                                        @if(!$product->status)
                                            <span class="badge badge-secondary">Inactive</span>
                                        @elseif($product->track_stock && $product->stock <= 0)
                                            <span class="badge badge-danger">Out</span>
                                        @elseif($product->track_stock && $product->stock <= config('settings.warning_quantity', 10))
                                            <span class="badge badge-warning">Low</span>
                                        @elseif($product->total_sold <= 0)
                                            <span class="badge badge-light">No sales</span>
                                        @else
                                            <span class="badge badge-success">Selling</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted">No products found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">Stock Watchlist</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Product</th><th class="text-right">Stock</th><th class="text-right">Value</th></tr></thead>
                    <tbody>
                        @forelse($lowStockProducts as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td class="text-right"><span class="badge badge-warning">{{ $qty($product->stock) }}</span></td>
                                <td class="text-right">{{ $money($product->stock_value) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">No low stock products.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0">
                <h3 class="card-title">Unsold Stock In Range</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Product</th><th class="text-right">Stock</th><th class="text-right">Retail Value</th></tr></thead>
                    <tbody>
                        @forelse($deadStockProducts as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td class="text-right">{{ $qty($product->stock) }}</td>
                                <td class="text-right">{{ $money($product->stock_value) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">Every stocked product sold in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
