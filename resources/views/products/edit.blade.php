@extends('layouts.admin')

@section('title', __('product.Edit_Product'))
@section('content-header', __('product.Edit_Product'))

@section('content')

<div class="card">
    <div class="card-body">

        <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">{{ __('product.Name') }}</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name"
                    placeholder="{{ __('product.Name') }}" value="{{ old('name', $product->name) }}">
                @error('name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>


            <div class="form-group">
                <label for="category_id">{{ __('Category') }}</label>
                <select name="category_id" class="form-control @error('category_id') is-invalid @enderror" id="category_id">
                    <option value="">{{ __('Uncategorized') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">{{ __('product.Description') }}</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                    id="description"
                    placeholder="{{ __('product.Description') }}">{{ old('description', $product->description) }}</textarea>
                @error('description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="image">{{ __('product.Image') }}</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" name="image" id="image">
                    <label class="custom-file-label" for="image">{{ __('product.Choose_file') }}</label>
                </div>
                @error('image')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="barcode">{{ __('product.Barcode') }}</label>
                <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror"
                    id="barcode" placeholder="{{ __('product.Barcode') }}" value="{{ old('barcode', $product->barcode) }}">
                <small class="form-text text-muted">Barcode is optional. Leave empty if product has no barcode.</small>
                @error('barcode')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="sku">SKU / Product Code</label>
                <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" id="sku" placeholder="SKU" value="{{ old('sku', $product->sku) }}">
                <small class="form-text text-muted">SKU is used for internal product tracking and can be auto-generated.</small>
                @error('sku')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="form-group">
                <label for="short_code">Short Code (optional)</label>
                <input type="text" name="short_code" class="form-control @error('short_code') is-invalid @enderror" id="short_code" value="{{ old('short_code', $product->short_code) }}">
                @error('short_code')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="form-group">
                <label for="purchase_price">{{ __('product.Purchase_Price') }}</label>
                <input type="text" name="purchase_price" class="form-control @error('purchase_price') is-invalid @enderror" id="purchase_price" placeholder="{{ __('product.Purchase_Price') }}" value="{{ old('purchase_price', $product->purchase_price) }}">
                @error('purchase_price')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="form-group">
                <label for="price">{{ __('product.Price') }}</label>
                <input type="text" name="price" class="form-control @error('price') is-invalid @enderror" id="price"
                    placeholder="{{ __('product.Price') }}" value="{{ old('price', $product->price) }}">
                @error('price')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="wholesale_price">Wholesale Price</label>
                <input type="text" name="wholesale_price" class="form-control @error('wholesale_price') is-invalid @enderror" id="wholesale_price" value="{{ old('wholesale_price', $product->wholesale_price) }}">
                @error('wholesale_price')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="form-group">
                <label for="quantity">{{ __('product.Quantity') }}</label>
                <input type="number" step="0.01" name="quantity" class="form-control @error('quantity') is-invalid @enderror"
                    id="quantity" placeholder="{{ __('product.Quantity') }}" value="{{ old('quantity', $product->quantity) }}">
                @error('quantity')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="minimum_stock_level">Minimum Stock Level</label>
                <input type="number" step="0.01" name="minimum_stock_level" class="form-control @error('minimum_stock_level') is-invalid @enderror" id="minimum_stock_level" value="{{ old('minimum_stock_level', $product->minimum_stock_level ?? 0) }}">
                @error('minimum_stock_level')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="form-group">
                <label for="unit">Unit</label>
                <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror" id="unit" value="{{ old('unit', $product->unit ?? 'pcs') }}">
                @error('unit')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="form-group form-check">
                <input type="hidden" name="track_stock" value="0">
                <input type="checkbox" class="form-check-input" id="track_stock" name="track_stock" value="1" {{ old('track_stock', $product->track_stock) ? 'checked' : '' }}>
                <label class="form-check-label" for="track_stock">Track Stock</label>
            </div>

            <div class="form-group form-check">
                <input type="hidden" name="is_quick_item" value="0">
                <input type="checkbox" class="form-check-input" id="is_quick_item" name="is_quick_item" value="1" {{ old('is_quick_item', $product->is_quick_item) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_quick_item">Quick Item</label>
            </div>

            <div class="form-group">
                <label for="status">{{ __('product.Status') }}</label>
                <select name="status" class="form-control @error('status') is-invalid @enderror" id="status">
                    <option value="1" {{ old('status', $product->status) === 1 ? 'selected' : ''}}>{{ __('common.Active') }}</option>
                    <option value="0" {{ old('status', $product->status) === 0 ? 'selected' : ''}}>{{ __('common.Inactive') }}</option>
                </select>
                @error('status')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <button class="btn btn-primary" type="submit">{{ __('common.Update') }}</button>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('plugins/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
<script>
    $(document).ready(function () {
        bsCustomFileInput.init();
    });
</script>
@endsection
