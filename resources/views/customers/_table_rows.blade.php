@foreach ($customers as $customer)
<tr class="{{ $customer->total_pending_balance > 0 ? 'customer-row-pending' : '' }}">
    <td>
        <div class="customer-identity">
            <div>
                <strong>{{ $customer->full_name }}</strong>
                <span>ID #{{ $customer->id }}</span>
            </div>
        </div>
    </td>
    <td><span class="badge badge-info">{{ $customer->customer_code }}</span></td>
    <td class="text-break">{{ $customer->email ?: '-' }}</td>
    <td class="text-nowrap">{{ $customer->phone ?: '-' }}</td>
    <td class="customer-address">{{ $customer->address ?: '-' }}</td>
    <td class="text-nowrap">
        <strong class="{{ $customer->total_pending_balance > 0 ? 'text-warning' : '' }}">
            {{ config('settings.currency_symbol') }} {{ number_format((float) $customer->total_pending_balance, 2) }}
        </strong>
        @if((float) $customer->pending_amount > 0)
            <small class="d-block text-muted">
                Opening: {{ config('settings.currency_symbol') }} {{ number_format((float) $customer->pending_amount, 2) }}
            </small>
        @endif
    </td>
    @if($canManageCustomers)
    <td>
        <form action="{{ route('customers.toggle-salesman-visibility', $customer) }}" method="POST" class="d-inline">
            @csrf
            @method('PATCH')
            <button
                type="submit"
                class="btn btn-sm {{ $customer->is_visible_to_salesman ? 'btn-success' : 'btn-secondary' }}"
                title="{{ $customer->is_visible_to_salesman ? 'Hide from salesman' : 'Show to salesman' }}"
            >
                <i class="fas {{ $customer->is_visible_to_salesman ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                {{ $customer->is_visible_to_salesman ? 'Allowed' : 'Hidden' }}
            </button>
        </form>
    </td>
    @endif
    <td class="text-nowrap">{{ $customer->created_at?->format('Y-m-d') }}</td>
    <td class="customer-actions">
        <a href="{{ route('customers.show', $customer) }}" class="btn btn-info btn-sm" title="Ledger"><i class="fas fa-book"></i></a>
        @if($canManageCustomers)
            @if((float) $customer->total_pending_balance > 0)
                <button
                    type="button"
                    class="btn btn-success btn-sm btnReceiveOpeningPayment"
                    title="Receive customer balance"
                    data-toggle="modal"
                    data-target="#openingPaymentModal"
                    data-customer-name="{{ $customer->full_name }}"
                    data-customer-code="{{ $customer->customer_code }}"
                    data-pending-amount="{{ (float) $customer->pending_amount }}"
                    data-total-pending="{{ (float) $customer->total_pending_balance }}"
                    data-url="{{ route('customers.opening-payment', $customer) }}"
                >
                    <i class="fas fa-hand-holding-usd"></i>
                </button>
            @endif
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
            <button class="btn btn-danger btn-sm btn-delete" data-url="{{ route('customers.destroy', $customer) }}" title="Delete"><i class="fas fa-trash"></i></button>
        @endif
    </td>
</tr>
@endforeach
@if($customers->isEmpty())
<tr>
    <td colspan="{{ $canManageCustomers ? 9 : 8 }}" class="text-center text-muted py-4">No customers found.</td>
</tr>
@endif
