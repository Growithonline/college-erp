@extends('institute.layout')
@section('title', 'Expense Permissions & Approval Limits')
@section('breadcrumb', 'Finance / Wallet / Expense Permissions')

@section('content')
<div class="mb-4">
    <h4 class="mb-0 fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Expense Permissions & Approval Limits</h4>
    <small class="text-muted">
        Set expense creation permission <strong>ON/OFF</strong> per role and define the maximum amount
        that can be auto-approved without admin review.
    </small>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>Could not save:</strong> {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Legend --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 bg-light h-100">
            <div class="card-body p-3">
                <div class="fw-semibold small mb-1">
                    <i class="bi bi-toggle-on text-success me-1"></i>Can Create = ON
                </div>
                <div class="small text-muted">Staff in this role can add expenses</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 bg-light h-100">
            <div class="card-body p-3">
                <div class="fw-semibold small mb-1">
                    <i class="bi bi-currency-rupee text-warning me-1"></i>Limit = Rs 0
                </div>
                <div class="small text-muted">All expenses require admin approval before the wallet is debited</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 bg-light h-100">
            <div class="card-body p-3">
                <div class="fw-semibold small mb-1">
                    <i class="bi bi-currency-rupee text-success me-1"></i>Limit = Rs 2,000
                </div>
                <div class="small text-muted">Up to ₹2,000 is auto-approved and wallet debited; above that requires admin approval</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('finance.wallet.approval-limits.update') }}">
            @csrf

            {{-- Institute Admin row --}}
            <div class="mb-3 p-3 border rounded bg-success bg-opacity-10">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-semibold">Institute Admin (Web Portal)</div>
                        <small class="text-muted">No restrictions — all expenses are auto-approved</small>
                    </div>
                    <div class="d-flex gap-3 align-items-center">
                        <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Always Allowed</span>
                        <span class="badge bg-success">Unlimited Auto-Approve</span>
                    </div>
                </div>
            </div>

            @forelse($roles as $role)
            @php
                $hasExpenseCreate  = $role->hasPermission('expense_create');
                $hasFinanceManage  = $role->hasPermission('finance_manage');
                $canCreate         = $hasExpenseCreate || $hasFinanceManage;
                $limit             = (float) ($limits[$role->id] ?? 0);
                // If finance_manage is ON, toggle is locked — must be changed via Staff Role settings
                $toggleLocked      = $hasFinanceManage;
            @endphp
            <div class="mb-3 p-3 border rounded {{ !$canCreate ? 'border-secondary opacity-75' : '' }}">
                <div class="row align-items-center g-3">

                    <div class="col-md-3">
                        <div class="fw-semibold">{{ $role->name }}</div>
                        @if($hasFinanceManage)
                            <span class="badge bg-warning text-dark small mt-1">
                                <i class="bi bi-lock-fill me-1"></i>finance_manage ON
                            </span>
                        @endif
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1">Can Create Expense</label>
                        @if($toggleLocked)
                            {{-- finance_manage is ON: toggle is locked — must be changed via Staff Role settings --}}
                            <div class="form-check form-switch opacity-50">
                                <input class="form-check-input" type="checkbox" disabled checked>
                                <label class="form-check-label text-warning small">
                                    Locked (finance_manage ON)
                                </label>
                            </div>
                            <div class="small text-muted mt-1">
                                <i class="bi bi-info-circle me-1"></i>
                                To restrict, turn off <strong>finance_manage</strong> in Staff Role settings.
                            </div>
                        @else
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       name="expense_create[{{ $role->id }}]"
                                       id="can_create_{{ $role->id }}"
                                       value="1"
                                       {{ $hasExpenseCreate ? 'checked' : '' }}
                                       onchange="toggleRow({{ $role->id }}, this.checked)">
                                <label class="form-check-label" for="can_create_{{ $role->id }}">
                                    <span id="can_create_label_{{ $role->id }}">
                                        {{ $hasExpenseCreate ? 'Allowed' : 'Not Allowed' }}
                                    </span>
                                </label>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-4"
                         id="limit_col_{{ $role->id }}"
                         style="{{ $canCreate ? '' : 'opacity:0.4' }}">
                        <label class="form-label small fw-semibold mb-1">Auto-Approve up to</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rs</span>
                            <input type="number" step="100" min="0"
                                   name="limits[{{ $role->id }}]"
                                   class="form-control"
                                   value="{{ old('limits.'.$role->id, $limit > 0 ? $limit : '') }}"
                                   placeholder="0 = all need approval"
                                   {{ !$canCreate ? 'disabled' : '' }}>
                        </div>
                        <div class="form-text small">0 = all expenses require admin approval</div>
                    </div>

                    <div class="col-md-2">
                        @if(!$canCreate)
                            <span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i>No Access</span>
                        @elseif($limit > 0)
                            <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Up to Rs {{ number_format($limit) }}</span>
                        @else
                            <span class="badge bg-warning text-dark">All need approval</span>
                        @endif
                    </div>

                </div>
            </div>
            @empty
            <div class="alert alert-warning">
                No staff roles found. Please <a href="{{ route('institute.master.roles.index') }}">create staff roles</a> first.
            </div>
            @endforelse

            @if($roles->isNotEmpty())
            <div class="mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Save All Permissions & Limits
                </button>
            </div>
            @endif
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleRow(roleId, canCreate) {
    const col = document.getElementById('limit_col_' + roleId);
    const label = document.getElementById('can_create_label_' + roleId);
    col.style.opacity = canCreate ? '1' : '0.4';
    col.style.pointerEvents = canCreate ? '' : 'none';
    label.textContent = canCreate ? 'Allowed' : 'Not Allowed';
}
</script>
@endpush
