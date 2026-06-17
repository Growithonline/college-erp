@extends('institute.layout')
@section('title','Staff Members')
@section('breadcrumb','Master / Staff / Members')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Staff Members</h4>
        <small class="text-muted">{{ $staff->count() }} active member(s)</small>
    </div>
    <div class="d-flex gap-2">
        @if($trashedCount > 0)
        <a href="{{ route('master.staff-members.trashed') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-archive me-1"></i> Archived
            <span class="badge bg-secondary ms-1">{{ $trashedCount }}</span>
        </a>
        @endif
        <a href="{{ route('master.staff-members.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Staff
        </a>
    </div>
</div>

@if(session('staff_plain_password'))
<div class="alert alert-warning border-warning d-flex align-items-start gap-3 mb-4" role="alert">
    <i class="bi bi-key-fill fs-5 mt-1 text-warning"></i>
    <div>
        <div class="fw-semibold mb-1">Staff Credentials — Shown once only, copy them now</div>
        <div class="small">
            <strong>Email:</strong> {{ session('staff_plain_email') }}&nbsp;&nbsp;
            <strong>Password:</strong>
            <code class="bg-warning-subtle px-2 py-1 rounded">{{ session('staff_plain_password') }}</code>
        </div>
        <div class="text-muted small mt-1">This will not be shown again after you refresh the page.</div>
    </div>
</div>
@endif

@if($staff->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <i class="bi bi-person-badge" style="font-size:3rem;color:#94a3b8;"></i>
            <h5 class="mt-3 text-muted">No Staff Yet</h5>
            <a href="{{ route('master.staff-members.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-lg me-1"></i> Add First Staff
            </a>
        </div>
    </div>
@else
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Access Scope</th>
                        <th>Contact</th>
                        <th>Joining</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($staff as $i => $member)
                    @php
                        $admAll = $member->canViewAllAdmissionData();
                        $feeAll = $member->canViewAllFeeData();
                        $activeOverrides = $hasOverridesTable
                            ? $member->permissionOverrides->filter(fn($o) => $o->isActive())->count()
                            : 0;
                    @endphp
                    <tr>
                        <td class="text-muted small">{{ $i+1 }}</td>
                        <td>
                            <div class="fw-semibold">{{ $member->name }}</div>
                            <small class="text-muted">{{ $member->email }}</small>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                {{ $member->role->name ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1" style="min-width:130px">
                                {{-- Self / All --}}
                                @if($admAll && $feeAll)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" title="Can see all admission & fee data">
                                        <i class="bi bi-eye-fill me-1"></i>All Data
                                    </span>
                                @elseif($admAll || $feeAll)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"
                                          title="{{ $admAll ? 'All admissions' : 'Self admissions' }} · {{ $feeAll ? 'All fee' : 'Self fee' }}">
                                        <i class="bi bi-eye me-1"></i>{{ $admAll ? 'Adm: All' : 'Fee: All' }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" title="Sees only own records">
                                        <i class="bi bi-person me-1"></i>Self Only
                                    </span>
                                @endif

                                {{-- Allowed Sessions (no restriction system yet) --}}
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" title="No session restriction">
                                    <i class="bi bi-calendar3 me-1"></i>All Sessions
                                </span>

                                {{-- Allowed Courses --}}
                                @if(!$member->restrict_course_access)
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" title="No course restriction">
                                        <i class="bi bi-journal-bookmark me-1"></i>All Courses
                                    </span>
                                @elseif($hasCoursePermTable && $member->coursePermissions->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($member->coursePermissions as $cp)
                                            <span class="badge bg-info-subtle text-info border border-info-subtle"
                                                  title="{{ $cp->course->name ?? '?' }}">
                                                {{ \Illuminate\Support\Str::limit($cp->course->name ?? '?', 10) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <i class="bi bi-slash-circle me-1"></i>No Courses
                                    </span>
                                @endif

                                {{-- Overrides Active --}}
                                @if($activeOverrides > 0)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"
                                          title="{{ $activeOverrides }} active permission override(s)">
                                        <i class="bi bi-lightning-charge-fill me-1"></i>{{ $activeOverrides }} Override{{ $activeOverrides > 1 ? 's' : '' }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="small">{{ $member->mobile }}</td>
                        <td class="small">{{ $member->joining_date?->format('d M Y') ?? '-' }}</td>
                        <td class="small fw-semibold">{{ $member->salary ? 'Rs '.number_format((float) $member->salary, 2) : '-' }}</td>
                        <td>
                            <form method="POST" action="{{ route('master.staff-members.toggle', $member) }}">
                                @csrf
                                <button class="btn btn-sm {{ $member->status ? 'btn-success' : 'btn-secondary' }}">
                                    <i class="bi bi-{{ $member->status ? 'check-circle' : 'x-circle' }}"></i>
                                    {{ $member->status ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('master.staff-members.edit', $member) }}"
                                   class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                                <button type="button" class="btn btn-outline-secondary btn-sm" title="Archive Staff"
                                        onclick="openDeleteModal('{{ route('master.staff-members.destroy', $member) }}', '{{ addslashes($member->name) }}')">
                                    <i class="bi bi-archive"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- Archive Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-archive me-2 text-secondary"></i>Archive Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Archive <strong id="deleteTargetName"></strong>?</p>
                <p class="text-muted small mb-0">Staff will be deactivated and moved to Archived list. All data stays safe. You can restore anytime.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-secondary" id="confirmDeleteBtn">
                    <i class="bi bi-archive me-1"></i>Archive
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
var _deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
var _deleteUrl   = '';

function openDeleteModal(url, name) {
    _deleteUrl = url;
    document.getElementById('deleteTargetName').textContent = '"' + name + '"';
    _deleteModal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting…';
    try {
        var res = await fetch(_deleteUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        var data = await res.json();
        _deleteModal.hide();
        if (data.success) {
            window.showToast?.(data.message, 'success');
            setTimeout(function () { window.location.reload(); }, 900);
        } else {
            window.showToast?.(data.message || 'Delete failed.', 'danger');
        }
    } catch (e) {
        _deleteModal.hide();
        window.showToast?.('Network error. Please try again.', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-archive me-1"></i>Archive';
    }
});
</script>
@endpush
@endsection
