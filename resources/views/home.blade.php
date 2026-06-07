@extends('layouts.admin')

@section('content-header', __('dashboard.title'))

@section('content-actions')
    <a href="{{ route('cart.index') }}" class="btn btn-primary">
        <i class="fas fa-cash-register mr-1"></i>
        {{ __('cart.title') }}
    </a>
@endsection

@section('content')
<div class="container-fluid dashboard-page">
    <div class="dashboard-metrics">
        <a href="{{ route('orders.index') }}" class="metric-card metric-sales">
            <span>{{ __('dashboard.Income_Today') }}</span>
            <strong>{{ config('settings.currency_symbol') }} {{ number_format($income_today, 2) }}</strong>
            <small>{{ __('dashboard.today_sales_hint') }}</small>
        </a>
        <a href="{{ route('orders.index') }}" class="metric-card metric-orders">
            <span>{{ __('dashboard.Orders_Count') }}</span>
            <strong>{{ $orders_count }}</strong>
            <small>{{ $unpaid_orders_count }} {{ __('dashboard.open_balances') }}</small>
        </a>
        <a href="{{ route('reports.business') }}" class="metric-card metric-customers">
            <span>Net Profit Today</span>
            <strong>{{ config('settings.currency_symbol') }} {{ number_format($net_profit_today, 2) }}</strong>
            <small>{{ config('settings.currency_symbol') }} {{ number_format($expenses_today, 2) }} expenses</small>
        </a>
        <a href="{{ route('products.index') }}" class="metric-card metric-stock">
            <span>{{ __('dashboard.stock_alerts') }}</span>
            <strong>{{ $low_stock_count }}</strong>
            <small>{{ $out_of_stock_count }} {{ __('dashboard.out_of_stock') }}</small>
        </a>
        <a href="{{ route('customers.index') }}" class="metric-card metric-customers">
            <span>{{ __('dashboard.Customers_Count') }}</span>
            <strong>{{ $customers_count }}</strong>
            <small>{{ $active_products_count }} / {{ $products_count }} {{ __('dashboard.active_products') }}</small>
        </a>
    </div>

    <div class="row">
        <div class="col-xl-7">
            <div class="card table-card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('dashboard.recent_orders') }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('orders.index') }}" class="btn btn-tool">{{ __('common.More_info') }}</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('order.ID') }}</th>
                                    <th>{{ __('order.Customer_Name') }}</th>
                                    <th>{{ __('order.Total') }}</th>
                                    <th>{{ __('order.Status') }}</th>
                                    <th>{{ __('order.Created_At') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latest_orders as $order)
                                    @php
                                        $orderTotal = $order->total();
                                        $orderReceived = $order->receivedAmount();
                                    @endphp
                                    <tr>
                                        <td><a href="{{ route('orders.show', $order) }}">#{{ $order->id }}</a></td>
                                        <td>{{ $order->getCustomerName() }}</td>
                                        <td>{{ config('settings.currency_symbol') }} {{ number_format($orderTotal, 2) }}</td>
                                        <td>
                                            @if($orderReceived == 0)
                                                <span class="badge badge-danger">{{ __('order.Not_Paid') }}</span>
                                            @elseif($orderReceived < $orderTotal)
                                                <span class="badge badge-warning">{{ __('order.Partial') }}</span>
                                            @else
                                                <span class="badge badge-success">{{ __('order.Paid') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $order->created_at->format('M d, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">{{ __('dashboard.no_recent_orders') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card table-card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('dashboard.low_stock_products') }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('products.index') }}" class="btn btn-tool">{{ __('common.More_info') }}</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @forelse($low_stock_products as $product)
                        <div class="stock-row">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                            <div>
                                <strong>{{ $product->name }}</strong>
                                <span>{{ $product->barcode }}</span>
                            </div>
                            <span class="badge badge-{{ $product->quantity <= 0 ? 'danger' : 'warning' }}">{{ $product->quantity }}</span>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">{{ __('dashboard.stock_is_healthy') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-header">
            <h3 class="card-title">{{ __('dashboard.best_selling_products') }}</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('product.Name') }}</th>
                            <th>{{ __('product.Barcode') }}</th>
                            <th>{{ __('product.Price') }}</th>
                            <th>{{ __('product.Quantity') }}</th>
                            <th>{{ __('dashboard.units_sold') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($best_selling_products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->barcode }}</td>
                                <td>{{ config('settings.currency_symbol') }} {{ number_format((float) $product->price, 2) }}</td>
                                <td>{{ $product->quantity }}</td>
                                <td>{{ $product->total_sold }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">{{ __('dashboard.no_sales_data') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
