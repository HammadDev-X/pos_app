@extends('layouts.admin')

@section('title', __('order.Orders_List'))
@section('content-header', __('order.Orders_List'))
@section('content-actions')
    <a href="{{route('cart.index')}}" class="btn btn-primary">{{ __('cart.title') }}</a>
@endsection
@section('content')

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-7"></div>
                <div class="col-md-5">
                    <form action="{{route('orders.index')}}">
                        <div class="row">
                            <div class="col-md-5">
                                <input type="date" name="start_date" class="form-control" value="{{request('start_date')}}" />
                            </div>
                            <div class="col-md-5">
                                <input type="date" name="end_date" class="form-control" value="{{request('end_date')}}" />
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary" type="submit">{{ __('order.submit') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <table class="table">
                <thead>
                <tr>
                    <th>{{ __('order.ID') }}</th>
                    <th>{{ __('order.Customer_Name') }}</th>
                    <th>{{ __('order.Total') }}</th>
                    <th>{{ __('order.Received_Amount') }}</th>
                        <th>{{ __('order.Status') }}</th>
                    <th>Return</th>
                    <th>{{ __('order.To_Pay') }}</th>
                    <th>{{ __('order.Created_At') }}</th>
                    <th>{{ __('order.Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($orders as $order)
                    @php
                        $orderTotal = $order->total();
                        $orderReceived = $order->receivedAmount();
                        $orderRemaining = $orderTotal - $orderReceived;
                        $orderDiscount = $order->discountAmount();
                        $customerMobile = $order->customer?->phone ?: 'N/A';
                        $invoiceItems = $order->items->map(fn ($item): array => [
                            'name' => $item->product?->name ?? $item->custom_item_name ?? 'Product removed',
                            'quantity' => (float) $item->quantity,
                            'rate' => $item->unitPrice(),
                            'total' => $item->subtotal(),
                        ]);
                    @endphp
                    <tr>
                        <td>{{$order->id}}</td>
                        <td>{{$order->getCustomerName()}}</td>
                        <td>{{ config('settings.currency_symbol') }} {{number_format($orderTotal, 2)}}</td>
                        <td>{{ config('settings.currency_symbol') }} {{number_format($orderReceived, 2)}}</td>
                        <td>
                            @if($order->isCancelled())
                                <span class="badge badge-dark">Cancelled</span>
                            @elseif($orderReceived == 0)
                                <span class="badge badge-danger">{{ __('order.Not_Paid') }}</span>
                            @elseif($orderReceived < $orderTotal)
                                <span class="badge badge-warning">{{ __('order.Partial') }}</span>
                            @elseif($orderReceived >= $orderTotal)
                                <span class="badge badge-success">{{ __('order.Paid') }}</span>
                            @endif
                        </td>
                        <td>{{ config('settings.currency_symbol') }} {{ number_format($order->returnedAmount(), 2) }}</td>
                        <td>{{config('settings.currency_symbol')}} {{number_format($orderRemaining, 2)}}</td>
                        <td>{{$order->created_at}}</td>
                        <td>
                            <button
                                class="btn btn-sm btn-secondary btnShowInvoice"
                                data-toggle="modal"
                                data-target="#modalInvoice"
                                data-order-id="{{ $order->id }}"
                                data-customer-name="{{ $order->getCustomerName() }}"
                                data-customer-mobile="{{ $customerMobile }}"
                                data-total="{{ $orderTotal }}"
                                data-received="{{ $orderReceived }}"
                                data-discount="{{ $orderDiscount }}"
                                data-balance="{{ max($orderRemaining, 0) }}"
                                data-items='@json($invoiceItems, JSON_HEX_APOS)'
                                data-created-at="{{ $order->created_at }}">
                                <ion-icon size="small" name="eye"></ion-icon>
                            </button>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('orders.show', $order) }}" target="_blank">
                                <i class="fas fa-print"></i>
                            </a>
                            <a class="btn btn-sm btn-outline-danger" href="{{ route('orders.return', $order) }}">
                                <i class="fas fa-undo"></i>
                            </a>

                            @if($orderRemaining > 0 && !$order->isCancelled())
                                <button class="btn btn-sm btn-primary btnPartialPayment"
                                        data-toggle="modal"
                                        data-target="#partialPaymentModal"
                                        data-order-id="{{ $order->id }}"
                                        data-remaining-amount="{{ $orderRemaining }}">
                                    Pay Partial
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>{{ config('settings.currency_symbol') }} {{ number_format($total, 2) }}</th>
                    <th>{{ config('settings.currency_symbol') }} {{ number_format($receivedAmount, 2) }}</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
                </tfoot>
            </table>
            {{ $orders->render() }}
        </div>
    </div>

    <!-- Partial Payment Modal -->
    <div class="modal fade" id="partialPaymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pay Partial Amount</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('orders.partial-payment') }}">
                    @csrf
                    <div class="modal-body">
                            <input type="hidden" name="order_id" id="modalOrderId">
                            <div class="form-group">
                                <label for="partialPaymentMethod">Payment Method</label>
                                <select class="form-control" id="partialPaymentMethod" name="payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="bank_transfer">Bank transfer</option>
                                    <option value="mobile_money">Mobile money</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="partialAmount">Enter Amount to Pay</label>
                            <input type="number" class="form-control" step="0.01" id="partialAmount" name="amount" required>
                            <small class="form-text text-muted">Remaining: <span id="remainingAmount"></span></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('model')
    <!-- Invoice Modal -->
    <div class="modal fade" id="modalInvoice" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Invoice</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Dynamic content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script type="module" src="https://unpkg.com/ionicons@4.5.10-0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@4.5.10-0/dist/ionicons/ionicons.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        jQuery(document).ready(function($) {
            var currencySymbol = @json(config('settings.currency_symbol'));
            var businessName = @json('Musa Jan Frozen Foods');
            var logoUrl = @json(asset('images/logo.png'));
            var receiptFooter = @json(config('settings.receipt_footer', 'Thank you for shopping with Musa Jan Frozen Foods.'));

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, function(match) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    }[match];
                });
            }

            // Invoice Modal
            $(document).on('click', '.btnShowInvoice', function() {
                var button = $(this);
                var orderId = button.data('order-id');
                var customerName = button.data('customer-name');
                var customerMobile = button.data('customer-mobile') || 'N/A';
                var totalAmount = button.data('total');
                var receivedAmount = button.data('received');
                var discountAmount = button.data('discount') || 0;
                var balanceAmount = button.data('balance') || Math.max(totalAmount - receivedAmount, 0);
                var createdAt = button.data('created-at');
                var items = button.data('items');

                var statusBadge = '';
                if (receivedAmount == 0) {
                    statusBadge = '<span class="badge badge-danger">Not Paid</span>';
                } else if (receivedAmount < totalAmount) {
                    statusBadge = '<span class="badge badge-warning">Partial</span>';
                } else {
                    statusBadge = '<span class="badge badge-success">Paid</span>';
                }

                var itemsHTML = '';
                if (items && Array.isArray(items) && items.length > 0) {
                    items.forEach(function(item, index) {
                        itemsHTML += '<tr>' +
                            '<td>' + (index + 1) + '</td>' +
                            '<td>' + escapeHtml(item.name || 'N/A') + '</td>' +
                            '<td>' + parseFloat(item.quantity || 0).toFixed(2) + '</td>' +
                            '<td>' + currencySymbol + ' ' + parseFloat(item.rate || 0).toFixed(2) + '</td>' +
                            '<td>' + currencySymbol + ' ' + parseFloat(item.total || 0).toFixed(2) + '</td>' +
                            '</tr>';
                    });
                } else {
                    itemsHTML = '<tr><td colspan="5" class="text-center">No items found</td></tr>';
                }

                var modalBody = $('#modalInvoice').find('.modal-body');
                modalBody.html(
                    '<div class="card">' +
                    '<div class="card-body">' +
                    '<div class="text-center mb-3">' +
                    '<img src="' + logoUrl + '" alt="' + businessName + ' logo" style="max-width:72px;margin-bottom:8px;">' +
                    '<h4 class="mb-1">' + businessName + '</h4>' +
                    '<div class="text-muted">Sales Invoice</div>' +
                    '</div>' +
                    '<div class="d-flex justify-content-between align-items-center border-top border-bottom py-2 mb-3">' +
                    '<div>Invoice <strong>#' + orderId + '</strong></div>' +
                    '<div><strong>Status:</strong> ' + statusBadge + '</div>' +
                    '</div>' +
                    '<div class="row mb-4">' +
                    '<div class="col-sm-6">' +
                    '<div><strong>Customer Name:</strong> ' + escapeHtml(customerName) + '</div>' +
                    '<div><strong>Mobile Number:</strong> ' + escapeHtml(customerMobile) + '</div>' +
                    '</div>' +
                    '<div class="col-sm-6 text-sm-right mt-2 mt-sm-0">' +
                    '<div><strong>Date &amp; Time:</strong> ' + escapeHtml(createdAt) + '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="table-responsive">' +
                    '<table class="table table-striped">' +
                    '<thead>' +
                    '<tr>' +
                    '<th>#</th>' +
                    '<th>Product</th>' +
                    '<th>Quantity</th>' +
                    '<th>Rate</th>' +
                    '<th>Total</th>' +
                    '</tr>' +
                    '</thead>' +
                    '<tbody>' + itemsHTML + '</tbody>' +
                    '<tfoot>' +
                    '<tr>' +
                    '<th colspan="4" class="text-right">Discount</th>' +
                    '<th>-' + currencySymbol + ' ' + parseFloat(discountAmount).toFixed(2) + '</th>' +
                    '</tr>' +
                    '<tr>' +
                    '<th colspan="4" class="text-right">Grand Total</th>' +
                    '<th>' + currencySymbol + ' ' + parseFloat(totalAmount).toFixed(2) + '</th>' +
                    '</tr>' +
                    '<tr>' +
                    '<th colspan="4" class="text-right">Paid Amount</th>' +
                    '<th>' + currencySymbol + ' ' + parseFloat(receivedAmount).toFixed(2) + '</th>' +
                    '</tr>' +
                    '<tr>' +
                    '<th colspan="4" class="text-right">Remaining Balance</th>' +
                    '<th>' + currencySymbol + ' ' + parseFloat(balanceAmount).toFixed(2) + '</th>' +
                    '</tr>' +
                    '</tfoot>' +
                    '</table>' +
                    '</div>' +
                    '<p class="text-center mb-0">' + escapeHtml(receiptFooter) + '</p>' +
                    '</div>' +
                    '</div>'
                );
            });

            // Partial Payment Modal
            $(document).on('click', '.btnPartialPayment', function() {
                var button = $(this);
                var orderId = button.data('order-id');
                var remainingAmount = button.data('remaining-amount');

                $('#modalOrderId').val(orderId);
                $('#partialAmount').val(remainingAmount).attr('max', remainingAmount);
                $('#remainingAmount').text(currencySymbol + ' ' + parseFloat(remainingAmount).toFixed(2));
            });
        });
    </script>
@endsection
