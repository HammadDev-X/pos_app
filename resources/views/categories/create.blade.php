@extends('layouts.admin')

@section('title', __('Add Category'))
@section('content-header', __('Add Category'))

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('categories.store') }}" method="POST">
            @include('categories.form', ['category' => null])
        </form>
    </div>
</div>
@endsection
