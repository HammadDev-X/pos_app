@extends('layouts.admin')

@section('title', __('Categories'))
@section('content-header', __('Categories'))
@section('content-actions')
<a href="{{ route('categories.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> {{ __('Add Category') }}
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('categories.index') }}" class="mb-3">
            <div class="input-group">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search categories') }}">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    <a href="{{ route('categories.index') }}" class="btn btn-default"><i class="fas fa-redo"></i></a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Products') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Created At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>
                                <strong>{{ $category->name }}</strong>
                                @if($category->description)
                                    <div class="text-muted small">{{ $category->description }}</div>
                                @endif
                            </td>
                            <td><span class="badge badge-info">{{ $category->products_count }}</span></td>
                            <td>
                                <span class="badge badge-{{ $category->status ? 'success' : 'danger' }}">
                                    {{ $category->status ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td>{{ $category->created_at }}</td>
                            <td>
                                <a href="{{ route('categories.edit', $category) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this category? Products will keep working without a category.') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">{{ __('No categories found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $categories->render() }}
    </div>
</div>
@endsection
