@csrf

<div class="form-group">
    <label for="name">{{ __('Name') }}</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category?->name) }}" required>
    @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
</div>

<div class="form-group">
    <label for="description">{{ __('Description') }}</label>
    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror">{{ old('description', $category?->description) }}</textarea>
    @error('description')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
</div>

<div class="form-group">
    <label for="status">{{ __('Status') }}</label>
    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
        <option value="1" @selected((string) old('status', $category?->status ?? 1) === '1')>{{ __('Active') }}</option>
        <option value="0" @selected((string) old('status', $category?->status ?? 1) === '0')>{{ __('Inactive') }}</option>
    </select>
    @error('status')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
</div>

<button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
<a href="{{ route('categories.index') }}" class="btn btn-default">{{ __('Cancel') }}</a>
