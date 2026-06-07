@extends('layouts.auth')

@section('title', __('Reset Password'))

@section('content')
<p class="login-box-msg">{{ __('Reset your password') }}</p>

<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="form-group">
        <div class="input-group">
            <input
                id="email"
                type="email"
                class="form-control @error('email') is-invalid @enderror"
                name="email"
                value="{{ $email ?? old('email') }}"
                placeholder="{{ __('Email') }}"
                required
                autocomplete="email"
                autofocus
            >
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-envelope"></span>
                </div>
            </div>
            @error('email')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
            @enderror
        </div>
    </div>

    <div class="form-group">
        <div class="input-group">
            <input
                id="password"
                type="password"
                class="form-control @error('password') is-invalid @enderror"
                name="password"
                placeholder="{{ __('Password') }}"
                required
                autocomplete="new-password"
            >
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>
            @error('password')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
            @enderror
        </div>
    </div>

    <div class="form-group">
        <div class="input-group">
            <input
                id="password-confirm"
                type="password"
                class="form-control"
                name="password_confirmation"
                placeholder="{{ __('Confirm Password') }}"
                required
                autocomplete="new-password"
            >
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block">{{ __('Reset Password') }}</button>
</form>

<p class="mt-3 mb-1">
    <a href="{{ route('login') }}">{{ __('Login') }}</a>
</p>
@endsection
