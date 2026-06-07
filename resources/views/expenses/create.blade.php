@extends('layouts.admin')

@section('title', 'Add Expense')
@section('content-header', 'Add Expense')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('expenses.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="expense_date">Date</label>
                <input type="date" name="expense_date" id="expense_date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ old('expense_date', now()->toDateString()) }}" required>
                @error('expense_date')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <input list="expense-categories" type="text" name="category" id="category" class="form-control @error('category') is-invalid @enderror" value="{{ old('category') }}" placeholder="Rent, salary, electricity..." required>
                <datalist id="expense-categories">
                    @foreach($categories as $category)
                        <option value="{{ $category }}"></option>
                    @endforeach
                </datalist>
                @error('category')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                @error('amount')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <input type="text" name="description" id="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description') }}" placeholder="Optional note">
                @error('description')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <button class="btn btn-primary" type="submit">Save Expense</button>
            <a href="{{ route('expenses.index') }}" class="btn btn-default">Cancel</a>
        </form>
    </div>
</div>
@endsection
