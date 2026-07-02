@extends('institute.layout')
@section('title', 'Routes')
@section('breadcrumb', 'Transport / Routes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Routes</h4>
        <small class="text-muted">{{ $routes->total() }} route(s)</small>
    </div>
    <a href="{{ route('transport.routes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Route</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Route</th>
                    <th>Fee</th>
                    <th>Stops</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($routes as $i => $route)
                    <tr>
                        <td>{{ $routes->firstItem() + $i }}</td>
                        <td>
                            <div class="fw-semibold">{{ $route->name }}</div>
                            <small class="text-muted">{{ $route->route_code }} | {{ $route->start_point ?? 'Start' }} to {{ $route->end_point ?? 'End' }}</small>
                        </td>
                        <td>₹{{ number_format((float) $route->fee_amount, 2) }}</td>
                        <td>{{ $route->stops_count }}</td>
                        <td>
                            <form method="POST" action="{{ route('transport.routes.toggle', $route) }}">
                                @csrf
                                <button class="btn btn-sm {{ $route->status ? 'btn-success' : 'btn-secondary' }}">
                                    {{ $route->status ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('transport.routes.show', $route) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('transport.routes.edit', $route) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                                <form id="del-r-{{ $route->id }}" method="POST" action="{{ route('transport.routes.destroy', $route) }}" class="d-none">
                                    @csrf @method('DELETE')
                                </form>
                                <button class="btn btn-outline-danger btn-sm"
                                    onclick="deleteConfirm('del-r-{{ $route->id }}', 'Delete Route?', '{{ addslashes($route->name) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No routes configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $routes->links() }}</div>
@include('partials.delete-confirm-modal')
@endsection
