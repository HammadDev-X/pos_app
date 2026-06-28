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
        @php($canDeleteCustomers = auth()->user()?->isManager())
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
                <tr>
                    <td>
                        <div class="customer-identity">
                            <img src="{{$customer->getAvatarUrl()}}" alt="{{$customer->full_name}}">
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
                    <td class="text-nowrap">{{ config('settings.currency_symbol') }} {{ number_format((float) $customer->pending_amount, 2) }}</td>
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
