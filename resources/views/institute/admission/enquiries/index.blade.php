@extends($layout ?? 'institute.layout')
@section('title', 'Online Enquiries')
@section('breadcrumb', 'Admissions / Online Enquiries')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-chat-left-text text-primary me-2"></i> Online Enquiries
        </h4>
        <small class="text-muted">Leads submitted via the public admission enquiry form</small>
    </div>
</div>

{{-- Public link --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        @if($publicUrl)
            <label class="form-label small fw-semibold mb-1">Your Public Admission Enquiry Link</label>
            <div class="input-group">
                <input type="text" id="publicUrlInput" class="form-control form-control-sm" value="{{ $publicUrl }}" readonly>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyPublicUrl()">
                    <i class="bi bi-clipboard me-1"></i> Copy
                </button>
                <a href="{{ $publicUrl }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>
            <small class="text-muted">Share this link or embed it on your website — students use it to submit enquiries.</small>

            <label class="form-label small fw-semibold mb-1 mt-3">Embed on Your Website</label>
            <div class="input-group">
                <textarea id="embedCodeInput" class="form-control form-control-sm" rows="2" readonly style="font-family: monospace; font-size: 12px;">&lt;iframe src="{{ $publicUrl }}" style="width:100%;height:800px;border:0;" title="Admission Enquiry"&gt;&lt;/iframe&gt;</textarea>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyEmbedCode()">
                    <i class="bi bi-clipboard me-1"></i> Copy
                </button>
            </div>
            <small class="text-muted">Paste this code into your own website's HTML to embed the enquiry form directly.</small>
        @else
            <div class="text-warning small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Your institute doesn't have a short code set yet, so the public enquiry link isn't available. Contact support to get one assigned.
            </div>
        @endif
    </div>
</div>

{{-- Filter --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Name / Mobile / Email" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    @foreach(['new', 'contacted', 'interested', 'not_interested', 'junk'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Course</label>
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ (string) request('course_id') === (string) $course->id ? 'selected' : '' }}>
                            {{ $course->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if($canViewAll ?? true)
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Assigned To</label>
                <select name="assigned_staff_id" class="form-select form-select-sm">
                    <option value="">Anyone</option>
                    @foreach($staffMembers as $staff)
                        <option value="{{ $staff->id }}" {{ (string) request('assigned_staff_id') === (string) $staff->id ? 'selected' : '' }}>
                            {{ $staff->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route(($routePrefix ?? '').'enquiries.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Received</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enquiries as $i => $enquiry)
                    <tr>
                        <td class="ps-3 text-muted">{{ $enquiries->firstItem() + $i }}</td>
                        <td class="fw-semibold">{{ $enquiry->name }}</td>
                        <td>{{ $enquiry->mobile }}</td>
                        <td class="small">{{ $enquiry->email }}</td>
                        <td class="small">{{ $enquiry->course?->name ?? '-' }}</td>
                        <td>
                            <span class="badge bg-{{ ['new' => 'primary', 'contacted' => 'info', 'interested' => 'success', 'not_interested' => 'secondary', 'junk' => 'danger'][$enquiry->status] ?? 'secondary' }}-subtle text-{{ ['new' => 'primary', 'contacted' => 'info', 'interested' => 'success', 'not_interested' => 'secondary', 'junk' => 'danger'][$enquiry->status] ?? 'secondary' }}">
                                {{ ucfirst(str_replace('_', ' ', $enquiry->status)) }}
                            </span>
                        </td>
                        <td class="small">{{ $enquiry->assignedStaff?->name ?? '-' }}</td>
                        <td class="small">{{ $enquiry->created_at?->format('d M Y, h:i A') }}</td>
                        <td>
                            <a href="{{ route(($routePrefix ?? '').'enquiries.show', $enquiry) }}"
                               class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:11px">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-chat-left-text fs-3 d-block mb-2 opacity-25"></i>
                            <div class="fw-semibold">No enquiries found</div>
                            <small>Leads submitted via your public admission form will appear here</small>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($enquiries->hasPages())
        <div class="card-footer bg-white">
            {{ $enquiries->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
    function copyPublicUrl() {
        const input = document.getElementById('publicUrlInput');
        input.select();
        navigator.clipboard.writeText(input.value);
    }

    function copyEmbedCode() {
        const input = document.getElementById('embedCodeInput');
        input.select();
        navigator.clipboard.writeText(input.value);
    }
</script>
@endpush

@endsection
