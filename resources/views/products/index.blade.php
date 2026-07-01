@extends('layouts.admin')

@section('title', __('product.Product_List'))
@section('content-header', __('product.Product_List'))
@section('content-actions')
@if(auth()->user()?->isManager())
    <a href="{{route('products.create')}}" class="btn btn-primary">{{ __('product.Create_Product') }}</a>
@endif
@endsection
@section('css')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection
@section('content')
<div class="card product-list product-list-card">
    <div class="card-body">
        @php($canManageProducts = auth()->user()?->isManager())
        <form method="GET" action="{{ route('products.index') }}" class="mb-3">
            <div class="row">
                <div class="col-md-5">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search by name, SKU, short code, or category') }}">
                </div>
                <div class="col-md-4">
                    <select name="category_id" class="form-control">
                        <option value="">{{ __('All Categories') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> {{ __('Search') }}</button>
                    <a href="{{ route('products.index') }}" class="btn btn-default"><i class="fas fa-redo"></i></a>
                </div>
            </div>
        </form>
        <div class="table-responsive product-list-table">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>{{ __('product.ID') }}</th>
                    <th>{{ __('product.Name') }}</th>
                    <th>{{ __('Category') }}</th>
                    <th>{{ __('product.Image') }}</th>
                    <th>SKU / Product Code</th>
                    <th>Unit Type</th>
                    @if($canManageProducts)
                        <th>Purchase Cost</th>
                    @endif
                    <th>Sale Price</th>
                    <th>Wholesale Price</th>
                    <th>Minimum Stock</th>
                    <th>Current Stock</th>
                    <th>{{ __('product.Status') }}</th>
                    @if($canManageProducts)
                        <th>{{ __('product.Actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                <tr>
                    <td>{{$product->id}}</td>
                    <td>{{$product->name}}</td>
                    <td>{{ $product->category?->name ?? __('Uncategorized') }}</td>
                    <td><img class="product-img" src="{{ $product->image_url }}" alt="{{ $product->name }}"></td>
                    <td>{{ $product->sku }}</td>
                    <td>{{ ['pack' => 'Pack', 'kg' => 'Kg', 'piece' => 'Piece', 'pcs' => 'Piece', 'carton' => 'Carton'][$product->unit] ?? ucfirst((string) $product->unit) }}</td>
                    @if($canManageProducts)
                        <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $product->purchase_price, 2) }}</td>
                    @endif
                    <td>{{ config('settings.currency_symbol') }}{{ number_format((float) $product->price, 2) }}</td>
                    <td>{{ $product->wholesale_price !== null ? config('settings.currency_symbol') . number_format((float) $product->wholesale_price, 2) : '-' }}</td>
                    <td>{{ number_format((float) $product->minimum_stock_level, 2) }}</td>
                    <td>
                        <span class="badge badge-{{ $product->isOutOfStock() ? 'danger' : ($product->isLowStock() ? 'warning' : 'secondary') }}">
                            {{ $product->quantity }}
                        </span>
                    </td>
                    <td>
                        <span class="right badge badge-{{ $product->status ? 'success' : 'danger' }}">{{$product->status ? __('common.Active') : __('common.Inactive') }}</span>
                    </td>
                    @if($canManageProducts)
                        <td>
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                            <button class="btn btn-danger btn-delete" data-url="{{route('products.destroy', $product)}}"><i class="fas fa-trash"></i></button>
                        </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        {{ $products->render() }}
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    document.addEventListener('click', function(event) {
        var button = event.target.closest('.btn-delete');

        if (!button) {
            return;
        }

        event.preventDefault();

        const swalWithBootstrapButtons = Swal.mixin({
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-danger'
            },
            buttonsStyling: false
        })

        swalWithBootstrapButtons.fire({
            title: '{{ __('product.sure ') }}',
            text: '{{ __('product.really_delete ') }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __('product.yes_delete ') }}',
            cancelButtonText: '{{ __('product.No ') }}',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                fetch(button.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        _method: 'DELETE',
                    }),
                }).then(function(response) {
                    if (!response.ok) {
                        throw new Error('Delete failed');
                    }

                    button.closest('tr')?.remove();
                });
            }
        });
    });
</script>
@endsection
