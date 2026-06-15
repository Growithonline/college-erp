@extends($libraryLayout)
@section('title', 'Library Members')
@section('breadcrumb', 'Library / Members')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Library Members</h4>
        <small class="text-muted">Students aur staff ko library membership me sync aur manage karo.</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <form method="POST" action="{{ route($libraryRoutePrefix . '.members.sync-students') }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-people me-1"></i>Sync Students</button>
        </form>
        <form method="POST" action="{{ route($libraryRoutePrefix . '.members.sync-staff') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-person-badge me-1"></i>Sync Staff</button>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Student Members</small><h4 class="mb-0">{{ $stats['student_members'] }}</h4></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Staff / Faculty</small><h4 class="mb-0">{{ $stats['staff_members'] }}</h4></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Blocked</small><h4 class="mb-0 text-danger">{{ $stats['blocked_members'] }}</h4></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Active Issues</small><h4 class="mb-0 text-primary">{{ $stats['active_issues'] }}</h4></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-lg-9">
                <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search member code, name, mobile, email">
            </div>
            <div class="col-sm-6 col-lg-2 d-grid">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
            </div>
            <div class="col-sm-6 col-lg-1 d-grid">
                <a href="{{ route($libraryRoutePrefix . '.members.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="card-body p-3 p-lg-4">
        @forelse($members as $member)
            <div class="border rounded-3 p-3 p-lg-4 mb-3 bg-white">
                <div class="row g-3 align-items-start">
                    <div class="col-lg-4">
                        <div class="d-flex flex-column h-100">
                            <div class="fw-bold fs-5">{{ $member->name }}</div>
                            <div class="text-muted small mb-2">{{ $member->member_code }} | {{ ucfirst($member->member_type) }}</div>

                            @if($member->student)
                                <div class="small">
                                    <div class="fw-semibold text-dark">{{ $member->student->student_uid }}</div>
                                    <div class="text-muted">{{ $member->student->stream->course->name ?? '-' }}</div>
                                    @if($member->student->roll_no)
                                        <div class="text-muted">Roll: {{ $member->student->roll_no }}</div>
                                    @endif
                                    @if($member->student->uin_no)
                                        <div class="text-muted">UIN: {{ $member->student->uin_no }}</div>
                                    @endif
                                    @if($member->student->father_name)
                                        <div class="text-muted">Father: {{ $member->student->father_name }}</div>
                                    @endif
                                    @if($member->student->mother_name)
                                        <div class="text-muted">Mother: {{ $member->student->mother_name }}</div>
                                    @endif
                                </div>
                            @elseif($member->staffMember)
                                <div class="small">
                                    <div class="fw-semibold text-dark">{{ $member->staffMember->name }}</div>
                                    <div class="text-muted">{{ $member->staffMember->role->name ?? '-' }}</div>
                                </div>
                            @else
                                <div class="text-muted small">Linked record unavailable</div>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <form method="POST" action="{{ route($libraryRoutePrefix . '.members.update', $member) }}">
                            @csrf
                            @method('PUT')

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted mb-1">Rule Set</label>
                                    <select name="rule_set_id" class="form-select">
                                        <option value="">No rule</option>
                                        @foreach($ruleSets as $ruleSet)
                                            <option value="{{ $ruleSet->id }}" @selected($member->rule_set_id == $ruleSet->id)>{{ $ruleSet->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted mb-1">Current Summary</label>
                                    <div class="border rounded-3 px-3 py-2 bg-light h-100">
                                        <div class="small">{{ $member->activeTransactions->count() }} active issue(s)</div>
                                        <div class="small text-danger">Fine: Rs {{ number_format($member->pending_fine, 2) }}</div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                                    <select name="status" class="form-select">
                                        @foreach(['active' => 'Active', 'blocked' => 'Blocked', 'inactive' => 'Inactive'] as $statusValue => $statusLabel)
                                            <option value="{{ $statusValue }}" @selected($member->status === $statusValue)>{{ $statusLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold text-muted mb-1">Blocked Reason</label>
                                    <input type="text" name="blocked_reason" value="{{ $member->blocked_reason }}" class="form-control" placeholder="Optional reason">
                                </div>

                                <div class="col-md-3 d-grid">
                                    <label class="form-label small fw-semibold text-muted mb-1 d-none d-md-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5">Abhi koi library member nahi hai. Pehle sync karo.</div>
        @endforelse
    </div>
    @if($members->hasPages())
        <div class="card-footer bg-white d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
            <div class="small text-muted">
                Showing {{ $members->firstItem() }} to {{ $members->lastItem() }} of {{ $members->total() }} members
            </div>
            <div>
                {{ $members->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>
@endsection
