@extends('institute.layout')
@section('title', 'Drivers')
@section('breadcrumb', 'Transport / Drivers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Drivers</h4>
        <small class="text-muted">{{ $drivers->total() }} driver(s)</small>
    </div>
    <a href="{{ route('transport.drivers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Driver</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Driver</th>
                    <th>License</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drivers as $i => $driver)
                    <tr>
                        <td>{{ $drivers->firstItem() + $i }}</td>
                        <td>
                            <div class="fw-semibold">{{ $driver->name }}</div>
                            <small class="text-muted">{{ $driver->mobile ?? '—' }}</small>
                        </td>
                        <td class="small text-muted">
                            {{ $driver->license_no ?? '—' }}<br>
                            {{ $driver->license_expiry?->format('d M Y') ?? 'No expiry set' }}
                        </td>
                        <td>
                            <form method="POST" action="{{ route('transport.drivers.toggle', $driver) }}">
                                @csrf
                                <button class="btn btn-sm {{ $driver->status ? 'btn-success' : 'btn-secondary' }}">
                                    {{ $driver->status ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('transport.drivers.edit', $driver) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('transport.drivers.destroy', $driver) }}" onsubmit="return confirm('Delete this driver?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">No drivers added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $drivers->links() }}</div>
@endsection
