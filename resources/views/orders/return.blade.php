@extends('layouts.admin')

@section('title', 'Sales Return')
@section('content-header', 'Sales Return / Invoice Cancellation')

@section('content-actions')
    <a href="{{ route('orders.index') }}" class="btn btn-secondary">Back to Orders</a>
@endsection

@section('content')
@php
    $currency = config('settings.currency_symbol');
@endphp

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Invoice Summary</h3></div>
            <div class="card-body">
                <p class="mb-1"><strong>Invoice:</strong> #{{ $order->id }}</p>
                <p class="mb-1"><strong>Customer:</strong> {{ $order->getCustomerName() }}</p>
                <p class="mb-1"><strong>Date:</strong> {{ $order->created_at->format('M d, Y h:i A') }}</p>
                <hr>
                <p class="mb-1"><strong>Gross:</strong> {{ $currency }} {{ number_format($order->grossTotal(), 2) }}</p>
                <p class="mb-1"><strong>Already Returned:</strong> {{ $currency }} {{ number_format($order->returnedAmount(), 2) }}</p>
                <p class="mb-1"><strong>Discount:</strong> {{ $currency }} {{ number_format($order->discountAmount(), 2) }}</p>
                <p class="mb-1"><strong>Net Total:</strong> {{ $currency }} {{ number_format($order->total(), 2) }}</p>
                <p class="mb-0"><strong>Balance:</strong> {{ $currency }} {{ number_format(max($order->remainingBalance(), 0), 2) }}</p>
                <hr>
                <p class="text-muted mb-0">
                    Stock, customer balance, sales, and profit reports adjust automatically after the return is processed.
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <form method="POST" action="{{ route('orders.return.store', $order) }}" class="card">
            @csrf
            <div class="card-header"><h3 class="card-title">Return Items</h3></div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Return Type</label>
                        <select name="type" id="return_type" class="form-control">
                            <option value="partial" @selected(old('type', 'partial') === 'partial')>Partial product return</option>
                            <option value="full" @selected(old('type') === 'full')>Full invoice return / cancel</option>
                        </select>
                    </div>
                    <div class="form-group col-md-8">
                        <label>Return Reason</label>
                        <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" required>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Sold</th>
                                <th>Returned</th>
                                <th>Available</th>
                                <th>Return Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $item->product?->name ?? $item->custom_item_name ?? 'Product removed' }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ $item->returned_quantity }}</td>
                                    <td>{{ $item->returnableQuantity() }}</td>
                                    <td style="width: 160px">
                                        <input
                                            type="number"
                                            name="items[{{ $item->id }}]"
                                            class="form-control return-qty"
                                            min="0"
                                            max="{{ $item->returnableQuantity() }}"
                                            data-max="{{ $item->returnableQuantity() }}"
                                            step="0.01"
                                            value="{{ old("items.{$item->id}", 0) }}"
                                            {{ $item->returnableQuantity() <= 0 ? 'disabled' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-undo mr-1"></i>
                    Process Return
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
    $(function () {
        function syncReturnQuantities() {
            var isFullReturn = $('#return_type').val() === 'full';

            $('.return-qty').each(function () {
                var max = $(this).data('max');
                $(this)
                    .val(isFullReturn ? max : 0)
                    .prop('readonly', isFullReturn);
            });
        }

        $('#return_type').on('change', syncReturnQuantities);
        if ($('#return_type').val() === 'full') {
            syncReturnQuantities();
        }
    });
</script>
@endsection
