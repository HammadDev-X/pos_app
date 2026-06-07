@extends('layouts.admin')

@section('title', __('Update Supplier'))
@section('content-header', __('Update Supplier'))

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('suppliers.update', $supplier) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="first_name">{{ __('First Name') }}</label>
                <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" id="first_name" value="{{ old('first_name', $supplier->first_name) }}">
                @error('first_name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="form-group">
                <label for="last_name">{{ __('Last Name') }}</label>
                <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" id="last_name" value="{{ old('last_name', $supplier->last_name) }}">
                @error('last_name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="form-group">
                <label for="email">{{ __('Email') }}</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email" value="{{ old('email', $supplier->email) }}">
                @error('email')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="form-group">
                <label for="phone">{{ __('Phone') }}</label>
                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" id="phone" value="{{ old('phone', $supplier->phone) }}">
                @error('phone')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="form-group">
                <label for="address">{{ __('Address') }}</label>
                <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" id="address" value="{{ old('address', $supplier->address) }}">
                @error('address')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <div class="form-group">
                <label for="avatar">{{ __('Avatar') }}</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" name="avatar" id="avatar">
                    <label class="custom-file-label" for="avatar">{{ __('Choose file') }}</label>
                </div>
                @error('avatar')<span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>@enderror
            </div>

            <button class="btn btn-primary" type="submit">{{ __('Update') }}</button>
            <a href="{{ route('suppliers.index') }}" class="btn btn-default">{{ __('Cancel') }}</a>
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
