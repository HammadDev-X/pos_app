@extends('layouts.admin')

@section('content-header', __('dashboard.title'))

@section('content-actions')
    <a href="{{ route('cart.index') }}" class="btn btn-primary">
        <i class="fas fa-cash-register mr-1"></i>
        {{ __('cart.title') }}
    </a>
@endsection

@section('content')
@php($canManage = auth()->user()?->isManager())
<div class="container-fluid dashboard-page">
    <div class="dashboard-metrics">
        <a href="{{ route('orders.index') }}" class="metric-card metric-sales">
            <span>Today’s Sales</span>
            <strong>{{ config('settings.currency_symbol') }} {{ number_format($income_today, 2) }}</strong>
            <small>{{ __('dashboard.today_sales_hint') }}</small>
        </a>
        <a href="{{ $canManage ? route('reports.business') : route('orders.index') }}" class="metric-card metric-sales">
            <span>This Month’s Sales</span>
            <strong>{{ config('settings.currency_symbol') }} {{ number_format($sales_this_month, 2) }}</strong>
            <small>Month-to-date invoice total</small>
        </a>
        <a href="{{ route('orders.index') }}" class="metric-card metric-orders">
            <span>Total Invoices</span>
            <strong>{{ $orders_count }}</strong>
            <small>{{ $unpaid_orders_count }} open balances</small>
        </a>
        <a href="{{ route('orders.index') }}" class="metric-card metric-cash">
            <span>Cash Sales</span>
            <strong>{{ config('settings.currency_symbol') }} {{ number_format($cash_sales, 2) }}</strong>
            <small>Payments received by cash</small>
        </a>
        <a href="{{ route('customers.index') }}" class="metric-card metric-credit">
            <span>Credit Sales</span>
            <strong>{{ config('settings.currency_symbol') }} {{ number_format($credit_sales, 2) }}</strong>
            <small>Outstanding customer balances</small>
        </a>
        <a href="{{ route('orders.index') }}" class="metric-card metric-recovery">
            <span>Credit Recovery</span>
            <strong>{{ config('settings.currency_symbol') }} {{ number_format((float) $recovery_payments, 2) }}</strong>
            <small>Received payments after invoice date</small>
        </a>
        <a href="{{ route('customers.index') }}" class="metric-card metric-orders">
            <span>Total Receivable</span>
            <strong>{{ config('settings.currency_symbol') }} {{ number_format($total_receivable, 2) }}</strong>
            <small>{{ $customers_with_balance_count }} customers with balance</small>
        </a>
        <a href="{{ $canManage ? route('reports.business') : route('expenses.index') }}" class="metric-card metric-customers">
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
            <small>{{ $active_customers_count }} active customers</small>
        </a>
    </div>

    <div class="dashboard-ledger-grid">
        <div class="card table-card ledger-card">
            <div class="card-header">
                <h3 class="card-title">Expense Management</h3>
            </div>
            <div class="card-body">
                <div class="ledger-metrics">
                    <a href="{{ route('expenses.index') }}" class="ledger-metric ledger-total">
                        <span>Total Expenses</span>
                        <strong>{{ config('settings.currency_symbol') }} {{ number_format($total_expenses, 2) }}</strong>
                    </a>
                    <a href="{{ route('expenses.index', ['category' => 'Petrol']) }}" class="ledger-metric">
                        <span>Petrol Expense</span>
                        <strong>{{ config('settings.currency_symbol') }} {{ number_format($petrol_expense, 2) }}</strong>
                    </a>
                    <a href="{{ route('expenses.index', ['category' => 'Packaging']) }}" class="ledger-metric">
                        <span>Packaging Expense</span>
                        <strong>{{ config('settings.currency_symbol') }} {{ number_format($packaging_expense, 2) }}</strong>
                    </a>
                    <a href="{{ route('expenses.index', ['category' => 'Delivery']) }}" class="ledger-metric">
                        <span>Delivery Expense</span>
                        <strong>{{ config('settings.currency_symbol') }} {{ number_format($delivery_expense, 2) }}</strong>
                    </a>
                    <a href="{{ route('expenses.index', ['category' => 'Salary']) }}" class="ledger-metric">
                        <span>Salary Expense</span>
                        <strong>{{ config('settings.currency_symbol') }} {{ number_format($salary_expense, 2) }}</strong>
                    </a>
                    <a href="{{ route('expenses.index') }}" class="ledger-metric">
                        <span>Other Expenses</span>
                        <strong>{{ config('settings.currency_symbol') }} {{ number_format($other_expenses, 2) }}</strong>
                    </a>
                </div>
            </div>
        </div>

        <div class="card table-card ledger-card">
            <div class="card-header">
                <h3 class="card-title">Customer Management & Ledger</h3>
            </div>
            <div class="card-body">
                <div class="ledger-metrics customer-ledger-metrics">
                    <a href="{{ route('customers.index') }}" class="ledger-metric ledger-total">
                        <span>Total Customers</span>
                        <strong>{{ $customers_count }}</strong>
                    </a>
                    <a href="{{ route('customers.index') }}" class="ledger-metric">
                        <span>Active Customers</span>
                        <strong>{{ $active_customers_count }}</strong>
                    </a>
                    <a href="{{ route('customers.index') }}" class="ledger-metric">
                        <span>Customers with Pending Balance</span>
                        <strong>{{ $customers_with_balance_count }}</strong>
                    </a>
                    <a href="{{ route('customers.index') }}" class="ledger-metric receivable-metric">
                        <span>Total Receivable Amount (total udhaar)</span>
                        <strong>{{ config('settings.currency_symbol') }} {{ number_format($total_receivable, 2) }}</strong>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card table-card">
                <div class="card-header"><h3 class="card-title">Monthly Sales Calendar / Day-wise Sales Graph</h3></div>
                <div class="card-body">
                    @php
                        $maxMonthlySales = max((float) collect($monthly_calendar)->max('sales'), 1);
                    @endphp
                    <div class="monthly-sales-chart">
                        @foreach($monthly_calendar as $day)
                            @php
                                $salesAmount = (float) $day['sales'];
                                $barHeight = max(($salesAmount / $maxMonthlySales) * 100, $salesAmount > 0 ? 8 : 0);
                            @endphp
                            <div class="sales-bar" title="{{ $day['date'] }}: {{ config('settings.currency_symbol') }} {{ number_format($salesAmount, 2) }}">
                                <div class="sales-bar-track">
                                    <span style="height: {{ $barHeight }}%"></span>
                                </div>
                                <small>{{ $day['day'] }}</small>
                            </div>
                        @endforeach
                    </div>
                    <div class="monthly-sales-grid">
                        @foreach($monthly_calendar as $day)
                            <div class="monthly-sales-day {{ $day['sales'] > 0 ? 'has-sales' : '' }}">
                                <span>{{ $day['day'] }}</span>
                                <strong>{{ number_format((float) $day['sales'], 0) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card table-card">
                <div class="card-header"><h3 class="card-title">Expense Summary</h3></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tr><th>Total Expenses</th><td>{{ config('settings.currency_symbol') }} {{ number_format($total_expenses, 2) }}</td></tr>
                        <tr><th>Credit Sales</th><td>{{ config('settings.currency_symbol') }} {{ number_format($credit_sales, 2) }}</td></tr>
                        <tr><th>Credit Recovery</th><td>{{ config('settings.currency_symbol') }} {{ number_format((float) $recovery_payments, 2) }}</td></tr>
                        @foreach($expense_breakdown as $expense)
                            <tr>
                                <th>{{ $expense->category }}</th>
                                <td>{{ config('settings.currency_symbol') }} {{ number_format((float) $expense->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
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
                                <span>{{ $product->sku }}</span>
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
                            <th>SKU</th>
                            <th>{{ __('product.Price') }}</th>
                            <th>{{ __('product.Quantity') }}</th>
                            <th>{{ __('dashboard.units_sold') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($best_selling_products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->sku }}</td>
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
