@extends('institute.layout')
@section('title', 'Fee Plans')
@section('breadcrumb', 'Master / Fee Structure / Fee Plans')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-layers me-2 text-primary"></i>Fee Plans</h4>
        <small class="text-muted">Define installment plans — students will select one at admission</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('master.fee-plans.report') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-bar-chart me-1"></i> Report
        </a>
        <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
            <i class="bi bi-plus-circle me-1"></i> New Fee Plan
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show py-2">
    @foreach($errors->all() as $err)<div><i class="bi bi-exclamation-triangle me-1"></i>{{ $err }}</div>@endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Plans Grid ── --}}
@if($plans->isEmpty())
<div class="text-center text-muted py-5">
    <i class="bi bi-layers fs-2 d-block mb-3 text-primary opacity-50"></i>
    <h6>No fee plans found</h6>
    <p class="small">Click "New Fee Plan" above to create your first plan</p>
</div>
@else
<div class="row g-3">
    @foreach($plans as $plan)
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100 {{ $plan->is_active ? '' : 'opacity-50' }}">
            <div class="card-header py-2 d-flex justify-content-between align-items-center"
                 style="background:#1e293b; color:white;">
                <div>
                    <span class="fw-semibold small">{{ $plan->name }}</span>
                    @if($plan->course)
                    <span class="badge bg-info text-dark ms-1" style="font-size:10px;">{{ $plan->course->name }}</span>
                    @else
                    <span class="badge bg-secondary ms-1" style="font-size:10px;">All Courses</span>
                    @endif
                </div>
                <div class="d-flex gap-1">
                    {{-- Edit --}}
                    <button type="button" class="btn btn-outline-light btn-sm py-0 px-1"
                            title="Edit"
                            onclick="openEditModal(this)"
                            data-id="{{ $plan->id }}"
                            data-name="{{ $plan->name }}"
                            data-description="{{ $plan->description ?? '' }}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    {{-- Toggle --}}
                    <form method="POST" action="{{ route('master.fee-plans.toggle', $plan) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm py-0 px-1
                            {{ $plan->is_active ? 'btn-success' : 'btn-outline-secondary' }}"
                            title="{{ $plan->is_active ? 'Deactivate' : 'Activate' }}">
                            <i class="bi {{ $plan->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                        </button>
                    </form>
                    {{-- Delete --}}
                    <form method="POST" action="{{ route('master.fee-plans.destroy', $plan) }}"
                          onsubmit="return confirmDeletePlan(this)"
                          data-plan-name="{{ $plan->name }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body p-3">
                @if($plan->description)
                <p class="text-muted small mb-2">{{ $plan->description }}</p>
                @endif

                <div class="mb-2">
                    <span class="badge bg-primary">{{ $plan->installment_count }} Installment{{ $plan->installment_count > 1 ? 's' : '' }}</span>
                </div>

                {{-- Installments breakdown --}}
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0" style="font-size:12px;">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Label</th>
                                <th class="text-end">%</th>
                                <th>When Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plan->installments as $inst)
                            <tr>
                                <td class="text-muted">{{ $inst->installment_number }}</td>
                                <td class="fw-semibold">{{ $inst->label }}</td>
                                <td class="text-end text-success fw-bold">{{ $inst->percentage }}%</td>
                                <td class="text-muted small">{{ $inst->dueTriggerLabel() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @php
                    $studentCount = $plan->students()->count();
                @endphp
                @if($studentCount > 0)
                <div class="mt-2">
                    <small class="text-muted"><i class="bi bi-people me-1"></i>{{ $studentCount }} student{{ $studentCount > 1 ? 's' : '' }} using this plan</small>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ── Create Modal ── --}}
