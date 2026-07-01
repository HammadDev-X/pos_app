@extends('layouts.admin')

@section('title', __('customer.Customer_List'))
@section('content-header', __('customer.Customer_List'))
@section('content-actions')
@if(auth()->user()?->isManager())
    <a href="{{route('customers.create')}}" class="btn btn-primary">
        <i class="fas fa-user-plus mr-1"></i>{{ __('customer.Add_Customer') }}
    </a>
@endif
@endsection
@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('customers.index') }}" method="GET" class="mb-3" id="customerSearchForm">
            <div class="input-group">
                <input
                    type="text"
                    name="search"
                    id="customerLiveSearch"
                    value="{{ request('search') }}"
                    class="form-control"
                    placeholder="Search customers by name, code, phone, email, or address"
                    autocomplete="off"
                >
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> {{ __('Search') }}</button>
                    @if(request('search'))
                        <a href="{{ route('customers.index') }}" class="btn btn-default" id="customerSearchReset"><i class="fas fa-redo"></i></a>
                    @endif
                </div>
            </div>
        </form>
        <div class="table-responsive customer-table-wrap">
        <table class="table table-hover customer-table mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Code</th>
                    <th>{{ __('customer.Email') }}</th>
                    <th>{{ __('customer.Phone') }}</th>
                    <th>{{ __('customer.Address') }}</th>
                    <th>Previous Amount</th>
                    @if($canManageCustomers)
                        <th>Salesman</th>
                    @endif
                    <th>{{ __('common.Created_At') }}</th>
                    <th>{{ __('customer.Actions') }}</th>
                </tr>
            </thead>
            <tbody id="customerTableBody">
                @include('customers._table_rows', ['customers' => $customers, 'canManageCustomers' => $canManageCustomers])
            </tbody>
        </table>
        </div>
        <div id="customerPagination">
            {{ $customers->render() }}
        </div>
    </div>
</div>

@if($canManageCustomers)
    <div class="modal fade" id="openingPaymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" id="openingPaymentForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Receive Customer Balance</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <strong id="openingPaymentCustomer">Customer</strong>
                            <div class="text-muted">Total pending: <span id="openingPaymentPending"></span></div>
                            <small class="text-muted" id="openingPaymentOpening"></small>
                        </div>
                        <div class="form-group">
                            <label for="openingPaymentMethod">Payment Method</label>
                            <select class="form-control" id="openingPaymentMethod" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="jazzcash">JazzCash</option>
                                <option value="easypaisa">EasyPaisa</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="openingPaymentAmount">Amount Received</label>
                            <input type="number" min="0.01" step="0.01" class="form-control" id="openingPaymentAmount" name="amount" required>
                            <small class="form-text text-danger d-none" id="openingPaymentAmountHelp"></small>
                        </div>
                        <div class="form-group mb-0">
                            <label for="openingPaymentNote">Note</label>
                            <textarea class="form-control" id="openingPaymentNote" name="note" rows="2" maxlength="255" placeholder="Optional"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Receive Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@section('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script type="module">
    $(document).ready(function() {
        var currencySymbol = @json(config('settings.currency_symbol'));
        var customerSearchTimer = null;
        var customerSearchRequest = null;

        function money(value) {
            return parseFloat(value || 0).toFixed(2);
        }

        function clampMoneyInput(input, maximum, messageTarget) {
            var amount = parseFloat(input.val() || 0);
            var maxAmount = parseFloat(maximum || 0);

            if (amount > maxAmount) {
                input.val(money(maxAmount));
                messageTarget.removeClass('d-none').text('Maximum allowed amount is ' + currencySymbol + ' ' + money(maxAmount) + '.');
                return;
            }

            messageTarget.addClass('d-none').text('');
        }

        function loadCustomers(search, pageUrl) {
            var url = pageUrl || @json(route('customers.index'));
            var requestData = { table: 1, search: search };

            if (customerSearchRequest) {
                customerSearchRequest.abort();
            }

            customerSearchRequest = $.ajax({
                url: url,
                data: requestData,
                dataType: 'json',
                success: function(res) {
                    $('#customerTableBody').html(res.html);
                    $('#customerPagination').html(res.pagination);

                    var cleanUrl = new URL(@json(route('customers.index')), window.location.origin);
                    if (search) {
                        cleanUrl.searchParams.set('search', search);
                    }
                    window.history.replaceState({}, '', cleanUrl.toString());
                },
                complete: function() {
                    customerSearchRequest = null;
                }
            });
        }

        $('#customerSearchForm').on('submit', function(event) {
            event.preventDefault();
            loadCustomers($('#customerLiveSearch').val().trim());
        });

        $('#customerLiveSearch').on('input', function() {
            var search = $(this).val().trim();

            clearTimeout(customerSearchTimer);
            customerSearchTimer = setTimeout(function() {
                loadCustomers(search);
            }, 250);
        });

        $(document).on('click', '#customerPagination a', function(event) {
            event.preventDefault();
            loadCustomers($('#customerLiveSearch').val().trim(), $(this).attr('href'));
        });

        $(document).on('click', '.btn-delete', function() {
            var $this = $(this);
            const swalWithBootstrapButtons = Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger'
                },
                buttonsStyling: false
            });

            swalWithBootstrapButtons.fire({
                title: '{{ __('customer.sure ') }}', // Wrap in quotes
                text: '{{ __('customer.really_delete ') }}', // Wrap in quotes
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('customer.yes_delete ') }}', // Wrap in quotes
                cancelButtonText: '{{ __('customer.No ') }}', // Wrap in quotes
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.post($this.data('url'), {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}' // Wrap in quotes
                    }, function(res) {
                        $this.closest('tr').fadeOut(500, function() {
                            $(this).remove();
                        });
                    });
                }
            });
        });

        $(document).on('click', '.btnReceiveOpeningPayment', function() {
            var button = $(this);
            var openingAmount = parseFloat(button.data('pending-amount') || 0);
            var pendingAmount = parseFloat(button.data('total-pending') || openingAmount || 0);

            $('#openingPaymentForm').attr('action', button.data('url'));
            $('#openingPaymentCustomer').text(button.data('customer-code') + ' - ' + button.data('customer-name'));
            $('#openingPaymentPending').text(currencySymbol + ' ' + money(pendingAmount));
            $('#openingPaymentOpening').text('Opening balance included: ' + currencySymbol + ' ' + money(openingAmount));
            $('#openingPaymentAmount').val(money(pendingAmount)).attr('max', money(pendingAmount));
            $('#openingPaymentAmountHelp').addClass('d-none').text('');
            $('#openingPaymentMethod').val('cash');
            $('#openingPaymentNote').val('');
        });

        $(document).on('input', '#openingPaymentAmount', function() {
            clampMoneyInput($(this), $(this).attr('max'), $('#openingPaymentAmountHelp'));
        });
    });
</script>
@endsection
