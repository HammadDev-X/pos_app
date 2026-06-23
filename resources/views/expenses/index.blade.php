@extends('layouts.admin')

@section('title', 'Expenses')
@section('content-header', 'Expenses')
@section('content-actions')
<a href="{{ route('expenses.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> Add Expense
</a>
@endsection

@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ config('settings.currency_symbol') }}{{ number_format((float) $total, 2) }}</h3>
                <p>Filtered Expenses</p>
            </div>
            <div class="icon"><i class="fas fa-receipt"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ config('settings.currency_symbol') }}{{ number_format((float) $dailyExpense, 2) }}</h3>
                <p>Daily Expense</p>
            </div>
            <div class="icon"><i class="fas fa-calendar-day"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ config('settings.currency_symbol') }}{{ number_format((float) $monthlyExpense, 2) }}</h3>
                <p>Monthly Expense</p>
            </div>
            <div class="icon"><i class="fas fa-calendar-alt"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Category-wise Expense</h3></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    @forelse($categoryWiseExpense as $row)
                        <tr>
                            <th>{{ $row['category'] }}</th>
                            <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td class="text-muted">No expense categories found</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Expense Comparison by Month</h3></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    @forelse($expenseComparisonByMonth as $row)
                        <tr>
                            <th>{{ $row['label'] }}</th>
                            <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td class="text-muted">No monthly expenses found</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Expense vs Sales Comparison</h3></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Month</th><th>Sales</th><th>Expense</th></tr></thead>
                    <tbody>
                    @forelse($expenseVsSalesComparison as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['sales'], 2) }}</td>
                            <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $row['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No comparison data found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('expenses.index') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Filter</button>
                    <a href="{{ route('expenses.index') }}" class="btn btn-default"><i class="fas fa-redo"></i></a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Recorded By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td>{{ $expense->expense_date?->format('Y-m-d') }}</td>
                            <td><span class="badge badge-secondary">{{ $expense->category }}</span></td>
                            <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $expense->amount, 2) }}</td>
                            <td>{{ $expense->description }}</td>
                            <td>{{ $expense->user->getFullname() }}</td>
                            <td>
                                <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No expenses found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $expenses->render() }}
    </div>
</div>
@endsection
