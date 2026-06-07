@extends('layouts.admin')

@section('title', __('order.title'))

@section('content')
    <div id="cart">
        <div class="card">
            <div class="card-body text-muted">{{ __('Loading POS cart...') }}</div>
        </div>
    </div>

@endsection
