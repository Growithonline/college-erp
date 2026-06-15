@extends($libraryLayout)
@section('title', 'Library Fine Collection')
@section('breadcrumb', 'Library / Fine Collection')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Fine Collection</h4>
        <small class="text-muted">Pending library fines yahan se collect karo.</small>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Members with Pending Fine</small>
                <h4 class="mb-0 text-danger">{{ $stats->members_with_fines ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Total Pending Fine</small>
                <h4 class="mb-0 text-danger">Rs {{ number_format((float)($stats->total_pending ?? 0), 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted">Collected Today</small>
                <h4 class="mb-0 text-success">Rs {{ number_format($collectedToday, 2) }}</h4>
            </div>
        </div>
    </div>
</div>

{{-- Search --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-lg-9">
                <input type="text" name="search" value="{{ $search }}" class="form-control"
                       placeholder="Member code / naam / mobile se search karo">
            </div>
            <div class="col-sm-6 col-lg-2 d-grid">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
            </div>
            <div class="col-sm-6 col-lg-1 d-grid">
                <a href="{{ route($libraryRoutePrefix . '.fines.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Member</th>
                        <th>Code / Type</th>
                        <th>Roll No / UIN</th>
                        <th>Father / Mother</th>
                        <th class="text-end">Pending Fine</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $member->name }}</div>
                                <div class="small text-muted">{{ $member->mobile ?? '-' }}</div>
                                @if($member->student)
                                    <div class="small text-muted">{{ $member->student->stream->course->name ?? '-' }}</div>
                                @elseif($member->staffMember)
                                    <div class="small text-muted">{{ $member->staffMember->role->name ?? '-' }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="small fw-semibold">{{ $member->member_code }}</div>
                                <span class="badge bg-secondary">{{ ucfirst($member->member_type) }}</span>
                            </td>
                            <td class="small">
                                @if($member->student)
                                    <div>Roll: {{ $member->student->roll_no ?: '-' }}</div>
                                    <div>UIN: {{ $member->student->uin_no ?: '-' }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">
                                @if($member->student)
                                    <div>{{ $member->student->father_name ?: '-' }}</div>
                                    <div class="text-muted">{{ $member->student->mother_name ?: '-' }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-danger">
                                Rs {{ number_format($member->pending_fine_total, 2) }}
                            </td>
                            <td class="text-center">
                                <a href="{{ route($libraryRoutePrefix . '.fines.show', $member) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-cash-coin me-1"></i>Collect
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                Koi pending fine nahi hai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($members->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                Showing {{ $members->firstItem() }} to {{ $members->lastItem() }} of {{ $members->total() }} members
            </div>
            {{ $members->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@endsection
