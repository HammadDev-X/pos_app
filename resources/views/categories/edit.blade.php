@extends('layouts.admin')

@section('title', __('Edit Category'))
@section('content-header', __('Edit Category'))

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('categories.update', $category) }}" method="POST">
            @method('PUT')
            @include('categories.form', ['category' => $category])
        </form>
    </div>
</div>
@endsection
