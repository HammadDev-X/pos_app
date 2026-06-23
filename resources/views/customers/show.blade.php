@extends('layouts.admin')

@section('title', 'Customer Ledger')
@section('content-header', 'Customer Ledger')

@section('content-actions')
    <a href="{{ route('customers.index') }}" class="btn btn-secondary">Back to Customers</a>
@endsection

@section('content')
@php
    $currency = config('settings.currency_symbol');
    $message = rawurlencode("Assalam o Alaikum {$customer->full_name}, your pending balance is {$currency} " . number_format($balance, 2) . '. Please arrange payment. Thank you.');
    $phone = preg_replace('/\D+/', '', (string) $customer->phone);
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <img src="{{ $customer->avatar_url }}" alt="{{ $customer->full_name }}" class="rounded mr-3" width="72" height="72">
                    <div>
                        <h4 class="mb-1">{{ $customer->full_name }}</h4>
                        <p class="mb-0"><span class="badge badge-info">{{ $customer->customer_code }}</span></p>
                        <p class="mb-0 text-muted">{{ $customer->phone ?: 'No mobile number' }}</p>
                    </div>
                </div>
                <hr>
                <p class="mb-1"><strong>Total Sales:</strong> {{ $currency }} {{ number_format($totalSales, 2) }}</p>
                <p class="mb-1"><strong>Paid / Recovered:</strong> {{ $currency }} {{ number_format($totalPaid, 2) }}</p>
                <p class="mb-3"><strong>Pending Balance:</strong> {{ $currency }} {{ number_format($balance, 2) }}</p>
                @if($phone && $balance > 0)
                    <a href="https://wa.me/{{ $phone }}?text={{ $message }}" target="_blank" class="btn btn-success btn-block">
                        <i class="fab fa-whatsapp mr-1"></i>
                        Send WhatsApp Reminder
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Ledger Entries</h3></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice</th>
                            <th>Net Sale</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>{{ $order->created_at->format('M d, Y') }}</td>
                                <td><a href="{{ route('orders.show', $order) }}">#{{ $order->id }}</a></td>
                                <td>{{ $currency }} {{ number_format($order->total(), 2) }}</td>
                                <td>{{ $currency }} {{ number_format($order->receivedAmount(), 2) }}</td>
                                <td>{{ $currency }} {{ number_format(max($order->remainingBalance(), 0), 2) }}</td>
                                <td>
                                    @if($order->isCancelled())
                                        <span class="badge badge-dark">Cancelled</span>
                                    @elseif($order->remainingBalance() > 0)
                                        <span class="badge badge-warning">Pending</span>
                                    @else
                                        <span class="badge badge-success">Paid</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center py-4">No sales for this customer yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
