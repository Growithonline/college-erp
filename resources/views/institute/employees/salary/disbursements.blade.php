@extends('institute.layout')
@section('title', 'Salary Disbursements')
@section('breadcrumb', 'Employees / ' . $employee->name . ' / Salary')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Salary Disbursements</h4>
        <p class="text-muted mb-0" style="font-size:13px;">{{ $employee->name }} &mdash; Basic: ₹{{ number_format($employee->basic_salary, 2) }}</p>
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
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show py-2">
        <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($disbursements->count())
        <table class="table table-hover mb-0 align-middle">
            <thead style="background:#f8fafc;">
                <tr>
                    <th class="ps-4">Month / Year</th>
                    <th>Gross</th>
                    <th>Deductions</th>
                    <th>Net Paid</th>
                    <th>Paid On</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($disbursements as $dis)
                <tr>
                    <td class="ps-4 fw-medium">{{ $dis->month_name }} {{ $dis->year }}</td>
                    <td>₹{{ number_format($dis->gross_salary, 2) }}</td>
                    <td class="text-danger">
                        @if($dis->deductions > 0) -₹{{ number_format($dis->deductions, 2) }} @else — @endif
                    </td>
                    <td class="fw-semibold">₹{{ number_format($dis->net_salary, 2) }}</td>
                    <td class="text-muted" style="font-size:13px;">
                        {{ $dis->payment_date?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="text-muted" style="font-size:12px;">{{ $dis->remarks ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.salary.storeDisbursement', $employee) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Add Salary Disbursement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Month <span class="text-danger">*</span></label>
                            <select class="form-select" name="month" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0,0,0,$m,1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Year <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="year" value="{{ date('Y') }}" min="2000" max="2099" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Working Days</label>
                            <input type="number" class="form-control" name="working_days" min="0" max="31" placeholder="e.g. 26">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Gross Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="gross_amount" min="0" step="0.01" value="{{ $employee->basic_salary }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Deductions (₹)</label>
                            <input type="number" class="form-control" name="deductions" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Net Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="net_amount" id="netAmount" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Paid On</label>
                            <input type="date" class="form-control" name="paid_on" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Notes</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Remarks..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-floppy me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-calc net = gross - deductions
document.querySelector('[name=gross_amount]').addEventListener('input', calcNet);
document.querySelector('[name=deductions]').addEventListener('input', calcNet);
function calcNet() {
    const gross = parseFloat(document.querySelector('[name=gross_amount]').value) || 0;
    const ded   = parseFloat(document.querySelector('[name=deductions]').value) || 0;
    document.getElementById('netAmount').value = (gross - ded).toFixed(2);
}
@if($errors->any())
    document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('addModal')).show());
@endif
</script>
@endsection
