@extends('layouts.admin')

@section('title', __('customer.Customer_List'))
@section('content-header', __('customer.Customer_List'))
@section('content-actions')
<a href="{{route('customers.create')}}" class="btn btn-primary">
    <i class="fas fa-user-plus mr-1"></i>{{ __('customer.Add_Customer') }}
</a>
@endsection
@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        @php
            $canDeleteCustomers = auth()->user()?->isManager();
        @endphp
        <form action="{{ route('customers.index') }}" method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search customers by name, code, phone, email, or address">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> {{ __('Search') }}</button>
                    @if(request('search'))
                        <a href="{{ route('customers.index') }}" class="btn btn-default"><i class="fas fa-redo"></i></a>
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
                    <th>{{ __('common.Created_At') }}</th>
                    <th>{{ __('customer.Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($customers as $customer)
                <tr class="{{ $customer->total_pending_balance > 0 ? 'customer-row-pending' : '' }}">
                    <td>
                        <div class="customer-identity">
                            <div>
                                <strong>{{$customer->full_name}}</strong>
                                <span>ID #{{$customer->id}}</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-info">{{ $customer->customer_code }}</span></td>
                    <td class="text-break">{{$customer->email ?: '-'}}</td>
                    <td class="text-nowrap">{{$customer->phone ?: '-'}}</td>
                    <td class="customer-address">{{$customer->address ?: '-'}}</td>
                    <td class="text-nowrap">
                        <strong class="{{ $customer->total_pending_balance > 0 ? 'text-warning' : '' }}">
                            {{ config('settings.currency_symbol') }} {{ number_format((float) $customer->total_pending_balance, 2) }}
                        </strong>
                    </td>
                    <td class="text-nowrap">{{$customer->created_at?->format('Y-m-d')}}</td>
                    <td class="customer-actions">
                        <a href="{{ route('customers.show', $customer) }}" class="btn btn-info btn-sm" title="Ledger"><i class="fas fa-book"></i></a>
                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                        @if($canDeleteCustomers)
                            <button class="btn btn-danger btn-sm btn-delete" data-url="{{route('customers.destroy', $customer)}}" title="Delete"><i class="fas fa-trash"></i></button>
                        @endif
                    </td>
                </tr>
                @endforeach
                @if($customers->isEmpty())
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">No customers found.</td>
                </tr>
                @endif
            </tbody>
        </table>
        </div>
        {{ $customers->render() }}
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script type="module">
    $(document).ready(function() {
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
    });
</script>
@endsection
