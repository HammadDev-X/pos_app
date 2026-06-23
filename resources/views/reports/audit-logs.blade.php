@extends('layouts.admin')

@section('title', 'Audit Logs')
@section('content-header', 'Audit Logs')

@section('content')
<form method="GET" action="{{ route('audit-logs.index') }}" class="card card-body mb-3">
    <div class="row">
        <div class="col-md-8">
            <select name="action" class="form-control">
                <option value="">All actions</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Record</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                        <td><span class="badge badge-info">{{ $log->action }}</span></td>
                        <td>{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                        <td><code>{{ json_encode($log->properties) }}</code></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No audit logs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $logs->render() }}</div>
</div>
@endsection