<div class="modal fade" id="createModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#1e293b; color:white;">
                <h6 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2"></i>New Fee Plan</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('master.fee-plans.store') }}" id="createForm">
                @csrf
                <div class="modal-body p-4">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm"
                                   placeholder="e.g. 2 Installments (50-50)" required maxlength="100"
                                   value="{{ old('name') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Course</label>
                            <select name="course_id" class="form-select form-select-sm">
                                <option value="">All Courses</option>
                                @foreach($courses as $c)
                                <option value="{{ $c->id }}" {{ old('course_id') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Leave blank = applies to all</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">No. of Installments <span class="text-danger">*</span></label>
                            <select name="installment_count" class="form-select form-select-sm"
                                    id="installmentCountSel" onchange="updateInstallmentRows(this.value)"
                                    required>
                                @for($i = 1; $i <= 6; $i++)
                                <option value="{{ $i }}" {{ old('installment_count', 1) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control form-control-sm"
                               placeholder="Optional note..." maxlength="500"
                               value="{{ old('description') }}">
                    </div>

                    {{-- Installment rows --}}
                    <div class="card border-0 bg-light p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold small">Installment Details</span>
                            <span class="badge bg-secondary" id="pctTotal">Total: 0%</span>
                        </div>
                        <div id="installmentRows"></div>
                    </div>

                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg me-1"></i> Create Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Edit Modal ── --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#1e293b; color:white;">
                <h6 class="modal-title fw-semibold"><i class="bi bi-pencil me-2"></i>Edit Fee Plan</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editForm">
                @csrf @method('PATCH')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Plan Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editName" class="form-control form-control-sm"
                               required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <input type="text" name="description" id="editDescription" class="form-control form-control-sm"
                               maxlength="500" placeholder="Optional note...">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function confirmDeletePlan(form) {
    const name = form.dataset.planName || 'this plan';
    return confirm('Delete plan "' + name + '"? This cannot be undone.');
}

function openEditModal(btn) {
    const form = document.getElementById('editForm');
    form.action = '{{ url('master/fee-plans') }}/' + btn.dataset.id;
    document.getElementById('editName').value        = btn.dataset.name || '';
    document.getElementById('editDescription').value = btn.dataset.description || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function openCreateModal() {
    updateInstallmentRows(document.getElementById('installmentCountSel')?.value || 1);
    new bootstrap.Modal(document.getElementById('createModal')).show();
}

function updateInstallmentRows(count) {
    count = parseInt(count) || 1;
    const container = document.getElementById('installmentRows');
    const equalPct  = (100 / count).toFixed(2);
    const labels    = ['At Admission', '2nd Installment', '3rd Installment', '4th Installment', '5th Installment', '6th Installment'];

    container.innerHTML = '';
    for (let i = 0; i < count; i++) {
        const n = i + 1;
        container.innerHTML += `
        <div class="row g-2 mb-2 align-items-end border-bottom pb-2">
            <div class="col-auto text-muted fw-bold" style="width:28px;">${n}</div>
            <div class="col-md-3">
                <label class="form-label small mb-0">Label</label>
                <input type="text" name="installments[${i}][label]" class="form-control form-control-sm"
                       value="${labels[i] || n + ' Installment'}" required maxlength="100">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">% <span class="text-danger">*</span></label>
                <input type="number" name="installments[${i}][percentage]" class="form-control form-control-sm pct-input"
                       value="${equalPct}" min="0.01" max="100" step="0.01" required
                       oninput="updatePctTotal()">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0">When Due</label>
                <select name="installments[${i}][due_trigger]" class="form-select form-select-sm"
                        onchange="toggleDueTrigger(this, ${i})">
                    <option value="at_admission" ${i === 0 ? 'selected' : ''}>At Admission</option>
                    <option value="semester_start" ${i > 0 ? 'selected' : ''}>Semester Start</option>
                    <option value="months_after">After N Months</option>
                </select>
            </div>
            <div class="col-md-2" id="sem-col-${i}" style="display:${i > 0 ? 'block' : 'none'}">
                <label class="form-label small mb-0">Semester #</label>
                <input type="number" name="installments[${i}][due_semester]"
                       class="form-control form-control-sm"
                       value="${n}" min="1" max="12">
            </div>
            <div class="col-md-2" id="mo-col-${i}" style="display:none">
                <label class="form-label small mb-0">Months After</label>
                <input type="number" name="installments[${i}][due_months_after]"
                       class="form-control form-control-sm"
                       value="${n * 3}" min="1" max="60">
            </div>
        </div>`;
    }
    updatePctTotal();
}

function toggleDueTrigger(sel, i) {
    const semCol = document.getElementById('sem-col-' + i);
    const moCol  = document.getElementById('mo-col-' + i);
    semCol.style.display = sel.value === 'semester_start' ? 'block' : 'none';
    moCol.style.display  = sel.value === 'months_after'   ? 'block' : 'none';
}

function updatePctTotal() {
    const inputs = document.querySelectorAll('.pct-input');
    let total = 0;
    inputs.forEach(el => total += parseFloat(el.value) || 0);
    const badge = document.getElementById('pctTotal');
    badge.textContent = 'Total: ' + total.toFixed(2) + '%';
    badge.className   = Math.abs(total - 100) < 0.02 ? 'badge bg-success' : 'badge bg-danger';
}

// Init on page load with old() values if modal was re-opened after error
@if($errors->any() && old('installment_count'))
document.addEventListener('DOMContentLoaded', function() {
    openCreateModal();
});
@endif
</script>
@endpush
