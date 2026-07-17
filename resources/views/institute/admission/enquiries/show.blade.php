@extends('institute.layout')
@section('title', 'Enquiry Detail')
@section('breadcrumb', 'Admissions / Online Enquiries / ' . $enquiry->name)

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">
        <i class="bi bi-person-lines-fill text-primary me-2"></i> {{ $enquiry->name }}
    </h4>
    <a href="{{ route('enquiries.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Enquiry Details</h6>
                <div class="small text-muted">Mobile</div>
                <div class="mb-2">{{ $enquiry->mobile }}</div>
                <div class="small text-muted">Email</div>
                <div class="mb-2">{{ $enquiry->email }}</div>
                <div class="small text-muted">Course Interested In</div>
                <div class="mb-2">{{ $enquiry->course?->name ?? '-' }}</div>
                <div class="small text-muted">City</div>
                <div class="mb-2">{{ $enquiry->city ?? '-' }}</div>
                <div class="small text-muted">Source</div>
                <div class="mb-2">{{ $enquiry->source }}</div>
                <div class="small text-muted">Received On</div>
                <div>{{ $enquiry->created_at?->format('d M Y, h:i A') }}</div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Status</h6>
                <form method="POST" action="{{ route('enquiries.update-status', $enquiry) }}" class="d-flex gap-2">
                    @csrf
                    <select name="status" class="form-select form-select-sm">
                        @foreach(['new', 'contacted', 'interested', 'not_interested', 'junk'] as $status)
                            <option value="{{ $status }}" {{ $enquiry->status === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary btn-sm">Update</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Assign To</h6>
                <form method="POST" action="{{ route('enquiries.assign', $enquiry) }}" class="d-flex gap-2">
                    @csrf
                    <select name="assigned_staff_id" class="form-select form-select-sm">
                        <option value="">Unassigned</option>
                        @foreach($staffMembers as $staff)
                            <option value="{{ $staff->id }}" {{ $enquiry->assigned_staff_id === $staff->id ? 'selected' : '' }}>
                                {{ $staff->name }}
                            </option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary btn-sm">Save</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Add Follow-up</h6>
                <form method="POST" action="{{ route('enquiries.follow-up.store', $enquiry) }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-3">
                            <select name="type" class="form-select form-select-sm" required>
                                <option value="call">Call</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="email">Email</option>
                                <option value="note">Note</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <input type="text" name="note" class="form-control form-control-sm" placeholder="What was discussed?" required maxlength="1000">
                        </div>
                        <div class="col-md-3">
                            <input type="datetime-local" name="next_follow_up_at" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-primary btn-sm w-100">Add</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Follow-up History</h6>
                @forelse($enquiry->followUps as $followUp)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-light text-dark text-capitalize">{{ $followUp->type }}</span>
                            <span class="small text-muted">{{ $followUp->created_at?->format('d M Y, h:i A') }}</span>
                        </div>
                        <div class="small mt-1">{{ $followUp->note }}</div>
                        <div class="small text-muted">
                            By {{ $followUp->staff?->name ?? 'Institute' }}
                            @if($followUp->next_follow_up_at)
                                &bull; Next follow-up: {{ $followUp->next_follow_up_at->format('d M Y, h:i A') }}
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-clock-history fs-3 d-block mb-2 opacity-25"></i>
                        No follow-ups logged yet
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
