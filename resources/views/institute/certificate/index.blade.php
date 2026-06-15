@extends('institute.layout')
@section('title', 'Issued Certificates')
@section('breadcrumb', 'Certificates / All')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-award me-2 text-primary"></i>Issued Certificates</h4>
        <small class="text-muted">Sab issued, draft aur cancelled certificates yahin dikhenge</small>
    </div>
    <a href="{{ route('certificate.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Issue Certificate
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show">
        <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Search Student</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Name, Enrollment No..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Certificate Type</label>
                <select name="type_id" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" {{ request('type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>Issued</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel"></i></button>
                <a href="{{ route('certificate.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x"></i></a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
        <span class="fw-semibold">Certificates</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary-subtle text-secondary border">{{ $certificates->total() }} total</span>
            <span class="text-muted small">Page {{ $certificates->currentPage() }} of {{ $certificates->lastPage() }}</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:12.5px; min-width:1200px;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 text-center" style="width:48px;">S.No</th>
                    <th style="min-width:130px;">Certificate No.</th>
                    <th style="min-width:160px;">Student</th>
                    <th style="min-width:150px;">Father / Mother</th>
                    <th style="min-width:160px;">Course / Stream</th>
                    <th style="min-width:130px;">Contact</th>
                    <th style="min-width:110px;">Type</th>
                    <th style="min-width:90px;">Issued By</th>
                    <th style="min-width:90px;">Date</th>
                    <th style="min-width:80px;">Status</th>
                    <th class="pe-3 text-end" style="min-width:110px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($certificates as $cert)
                @php $s = $cert->student; @endphp
                <tr>
                    {{-- S.No --}}
                    <td class="ps-3 text-center text-muted">
                        {{ ($certificates->currentPage() - 1) * $certificates->perPage() + $loop->iteration }}
                    </td>

                    {{-- Certificate No --}}
                    <td>
                        <div class="fw-semibold text-primary">{{ $cert->certificate_number }}</div>
                    </td>

                    {{-- Student --}}
                    <td>
                        <div class="fw-semibold">{{ $s?->name }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $s?->student_uid }}</div>
                        @if($s?->enrollment_no)
                            <div class="text-muted" style="font-size:11px;">Enroll: {{ $s->enrollment_no }}</div>
                        @endif
                    </td>

                    {{-- Father / Mother --}}
                    <td>
                        @if($s?->father_name)
                            <div><span class="text-muted" style="font-size:11px;">Father:</span> {{ $s->father_name }}</div>
                        @endif
                        @if($s?->mother_name)
                            <div><span class="text-muted" style="font-size:11px;">Mother:</span> {{ $s->mother_name }}</div>
                        @endif
                        @if(!$s?->father_name && !$s?->mother_name)
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- Course / Stream --}}
                    <td>
                        @if($s?->stream?->course)
                            <div class="fw-semibold" style="font-size:11.5px;">{{ $s->stream->course->name }}</div>
                        @endif
                        @if($s?->stream)
                            <div class="text-muted" style="font-size:11px;">{{ $s->stream->name }}</div>
                        @endif
                        @if($s?->current_semester)
                            <div class="text-muted" style="font-size:11px;">Sem {{ $s->current_semester }}</div>
                        @endif
                        @if(!$s?->stream)
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- Contact --}}
                    <td>
                        @if($s?->mobile)
                            <div><i class="bi bi-telephone text-muted me-1" style="font-size:10px;"></i>{{ $s->mobile }}</div>
                        @endif
                        @if($s?->email)
                            <div class="text-muted" style="font-size:11px;"><i class="bi bi-envelope me-1" style="font-size:10px;"></i>{{ $s->email }}</div>
                        @endif
                        @if(!$s?->mobile && !$s?->email)
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- Type --}}
                    <td>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            {{ $cert->certificateType?->name }}
                        </span>
                    </td>

                    {{-- Issued By --}}
                    <td class="text-muted">{{ $cert->issuedBy?->name ?? 'N/A' }}</td>

                    {{-- Date --}}
                    <td class="text-muted">{{ \Carbon\Carbon::parse($cert->issued_at)->format('d/m/Y') }}</td>

                    {{-- Status --}}
                    <td>
                        @if($cert->status === 'issued')
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Issued</span>
                        @elseif($cert->status === 'cancelled')
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Cancelled</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Draft</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="pe-3 text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            @if($cert->status !== 'cancelled')
                                <a href="{{ route('certificate.show', $cert) }}" target="_blank"
                                   class="btn btn-sm btn-outline-primary" title="View PDF">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('certificate.download', $cert) }}"
                                   class="btn btn-sm btn-outline-success" title="Download PDF">
                                    <i class="bi bi-download"></i>
                                </a>
                                <form method="POST" action="{{ route('certificate.cancel', $cert) }}" class="d-inline"
                                      onsubmit="return confirm('{{ $cert->certificate_number }} cancel karna chahte hain?')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center py-5">
                        <i class="bi bi-award text-muted" style="font-size:2.5rem;"></i>
                        <div class="mt-2 fw-semibold text-muted">Koi certificate nahi mila</div>
                        <div class="text-muted small mt-1">
                            <a href="{{ route('certificate.create') }}" class="text-decoration-none">Issue Certificate</a> karke shuru karo
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($certificates->hasPages())
    <div class="card-footer bg-white py-3">
        {{ $certificates->links() }}
    </div>
    @endif
</div>

@endsection
