@extends('layouts.admin')

@section('title', __('settings.Update_Settings'))
@section('content-header', __('settings.Update_Settings'))

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('settings.store') }}" method="post">
            @csrf

            <div class="form-group">
                <label for="app_name">{{ __('settings.app_name') }}</label>
                <input type="text" name="app_name" class="form-control @error('app_name') is-invalid @enderror" id="app_name" placeholder="{{ __('settings.App_name') }}" value="{{ old('app_name', config('settings.app_name')) }}">
                @error('app_name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="app_description">{{ __('settings.app_description') }}</label>
                <textarea name="app_description" class="form-control @error('app_description') is-invalid @enderror" id="app_description" placeholder="{{ __('settings.app_description') }}">{{ old('app_description', config('settings.app_description')) }}</textarea>
                @error('app_description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="currency_symbol">{{ __('settings.Currency_symbol') }}</label>
                <input type="text" name="currency_symbol" class="form-control @error('currency_symbol') is-invalid @enderror" id="currency_symbol" placeholder="{{ __('settings.Currency_symbol') }}" value="{{ old('currency_symbol', config('settings.currency_symbol')) }}">
                @error('currency_symbol')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="form-group">
                <label for="shop_address">Shop Address</label>
                <input type="text" name="shop_address" class="form-control" id="shop_address" value="{{ old('shop_address', config('settings.shop_address')) }}">
            </div>
            <div class="form-group">
                <label for="shop_phone">Shop Phone</label>
                <input type="text" name="shop_phone" class="form-control" id="shop_phone" value="{{ old('shop_phone', config('settings.shop_phone')) }}">
            </div>
            <div class="form-group">
                <label for="tax_number">Tax Number</label>
                <input type="text" name="tax_number" class="form-control" id="tax_number" value="{{ old('tax_number', config('settings.tax_number')) }}">
            </div>
            <div class="form-group">
                <label for="receipt_footer">Receipt Footer</label>
                <input type="text" name="receipt_footer" class="form-control" id="receipt_footer" value="{{ old('receipt_footer', config('settings.receipt_footer')) }}">
            </div>
            <div class="form-group">
                <label for="warning_quantity">{{ __('settings.warning_quantity') }}</label>
                <input type="text" name="warning_quantity" class="form-control @error('warning_quantity') is-invalid @enderror" id="warning_quantity" placeholder="{{ __('settings.warning_quantity') }}" value="{{ old('warning_quantity', config('settings.warning_quantity')) }}">
                @error('warning_quantity')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">{{ __('settings.Change_Setting') }}</button>
        </form>
    </div>
</div>
@endsection
