@extends('layouts.admin')

@section('title', 'Data Export')
@section('content-header', 'Data Export')

@section('content')
<div class="card">
    <div class="card-body">
        <p class="text-muted">Download important business data as CSV for backup, migration, or owner reporting.</p>
        <div class="row">
            @foreach([
                'products' => ['Products', 'fa-box'],
                'customers' => ['Customers', 'fa-users'],
                'suppliers' => ['Suppliers', 'fa-truck'],
                'orders' => ['Orders', 'fa-shopping-cart'],
                'purchases' => ['Purchases', 'fa-dolly'],
                'expenses' => ['Expenses', 'fa-receipt'],
            ] as $type => [$label, $icon])
                <div class="col-md-4 mb-3">
                    <a href="{{ route('exports.download', $type) }}" class="btn btn-outline-primary btn-block text-left">
                        <i class="fas {{ $icon }} mr-2"></i> Export {{ $label }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
