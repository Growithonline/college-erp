@extends('institute.layout')
@section('title', $staffMember->name)
@section('breadcrumb', 'Master / Staff / ' . $staffMember->name)

@section('content')
@php
    $totalAllowances = round(
        ((float) $staffMember->monthly_salary * ((int) ($staffMember->hra_percent ?? 0) / 100))
        + ((float) $staffMember->monthly_salary * ((int) ($staffMember->da_percent ?? 0) / 100))
        + (float) ($staffMember->ta_amount ?? 0)
        + (float) ($staffMember->medical_amount ?? 0),
        2
    );
    $approxGross = round((float) $staffMember->monthly_salary + $totalAllowances, 2);
    $alertDocs = $staffMember->documents->filter(fn ($d) => $d->is_expired || $d->is_expiring_soon);
@endphp

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-4">
    @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Header --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center gap-4">
            @if($staffMember->photo)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($staffMember->photo) }}" class="rounded-circle" width="80" height="80" style="object-fit:cover;">
            @else
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width:80px;height:80px;font-size:28px;">
                    {{ strtoupper(substr($staffMember->name, 0, 1)) }}
                </div>
            @endif
            <div class="flex-grow-1">
                <h5 class="fw-bold mb-1">{{ $staffMember->name }}</h5>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @if($staffMember->role)
                        <span class="badge text-bg-light text-dark border">{{ $staffMember->role->name }}</span>
                    @endif
                    <span class="badge text-bg-light text-dark border">{{ $staffMember->staff_category }}</span>
                    <span class="badge text-bg-light text-dark border">{{ ucfirst($staffMember->payroll_type) }} payroll</span>
                    <span class="badge text-bg-{{ $staffMember->status ? 'success' : 'secondary' }}">{{ $staffMember->status ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="text-muted mt-1" style="font-size:13px;">
                    Joined: {{ $staffMember->joining_date?->format('d M Y') ?? '—' }} &nbsp;|&nbsp;
                    {{ $staffMember->mobile }} @if($staffMember->email) &nbsp;|&nbsp; {{ $staffMember->email }} @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('master.staff-members.edit', $staffMember) }}" class="btn btn-outline-primary btn-sm px-3">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                <a href="{{ route('master.staff-members.index') }}" class="btn btn-light btn-sm px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Document expiry alerts --}}
@if($alertDocs->count())
<div class="alert alert-warning py-2 mb-4">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Document Alert:</strong>
    @foreach($alertDocs as $doc)
        {{ $doc->type_label }} {{ $doc->is_expired ? 'expired on' : 'expires on' }} {{ $doc->expiry_date->format('d M Y') }}{{ !$loop->last ? ', ' : '.' }}
    @endforeach
</div>
@endif

{{-- Tabs --}}
<ul class="nav nav-tabs mb-4" id="staffTabs">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#tab-info"><i class="bi bi-info-circle me-1"></i> Info</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-salary"><i class="bi bi-cash-stack me-1"></i> Salary</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-bonuses"><i class="bi bi-gift me-1"></i> Bonuses</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-advances"><i class="bi bi-wallet2 me-1"></i> Advances</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-docs"><i class="bi bi-file-earmark me-1"></i> Documents</a>
    </li>
</ul>

