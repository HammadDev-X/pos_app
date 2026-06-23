@extends('layouts.admin')

@section('title', 'Stock Adjustments')
@section('content-header', 'Stock Adjustments')
@section('content-actions')
<a href="{{ route('stock-adjustments.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> Adjust Stock
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('stock-adjustments.index') }}" class="mb-3">
            <div class="row">
                <div class="col-md-5">
                    <select name="product_id" class="form-control">
                        <option value="">All Products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Filter</button>
                    <a href="{{ route('stock-adjustments.index') }}" class="btn btn-default"><i class="fas fa-redo"></i></a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>Before</th>
                        <th>After</th>
                        <th>Reason</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adjustment)
                        <tr>
                            <td>{{ $adjustment->created_at }}</td>
                            <td>{{ $adjustment->product->name }}</td>
                            <td><span class="badge badge-secondary">{{ $types[$adjustment->type] ?? ucfirst(str_replace('_', ' ', $adjustment->type)) }}</span></td>
                            <td>{{ $adjustment->quantity }}</td>
                            <td>{{ $adjustment->quantity_before }}</td>
                            <td>{{ $adjustment->quantity_after }}</td>
                            <td>{{ $adjustment->reason }}</td>
                            <td>{{ $adjustment->user->getFullname() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No stock adjustments found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $adjustments->render() }}
    </div>
</div>
@endsection
