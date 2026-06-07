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