<div class="tab-content">
    {{-- INFO TAB --}}
    <div class="tab-pane fade show active" id="tab-info">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Personal</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" width="40%">Mobile</td><td>{{ $staffMember->mobile }}</td></tr>
                            <tr><td class="text-muted">Email</td><td>{{ $staffMember->email ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Address</td><td>{{ $staffMember->address ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Joined</td><td>{{ $staffMember->joining_date?->format('d M Y') ?? '—' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Employment &amp; Bank</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" width="40%">Role</td><td>{{ $staffMember->role?->name ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Category</td><td>{{ $staffMember->staff_category }}</td></tr>
                            <tr><td class="text-muted">Payroll Type</td><td>{{ ucfirst($staffMember->payroll_type) }}</td></tr>
                            <tr><td class="text-muted">{{ $staffMember->payroll_type === 'monthly' ? 'Monthly Salary' : 'Daily Wage' }}</td>
                                <td>₹{{ number_format($staffMember->payroll_type === 'monthly' ? $staffMember->monthly_salary : $staffMember->daily_wage, 2) }}</td></tr>
                            <tr><td class="text-muted">Bank</td><td>{{ $staffMember->bank_name ?? '—' }} {{ $staffMember->bank_ifsc ? '('.$staffMember->bank_ifsc.')' : '' }}</td></tr>
                            <tr><td class="text-muted">Account No.</td><td>{{ $staffMember->bank_account_number ?? '—' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SALARY TAB --}}
    <div class="tab-pane fade" id="tab-salary">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-semibold mb-0">Salary Structure</h6>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editStructureModal">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </div>
                    <div class="card-body p-0">
                        @if($staffMember->payroll_type === 'monthly')
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr><td class="ps-3">Basic</td><td class="text-end pe-3">₹{{ number_format($staffMember->monthly_salary, 2) }}</td></tr>
                                <tr><td class="ps-3">HRA ({{ (int) $staffMember->hra_percent }}%)</td><td class="text-end pe-3">₹{{ number_format($staffMember->monthly_salary * $staffMember->hra_percent / 100, 2) }}</td></tr>
                                <tr><td class="ps-3">DA ({{ (int) $staffMember->da_percent }}%)</td><td class="text-end pe-3">₹{{ number_format($staffMember->monthly_salary * $staffMember->da_percent / 100, 2) }}</td></tr>
                                <tr><td class="ps-3">TA</td><td class="text-end pe-3">₹{{ number_format($staffMember->ta_amount, 2) }}</td></tr>
                                <tr><td class="ps-3">Medical</td><td class="text-end pe-3">₹{{ number_format($staffMember->medical_amount, 2) }}</td></tr>
                                <tr class="table-light fw-bold"><td class="ps-3">Approx. Gross</td><td class="text-end pe-3">₹{{ number_format($approxGross, 2) }}</td></tr>
                                <tr><td class="ps-3">PF Applicable</td><td class="text-end pe-3">{{ $staffMember->pf_applicable ? 'Yes (12% up to ₹15,000 basic)' : 'No' }}</td></tr>
                                <tr><td class="ps-3">TDS / month</td><td class="text-end pe-3">₹{{ number_format($staffMember->tds_monthly, 2) }}</td></tr>
                                <tr><td class="ps-3">Professional Tax / month</td><td class="text-end pe-3">₹{{ number_format($staffMember->professional_tax_monthly, 2) }}</td></tr>
                            </tbody>
                        </table>
                        <div class="px-3 pb-3 small text-muted">ESI auto-applies (0.75% employee) jab gross ≤ ₹21,000 ho — koi manual field nahi.</div>
                        @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-cash opacity-25 fs-3 d-block mb-2"></i>
                            Daily wage staff — HRA/DA/PF structure sirf monthly payroll staff ke liye applicable hai.
                            <div class="mt-2">Daily Wage: ₹{{ number_format($staffMember->daily_wage, 2) }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-semibold mb-0">Salary Disbursements</h6>
                        <a href="{{ route('finance.salary.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-right me-1"></i> Salary Book
                        </a>
                    </div>
                    <div class="card-body p-0">
                        @if($salaryRecords->count())
                        <table class="table table-sm table-hover mb-0">
                            <thead style="background:#f8fafc;">
                                <tr>
                                    <th class="ps-3">Month</th>
                                    <th>Net Payable</th>
                                    <th>Status</th>
                                    <th>Paid On</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salaryRecords as $rec)
                                <tr>
                                    <td class="ps-3 fw-medium">{{ date('M Y', mktime(0, 0, 0, $rec->salary_month, 1, $rec->salary_year)) }}</td>
                                    <td>₹{{ number_format($rec->net_payable, 2) }}</td>
                                    <td>
                                        @php $sc = ['draft'=>'light','approved'=>'info','pending'=>'warning','paid'=>'success','reversed'=>'danger']; @endphp
                                        <span class="badge text-bg-{{ $sc[$rec->status] ?? 'secondary' }} {{ $rec->status === 'draft' ? 'border text-dark' : '' }}">{{ ucfirst($rec->status) }}</span>
                                    </td>
                                    <td class="text-muted" style="font-size:12px;">{{ $rec->payment_date?->format('d M Y') ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar3 opacity-25 fs-3 d-block mb-2"></i>No disbursements yet.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BONUSES TAB --}}
    <div class="tab-pane fade" id="tab-bonuses">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-0">Bonuses</h6>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addBonusModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Bonus
                </button>
            </div>
            <div class="card-body p-0">
                @if($staffMember->bonuses->count())
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th class="ps-4">Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Remarks</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($staffMember->bonuses as $bonus)
                        <tr>
                            <td class="ps-4"><span class="badge text-bg-warning text-dark">{{ \App\Models\StaffBonus::$types[$bonus->bonus_type] ?? ucfirst($bonus->bonus_type) }}</span></td>
                            <td class="fw-medium">₹{{ number_format($bonus->amount, 2) }}</td>
                            <td class="text-muted" style="font-size:13px;">{{ $bonus->payment_date?->format('d M Y') ?? '—' }}</td>
                            <td class="text-muted" style="font-size:13px;">{{ $bonus->remarks ?? '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('master.staff-members.bonuses.destroy', [$staffMember, $bonus]) }}" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-gift opacity-25 fs-2 d-block mb-2"></i>No bonuses recorded.
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ADVANCES TAB --}}
    <div class="tab-pane fade" id="tab-advances">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-0">Loans &amp; Advances</h6>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAdvanceModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Loan/Advance
                </button>
            </div>
            <div class="card-body p-0">
                @if($staffMember->loans->count())
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th class="ps-4">Type</th>
                            <th>Principal</th>
                            <th>Outstanding</th>
                            <th>Monthly EMI</th>
                            <th>Start</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($staffMember->loans as $loan)
                        <tr>
                            <td class="ps-4"><span class="badge {{ $loan->loan_type === 'loan' ? 'bg-primary' : 'bg-info text-dark' }}">{{ ucfirst($loan->loan_type) }}</span></td>
                            <td class="fw-medium">₹{{ number_format($loan->principal_amount, 2) }}</td>
                            <td class="{{ $loan->outstanding_amount > 0 ? 'text-danger' : 'text-success' }}">₹{{ number_format($loan->outstanding_amount, 2) }}</td>
                            <td>₹{{ number_format($loan->monthly_deduction, 2) }}</td>
                            <td class="text-muted" style="font-size:13px;">{{ str_pad($loan->start_month, 2, '0', STR_PAD_LEFT) }}/{{ $loan->start_year }}</td>
                            <td>
                                <span class="badge {{ match($loan->status) { 'active' => 'bg-success', 'completed' => 'bg-secondary', 'cancelled' => 'bg-danger', default => 'bg-secondary' } }}">{{ ucfirst($loan->status) }}</span>
                            </td>
                            <td>
                                @if($loan->status === 'active')
                                <form method="POST" action="{{ route('finance.payroll.loans.cancel', $loan) }}" onsubmit="return confirm('Yeh loan cancel karein?')">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-wallet2 opacity-25 fs-2 d-block mb-2"></i>No loans/advances recorded.
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- DOCUMENTS TAB --}}
    <div class="tab-pane fade" id="tab-docs">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-0">Documents</h6>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addDocModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Document
                </button>
            </div>
            <div class="card-body p-0">
                @if($staffMember->documents->count())
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th class="ps-4">Type</th>
                            <th>Number</th>
                            <th>Expiry</th>
                            <th>File</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($staffMember->documents as $doc)
                        <tr>
                            <td class="ps-4"><span class="badge text-bg-light text-dark border">{{ $doc->type_label }}</span></td>
                            <td style="font-size:13px;">{{ $doc->document_number ?? '—' }}</td>
                            <td>
                                @if($doc->expiry_date)
                                    <span class="{{ $doc->is_expired ? 'text-danger fw-medium' : ($doc->is_expiring_soon ? 'text-warning fw-medium' : 'text-muted') }}" style="font-size:13px;">
                                        {{ $doc->expiry_date->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($doc->file_path)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-download"></i>
                                    </a>
                                @else
                                    <span class="text-muted" style="font-size:12px;">No file</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('master.staff-members.documents.destroy', [$staffMember, $doc]) }}" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-file-earmark opacity-25 fs-2 d-block mb-2"></i>No documents uploaded.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Edit Salary Structure Modal --}}
<div class="modal fade" id="editStructureModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('master.staff-members.salary-structure', $staffMember) }}">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Edit Salary Structure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">HRA (% of Basic)</label>
                            <input type="number" class="form-control" name="hra_percent" min="0" max="100" value="{{ $staffMember->hra_percent }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">DA (% of Basic)</label>
                            <input type="number" class="form-control" name="da_percent" min="0" max="100" value="{{ $staffMember->da_percent }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">TA (₹/month)</label>
                            <input type="number" class="form-control" name="ta_amount" min="0" step="0.01" value="{{ $staffMember->ta_amount }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Medical (₹/month)</label>
                            <input type="number" class="form-control" name="medical_amount" min="0" step="0.01" value="{{ $staffMember->medical_amount }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">TDS (₹/month)</label>
                            <input type="number" class="form-control" name="tds_monthly" min="0" step="0.01" value="{{ $staffMember->tds_monthly }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Professional Tax (₹/month)</label>
                            <input type="number" class="form-control" name="professional_tax_monthly" min="0" step="0.01" value="{{ $staffMember->professional_tax_monthly }}">
                        </div>
                        <div class="col-12 form-check ps-4 pt-2">
                            <input type="checkbox" class="form-check-input" name="pf_applicable" id="pfApplicable" value="1" {{ $staffMember->pf_applicable ? 'checked' : '' }}>
                            <label class="form-check-label" for="pfApplicable">PF Applicable (12% employee + 12% employer, capped at ₹15,000 basic)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Bonus Modal --}}
<div class="modal fade" id="addBonusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('master.staff-members.bonuses.store', $staffMember) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Add Bonus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Bonus Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="bonus_type" required>
                                @foreach(\App\Models\StaffBonus::$types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Advance Modal --}}
<div class="modal fade" id="addAdvanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('finance.payroll.loans.store') }}">
                @csrf
                <input type="hidden" name="staff_member_id" value="{{ $staffMember->id }}">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Add Loan / Advance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Type <span class="text-danger">*</span></label>
                            <select name="loan_type" class="form-select" required>
                                <option value="advance">Salary Advance</option>
                                <option value="loan">Loan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Principal Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="principal_amount" class="form-control" min="1" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Monthly EMI (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="monthly_deduction" class="form-control" min="1" step="0.01" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Start Month <span class="text-danger">*</span></label>
                            <select name="start_month" class="form-select" required>
                                @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
                                    <option value="{{ $i + 1 }}" @selected(now()->month == $i + 1)>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Start Year <span class="text-danger">*</span></label>
                            <input type="number" name="start_year" class="form-control" value="{{ now()->year }}" min="2020" max="2100" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Purpose</label>
                            <input type="text" name="purpose" class="form-control" maxlength="255" placeholder="e.g., Medical emergency">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Document Modal --}}
<div class="modal fade" id="addDocModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('master.staff-members.documents.store', $staffMember) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Add Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Document Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="document_type" required>
                                @foreach(\App\Models\StaffDocument::$types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Document Number</label>
                            <input type="text" class="form-control" name="document_number" placeholder="Aadhaar / PAN / DL No.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Expiry Date</label>
                            <input type="date" class="form-control" name="expiry_date">
                            <div class="form-text">Required for driving license, passport</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Upload File</label>
                            <input type="file" class="form-control" name="file">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Notes</label>
                            <input type="text" class="form-control" name="notes">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
