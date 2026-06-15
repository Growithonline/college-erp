@php
    $feeLayout = auth()->guard('staff')->check() ? 'staff.layout' : 'institute.layout';
@endphp
@extends($feeLayout)
@section('title', 'Practical Fee Tokens')
@section('breadcrumb', 'Fee / Practical Tokens')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Practical Fee Tokens</h4>
        <small class="text-muted">Batch-wise practical collection entry</small>
    </div>
    <a href="{{ route($routePrefix . '.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> New Token Batch
    </a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ (string) request('session_id', $activeSession?->id) === (string) $session->id ? 'selected' : '' }}>
                            {{ $session->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Batch</th>
                    <th>Course</th>
                    <th>Subject</th>
                    <th>Year / Sem</th>
                    <th>Token Amount</th>
                    <th>Posted</th>
                    <th>Remaining</th>
                    <th>Mode</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    @php
                        $posted = (float) ($batch->posted_amount ?? 0);
                        $remaining = max(0, (float) $batch->token_amount - $posted);
                    @endphp
                    <tr>
                        <td>{{ $batches->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="fw-semibold">{{ $batch->title ?: 'Practical Batch #' . $batch->id }}</div>
                            <small class="text-muted">{{ $batch->collection_date?->format('d-M-Y') }}</small>
                        </td>
                        <td>{{ $batch->course?->name }}</td>
                        <td>{{ $batch->subject?->name }}</td>
                        <td>{{ $batch->coursePart?->year_label ?? ($batch->year_number . ' Year') }} / Sem {{ $batch->semester }}</td>
                        <td>{{ number_format((float) $batch->token_amount, 2) }}</td>
                        <td>{{ number_format($posted, 2) }}</td>
                        <td>{{ number_format($remaining, 2) }}</td>
                        <td>{{ strtoupper($batch->payment_mode) }}</td>
                        <td class="text-end">
                            <a href="{{ route($routePrefix . '.show', $batch) }}" class="btn btn-outline-primary btn-sm">
                                Open
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No token batches found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">
        {{ $batches->links() }}
    </div>
</div>
@endsection
