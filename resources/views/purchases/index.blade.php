@extends('layouts.admin')

@section('title', __('All Purchases'))
@section('content-header', __('All Purchases'))
@section('content-actions')
<a href="{{ route('purchases.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> {{ __('New Purchase') }}
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('purchases.index') }}" class="mb-3">
            <div class="row">
                <div class="col-md-2">
                    <select name="status" class="form-control">
                        <option value="">{{ __('All Statuses') }}</option>
                        @foreach(['pending' => __('Pending'), 'completed' => __('Completed'), 'cancelled' => __('Cancelled')] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="supplier_id" class="form-control">
                        <option value="">{{ __('All Suppliers') }}</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>
                                {{ $supplier->first_name }} {{ $supplier->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search') }}">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            <a href="{{ route('purchases.index') }}" class="btn btn-default"><i class="fas fa-redo"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('ID') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Supplier') }}</th>
                        <th>{{ __('Items') }}</th>
                        <th>{{ __('Transport') }}</th>
                        <th>{{ __('Other Cost') }}</th>
                        <th>{{ __('Total Amount') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        <tr>
                            <td><a href="{{ route('purchases.show', $purchase) }}">#{{ $purchase->id }}</a></td>
                            <td>{{ optional($purchase->purchase_date)->format('Y-m-d') }}</td>
                            <td>{{ $purchase->supplier->first_name }} {{ $purchase->supplier->last_name }}</td>
                            <td><span class="badge badge-info">{{ $purchase->items->count() }}</span></td>
                            <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $purchase->transport_cost, 2) }}</td>
                            <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $purchase->other_cost, 2) }}</td>
                            <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $purchase->total_amount, 2) }}</td>
                            <td>
                                <span class="badge badge-{{ ['completed' => 'success', 'pending' => 'warning', 'cancelled' => 'danger'][$purchase->status] ?? 'secondary' }}">
                                    {{ ucfirst($purchase->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-info"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('purchases.receipt', $purchase) }}" class="btn btn-success" target="_blank"><i class="fas fa-print"></i></a>
                                    <form method="POST" action="{{ route('purchases.destroy', $purchase) }}" onsubmit="return confirm('{{ __('Delete this purchase?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <p>{{ __('No purchases found') }}</p>
                                <a href="{{ route('purchases.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> {{ __('Create First Purchase') }}
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $purchases->render() }}
    </div>
</div>
@endsection
