@extends('layouts.admin')

@section('title', 'Adjust Stock')
@section('content-header', 'Adjust Stock')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('stock-adjustments.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="product_id">Product</label>
                <select name="product_id" id="product_id" class="form-control @error('product_id') is-invalid @enderror" required>
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>
                            {{ $product->name }} (Current: {{ $product->quantity }})
                        </option>
                    @endforeach
                </select>
                @error('product_id')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="form-group">
                <label for="type">Adjustment Type</label>
                <select name="type" id="type" class="form-control @error('type') is-invalid @enderror" required>
                    <option value="increase" @selected(old('type') === 'increase')>Increase</option>
                    <option value="decrease" @selected(old('type') === 'decrease')>Decrease</option>
                    <option value="set" @selected(old('type') === 'set')>Set Quantity</option>
                </select>
                @error('type')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" name="quantity" id="quantity" min="0" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', 1) }}" required>
                @error('quantity')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="form-group">
                <label for="reason">Reason</label>
                <input type="text" name="reason" id="reason" class="form-control @error('reason') is-invalid @enderror" value="{{ old('reason') }}" placeholder="Damage, correction, opening stock, missing item..." required>
                @error('reason')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <button class="btn btn-primary" type="submit">Save Adjustment</button>
            <a href="{{ route('stock-adjustments.index') }}" class="btn btn-default">Cancel</a>
        </form>
    </div>
</div>
@endsection
