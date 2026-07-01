@extends('institute.layout')
@section('title', $employee->name)
@section('breadcrumb', 'Employees / ' . $employee->name)

@section('content')
{{-- Header --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center gap-4">
            @if($employee->photo)
                <img src="{{ Storage::url($employee->photo) }}" class="rounded-circle" width="80" height="80" style="object-fit:cover;">
            @else
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width:80px;height:80px;font-size:28px;">
                    {{ strtoupper(substr($employee->name, 0, 1)) }}
                </div>
            @endif
            <div class="flex-grow-1">
                <h5 class="fw-bold mb-1">{{ $employee->name }}</h5>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @if($employee->employee_code)
                        <span class="badge text-bg-light text-dark border">{{ $employee->employee_code }}</span>
                    @endif
                    @if($employee->department)
                        <span class="badge text-bg-light text-dark border">{{ $employee->department->name }}</span>
                    @endif
                    @if($employee->designation)
                        <span class="badge text-bg-light text-dark border">{{ $employee->designation->name }}</span>
                    @endif
                    @php $sc = ['active'=>'success','inactive'=>'secondary','terminated'=>'danger','resigned'=>'warning']; @endphp
                    <span class="badge text-bg-{{ $sc[$employee->status] ?? 'secondary' }}">{{ ucfirst($employee->status) }}</span>
                </div>
                <div class="text-muted mt-1" style="font-size:13px;">
                    Joined: {{ $employee->joining_date?->format('d M Y') ?? '—' }} &nbsp;|&nbsp;
                    {{ str_replace('_',' ', ucfirst($employee->employment_type)) }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-primary btn-sm px-3">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                <a href="{{ route('employees.index') }}" class="btn btn-light btn-sm px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Document expiry alerts --}}
@if($employee->expiringDocuments(30)->count())
<div class="alert alert-warning py-2 mb-4">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Document Alert:</strong>
    @foreach($employee->expiringDocuments(30) as $doc)
        {{ $doc->type_label }} expires on {{ $doc->expiry_date->format('d M Y') }}{{ !$loop->last ? ', ' : '.' }}
    @endforeach
</div>
@endif

{{-- Tabs --}}
<ul class="nav nav-tabs mb-4" id="empTabs">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#tab-info">
            <i class="bi bi-info-circle me-1"></i> Info
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-salary">
            <i class="bi bi-cash-stack me-1"></i> Salary
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-bonuses">
            <i class="bi bi-gift me-1"></i> Bonuses
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-advances">
            <i class="bi bi-wallet2 me-1"></i> Advances
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-docs">
            <i class="bi bi-file-earmark me-1"></i> Documents
        </a>
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
                            <tr><td class="text-muted" width="40%">Father's Name</td><td>{{ $employee->father_name ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Date of Birth</td><td>{{ $employee->dob?->format('d M Y') ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Gender</td><td>{{ ucfirst($employee->gender ?? '—') }}</td></tr>
                            <tr><td class="text-muted">Blood Group</td><td>{{ $employee->blood_group ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Phone</td><td>{{ $employee->phone ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Alt. Phone</td><td>{{ $employee->alternate_phone ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Email</td><td>{{ $employee->email ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Address</td><td>{{ implode(', ', array_filter([$employee->address, $employee->city, $employee->state, $employee->pincode])) ?: '—' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Employment</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" width="40%">Department</td><td>{{ $employee->department?->name ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Designation</td><td>{{ $employee->designation?->name ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Type</td><td>{{ str_replace('_', ' ', ucfirst($employee->employment_type)) }}</td></tr>
                            <tr><td class="text-muted">Salary Type</td><td>{{ ucfirst(str_replace('_', ' ', $employee->salary_type)) }}</td></tr>
                            <tr><td class="text-muted">Basic Salary</td><td>₹{{ number_format($employee->basic_salary, 2) }}</td></tr>
                            <tr><td class="text-muted">Joined</td><td>{{ $employee->joining_date?->format('d M Y') ?? '—' }}</td></tr>
                            <tr><td class="text-muted">Status</td><td><span class="badge text-bg-{{ $sc[$employee->status] ?? 'secondary' }}">{{ ucfirst($employee->status) }}</span></td></tr>
                        </table>
                        @if($employee->notes)
                            <hr>
                            <p class="text-muted mb-0" style="font-size:13px;">{{ $employee->notes }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SALARY TAB --}}
    <div class="tab-pane fade" id="tab-salary">
        <div class="row g-4">
            {{-- Salary Components --}}
            <div class="col-md-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-semibold mb-0">Salary Components</h6>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addComponentModal">
                            <i class="bi bi-plus-lg"></i> Add
                        </button>
                    </div>
                    <div class="card-body p-0">
                        @php
                            $totalComp = $employee->salaryComponents->sum('amount');
                        @endphp
                        @if($employee->salaryComponents->count())
                        <table class="table table-sm table-hover mb-0">
                            <thead style="background:#f8fafc;">
                                <tr>
                                    <th class="ps-3">Component</th>
                                    <th>Amount</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employee->salaryComponents as $comp)
                                <tr>
                                    <td class="ps-3">
                                        <div style="font-size:13px;">{{ $comp->display_label }}</div>
                                        @if($comp->effective_to)
                                            <div class="text-danger" style="font-size:11px;">Till {{ \Carbon\Carbon::parse($comp->effective_to)->format('d M Y') }}</div>
                                        @endif
                                    </td>
                                    <td class="fw-medium">₹{{ number_format($comp->amount, 2) }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('employees.salary.destroyComponent', [$employee, $comp]) }}" onsubmit="return confirm('Remove?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                                <tr class="table-light fw-bold">
                                    <td class="ps-3">Total CTC</td>
                                    <td>₹{{ number_format($employee->basic_salary + $totalComp, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-cash opacity-25 fs-3 d-block mb-2"></i>No components added.
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Disbursements --}}
            <div class="col-md-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-semibold mb-0">Salary Disbursements</h6>
                        <a href="{{ route('employees.salary.disbursements', $employee) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-right me-1"></i> Manage
                        </a>
                    </div>
                    <div class="card-body p-0">
                        @if($employee->disbursements->count())
                        <table class="table table-sm table-hover mb-0">
                            <thead style="background:#f8fafc;">
                                <tr>
                                    <th class="ps-3">Month</th>
                                    <th>Net Paid</th>
                                    <th>Paid On</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employee->disbursements->take(6) as $dis)
                                <tr>
                                    <td class="ps-3 fw-medium">{{ $dis->month_name }} {{ $dis->year }}</td>
                                    <td>₹{{ number_format($dis->net_salary, 2) }}</td>
                                    <td class="text-muted" style="font-size:12px;">{{ $dis->payment_date?->format('d M Y') ?? '—' }}</td>
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
                @if($employee->bonuses->count())
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th class="ps-4">Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Notes</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employee->bonuses as $bonus)
                        <tr>
                            <td class="ps-4">
                                <span class="badge text-bg-warning text-dark">{{ ucfirst($bonus->bonus_type) }}</span>
                            </td>
                            <td class="fw-medium">₹{{ number_format($bonus->amount, 2) }}</td>
                            <td class="text-muted" style="font-size:13px;">{{ $bonus->payment_date?->format('d M Y') ?? '—' }}</td>
                            <td class="text-muted" style="font-size:13px;">{{ $bonus->remarks ?? '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('employees.salary.destroyBonus', [$employee, $bonus]) }}" onsubmit="return confirm('Delete?')">
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
                <h6 class="fw-semibold mb-0">Salary Advances</h6>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAdvanceModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Advance
                </button>
            </div>
            <div class="card-body p-0">
                @if($employee->advances->count())
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Amount</th>
                            <th>Recovered</th>
                            <th>Pending</th>
                            <th>Notes</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employee->advances as $adv)
                        <tr>
                            <td class="ps-4" style="font-size:13px;">{{ $adv->given_date?->format('d M Y') ?? '—' }}</td>
                            <td class="fw-medium">₹{{ number_format($adv->amount, 2) }}</td>
                            <td class="text-success">₹{{ number_format($adv->recovered_amount, 2) }}</td>
                            <td class="{{ $adv->pending_amount > 0 ? 'text-danger fw-medium' : 'text-success' }}">
                                ₹{{ number_format($adv->pending_amount, 2) }}
                            </td>
                            <td class="text-muted" style="font-size:12px;">{{ $adv->remarks ?? '—' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary"
                                    onclick="openRecovery({{ $adv->id }}, {{ $adv->recovered_amount }})">
                                    <i class="bi bi-arrow-return-right"></i> Recovery
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-wallet2 opacity-25 fs-2 d-block mb-2"></i>No advances recorded.
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
                @if($employee->documents->count())
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
                        @foreach($employee->documents as $doc)
                        <tr>
                            <td class="ps-4">
                                <span class="badge text-bg-light text-dark border">{{ $doc->type_label }}</span>
                            </td>
                            <td style="font-size:13px;">{{ $doc->document_number ?? '—' }}</td>
                            <td>
                                @if($doc->expiry_date)
                                    <span class="{{ $doc->is_expired ? 'text-danger fw-medium' : ($doc->is_expiring_soon ? 'text-warning fw-medium' : 'text-muted') }}" style="font-size:13px;">
                                        {{ $doc->expiry_date->format('d M Y') }}
                                        @if($doc->is_expired) <i class="bi bi-exclamation-triangle-fill ms-1"></i>
                                        @elseif($doc->is_expiring_soon) <i class="bi bi-clock-history ms-1"></i>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($doc->file_path)
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-download"></i>
                                    </a>
                                @else
                                    <span class="text-muted" style="font-size:12px;">No file</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('employees.documents.destroy', [$employee, $doc]) }}" onsubmit="return confirm('Delete?')">
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

{{-- Add Salary Component Modal --}}
<div class="modal fade" id="addComponentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.salary.storeComponent', $employee) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Add Salary Component</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="component_type" required>
                                @foreach(\App\Models\EmployeeSalaryComponent::$types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Custom Label</label>
                            <input type="text" class="form-control" name="label" placeholder="Leave blank to use type name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Effective From <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="effective_from" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Effective To (optional)</label>
                            <input type="date" class="form-control" name="effective_to">
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
            <form method="POST" action="{{ route('employees.salary.storeBonus', $employee) }}">
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
                                @foreach(\App\Models\EmployeeBonus::$types as $key => $label)
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
            <form method="POST" action="{{ route('employees.salary.storeAdvance', $employee) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Add Advance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="given_date" value="{{ date('Y-m-d') }}" required>
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

{{-- Recovery Modal --}}
<div class="modal fade" id="recoveryModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" id="recoveryForm">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Update Recovery</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-medium">Total Recovered (₹)</label>
                    <input type="number" class="form-control" name="recovered_amount" id="recoveredInput" min="0" step="0.01" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Document Modal --}}
<div class="modal fade" id="addDocModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.documents.store', $employee) }}" enctype="multipart/form-data">
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
                                @foreach(\App\Models\EmployeeDocument::$types as $key => $label)
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

<script>
function openRecovery(advId, recovered) {
    document.getElementById('recoveryForm').action = `/employees/{{ $employee->id }}/advances/${advId}/recovery`;
    document.getElementById('recoveredInput').value = recovered;
    new bootstrap.Modal(document.getElementById('recoveryModal')).show();
}
</script>
@endsection
