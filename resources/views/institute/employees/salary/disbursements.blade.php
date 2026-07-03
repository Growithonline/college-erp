@extends('institute.layout')
@section('title', 'Salary Disbursements')
@section('breadcrumb', 'Employees / ' . $employee->name . ' / Salary')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Salary Disbursements</h4>
        <p class="text-muted mb-0" style="font-size:13px;">{{ $employee->name }}</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg me-1"></i> Add Disbursement
        </button>
        <a href="{{ route('employees.show', $employee) }}" class="btn btn-light btn-sm px-3">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show py-2">
        <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- CTC Summary Card --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <div class="small text-muted">Basic Salary</div>
                <div class="fw-bold fs-5">₹{{ number_format($employee->basic_salary, 2) }}</div>
            </div>
            @foreach($activeComponents as $comp)
            <div class="col-md-2">
                <div class="small text-muted">{{ $comp->display_label ?? strtoupper($comp->component_type) }}</div>
                <div class="fw-semibold text-primary">+₹{{ number_format($comp->amount, 2) }}</div>
            </div>
            @endforeach
            <div class="col-md-3">
                <div class="small text-muted fw-semibold">Total CTC (Gross)</div>
                <div class="fw-bold fs-5 text-success" id="ctcDisplay">₹{{ number_format($ctc, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($disbursements->count())
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="ps-4">Month / Year</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Deductions</th>
                        <th class="text-end">Net Paid</th>
                        <th>Mode</th>
                        <th>Paid On</th>
                        <th>Status</th>
                        <th class="pe-3">Journal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($disbursements as $dis)
                    <tr>
                        <td class="ps-4 fw-medium">{{ $dis->month_name }} {{ $dis->year }}</td>
                        <td class="text-end text-muted">₹{{ number_format($dis->gross_salary, 2) }}</td>
                        <td class="text-end {{ (float) $dis->deductions > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ (float) $dis->deductions > 0 ? '−₹' . number_format($dis->deductions, 2) : '—' }}
                        </td>
                        <td class="text-end fw-semibold">₹{{ number_format($dis->net_salary, 2) }}</td>
                        <td>
                            <span class="badge text-bg-light text-dark border" style="font-size:11px;">
                                {{ strtoupper($dis->payment_mode ?? '—') }}
                            </span>
                        </td>
                        <td class="text-muted" style="font-size:13px;">
                            {{ $dis->payment_date?->format('d M Y') ?? '—' }}
                        </td>
                        <td>
                            @if($dis->status === 'paid')
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:11px;">
                                    {{ $dis->journal_entry_id ? 'Paid & Posted' : 'Paid' }}
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:11px;">Pending</span>
                            @endif
                        </td>
                        <td class="pe-3">
                            @if($dis->journal_entry_id)
                                <span class="small text-muted">JE #{{ $dis->journal_entry_id }}</span>
                            @elseif(!$dis->journal_entry_id && $dis->status === 'paid')
                                <span class="small text-warning">Posting pending</span>
                            @else
                                <form method="POST" action="{{ route('employees.salary.destroyDisbursement', [$employee, $dis]) }}"
                                      onsubmit="return confirm('Delete this unpaid record?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $disbursements->links() }}</div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-calendar3 opacity-25 fs-2 d-block mb-2"></i>
            <p class="mb-3">No salary disbursements recorded.</p>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-lg me-1"></i> Add First Disbursement
            </button>
        </div>
        @endif
    </div>
</div>

{{-- Add Disbursement Modal --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.salary.storeDisbursement', $employee) }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold">Add Salary Disbursement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    {{-- CTC Preview --}}
                    <div class="alert alert-info py-2 mb-3" style="font-size:13px;">
                        <strong>Auto-calculated from salary structure:</strong>
                        Basic ₹{{ number_format($employee->basic_salary, 2) }}
                        @foreach($activeComponents as $comp)
                            + {{ $comp->display_label ?? strtoupper($comp->component_type) }} ₹{{ number_format($comp->amount, 2) }}
                        @endforeach
                        = <strong>Gross ₹{{ number_format($ctc, 2) }}</strong>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Month <span class="text-danger">*</span></label>
                            <select class="form-select" name="month" id="selMonth" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0,0,0,$m,1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Year <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="year" value="{{ date('Y') }}" min="2020" max="2099" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Deductions (₹)</label>
                            <input type="number" class="form-control" name="deductions" id="inpDed" min="0" step="0.01" value="0">
                            <div class="form-text">Advance recovery, etc.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Gross Salary</label>
                            <div class="form-control bg-light fw-semibold text-success" id="dispGross">
                                ₹{{ number_format($ctc, 2) }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Net Salary (after deductions)</label>
                            <div class="form-control bg-light fw-bold text-primary" id="dispNet">
                                ₹{{ number_format($ctc, 2) }}
                            </div>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Expense Account <span class="text-danger">*</span></label>
                            <select class="form-select" name="expense_account_id" required>
                                <option value="">Select expense head...</option>
                                @foreach($expenseAccounts as $acc)
                                    <option value="{{ $acc->id }}"
                                        {{ (old('expense_account_id') == $acc->id) ? 'selected' : '' }}>
                                        {{ $acc->code }} — {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-medium">Payment Mode <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_mode" id="selPayMode" required>
                                <option value="cash" {{ (old('payment_mode') === 'cash' || !old('payment_mode')) ? 'selected' : '' }}>Cash</option>
                                <option value="bank" {{ old('payment_mode') === 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-8" id="bankRow" style="display:none;">
                            <label class="form-label fw-medium">Bank Account <span class="text-danger">*</span></label>
                            <select class="form-select" name="bank_account_id">
                                <option value="">Select bank account...</option>
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->id }}" {{ old('bank_account_id') == $bank->id ? 'selected' : '' }}>
                                        {{ $bank->bank_name }} — {{ $bank->account_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-medium">Notes</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Remarks...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-floppy me-1"></i> Save & Post
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const CTC = {{ $ctc }};

document.getElementById('inpDed').addEventListener('input', function () {
    const ded = parseFloat(this.value) || 0;
    const net = Math.max(0, CTC - ded);
    document.getElementById('dispGross').textContent = '₹' + CTC.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('dispNet').textContent   = '₹' + net.toLocaleString('en-IN', {minimumFractionDigits: 2});
});

document.getElementById('selPayMode').addEventListener('change', function () {
    document.getElementById('bankRow').style.display = this.value === 'bank' ? '' : 'none';
});

// Show bank row if old value was bank (on validation error reopen)
if (document.getElementById('selPayMode').value === 'bank') {
    document.getElementById('bankRow').style.display = '';
}

@if($errors->any())
    document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('addModal')).show());
@endif
</script>
@endsection
