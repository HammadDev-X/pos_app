@extends('layouts.admin')

@section('title', __('customer.Create_Customer') )
@section('content-header', __('customer.Create_Customer') )

@section('content')

    <div class="card">
        <div class="card-body">

            <form action="{{ route('customers.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="customer_code">Customer Code</label>
                    <input type="text" name="customer_code" class="form-control @error('customer_code') is-invalid @enderror"
                           id="customer_code"
                           placeholder="Customer Code" value="{{ old('customer_code') }}" required>
                    @error('customer_code')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="first_name">{{ __('customer.First_Name') }}</label>
                    <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                           id="first_name"
                           placeholder="{{ __('customer.First_Name') }}" value="{{ old('first_name') }}">
                    @error('first_name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="last_name">{{ __('customer.Last_Name') }}</label>
                    <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                           id="last_name"
                           placeholder="{{ __('customer.Last_Name') }}" value="{{ old('last_name') }}">
                    @error('last_name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">{{ __('customer.Email') }}</label>
                    <input type="text" name="email" class="form-control @error('email') is-invalid @enderror" id="email"
                           placeholder="{{ __('customer.Email') }}" value="{{ old('email') }}">
                    @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="phone">{{ __('customer.Phone') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">+92</span>
                        </div>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" id="phone"
                               inputmode="numeric" pattern="[0-9]{10}" maxlength="10"
                               placeholder="3001234567" value="{{ old('phone') }}">
                    </div>
                    <small class="form-text text-muted">Enter 10 digits only after +92.</small>
                    @error('phone')
                    <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="address">{{ __('customer.Address') }}</label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror"
                           id="address"
                           placeholder="{{ __('customer.Address') }}" value="{{ old('address') }}">
                    @error('address')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="pending_amount">Pending Amount</label>
                    <input type="number" min="0" step="0.01" name="pending_amount" class="form-control @error('pending_amount') is-invalid @enderror"
                           id="pending_amount"
                           placeholder="Pending Amount" value="{{ old('pending_amount', 0) }}">
                    @error('pending_amount')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <button class="btn btn-primary" type="submit">{{ __('common.Create') }}</button>
            </form>
        </div>
    </div>
@endsection
