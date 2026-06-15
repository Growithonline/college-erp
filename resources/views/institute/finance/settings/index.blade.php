@extends('institute.layout')
@section('title', 'Finance Settings')
@section('breadcrumb', 'Finance / Settings')

@section('content')
@php
    $accountLabel = fn($account) => $account->code . ' - ' . $account->name . ' (' . ucfirst($account->type) . ')';
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-sliders me-2 text-primary"></i>Finance Settings</h4>
        <small class="text-muted">Core accounting mappings, fee income heads aur bank GL accounts yahin se control karo</small>
    </div>
    <a href="{{ route('finance.expenses.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-right-circle me-1"></i> Expense Book
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Chart Accounts</div>
                <div class="fw-bold fs-4">{{ number_format($accounts->count()) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Mapped Fee Types</div>
                <div class="fw-bold fs-4 text-success">{{ number_format($mappedFeeTypes) }} / {{ number_format($feeTypes->count()) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Mapped Bank Accounts</div>
                <div class="fw-bold fs-4 text-primary">{{ number_format($mappedBankAccounts) }} / {{ number_format($bankAccounts->count()) }}</div>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('finance.settings.update') }}">
    @csrf

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-semibold">Core Account Mapping</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Fees Receivable</label>
                    <select name="fees_receivable_account_id" class="form-select">
                        <option value="">Select account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ (string) old('fees_receivable_account_id', $settings->fees_receivable_account_id) === (string) $account->id ? 'selected' : '' }}>
                                {{ $accountLabel($account) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Student Advance</label>
                    <select name="student_advance_account_id" class="form-select">
                        <option value="">Select account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ (string) old('student_advance_account_id', $settings->student_advance_account_id) === (string) $account->id ? 'selected' : '' }}>
                                {{ $accountLabel($account) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Discount Allowed</label>
                    <select name="discount_allowed_account_id" class="form-select">
                        <option value="">Select account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ (string) old('discount_allowed_account_id', $settings->discount_allowed_account_id) === (string) $account->id ? 'selected' : '' }}>
                                {{ $accountLabel($account) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Cash Account</label>
                    <select name="cash_account_id" class="form-select">
                        <option value="">Select account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ (string) old('cash_account_id', $settings->cash_account_id) === (string) $account->id ? 'selected' : '' }}>
                                {{ $accountLabel($account) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Fine Income</label>
                    <select name="fine_income_account_id" class="form-select">
                        <option value="">Select account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ (string) old('fine_income_account_id', $settings->fine_income_account_id) === (string) $account->id ? 'selected' : '' }}>
                                {{ $accountLabel($account) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Rounding / Other Adjustment</label>
                    <select name="rounding_adjustment_account_id" class="form-select">
                        <option value="">Select account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ (string) old('rounding_adjustment_account_id', $settings->rounding_adjustment_account_id) === (string) $account->id ? 'selected' : '' }}>
                                {{ $accountLabel($account) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-semibold">Fee Type To Income Account Mapping</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Fee Type</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th class="pe-3">Income Account</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feeTypes as $feeType)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold">{{ $feeType->name }}</div>
                            <div class="small text-muted">{{ $feeType->description ?: 'No description' }}</div>
                        </td>
                        <td><span class="badge bg-info-subtle text-info border border-info-subtle">{{ $feeType->category }}</span></td>
                        <td>
                            @if($feeType->income_account_id)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Mapped</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                            @endif
                        </td>
                        <td class="pe-3">
                            <select name="fee_type_accounts[{{ $feeType->id }}]" class="form-select form-select-sm">
                                <option value="">Select income account</option>
                                @foreach($incomeAccounts as $account)
                                    <option value="{{ $account->id }}" {{ (string) old("fee_type_accounts.{$feeType->id}", $feeType->income_account_id) === (string) $account->id ? 'selected' : '' }}>
                                        {{ $accountLabel($account) }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No fee types found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-semibold">Bank Account To GL Mapping</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Bank Account</th>
                        <th>Modes</th>
                        <th>Status</th>
                        <th class="pe-3">GL Account</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bankAccounts as $bankAccount)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold">{{ $bankAccount->display_label ?: $bankAccount->bank_name }}</div>
                            <div class="small text-muted">{{ $bankAccount->account_no }}</div>
                        </td>
                        <td class="small text-muted">{{ $bankAccount->allowed_payment_modes ?: 'cash' }}</td>
                        <td>
                            @if($bankAccount->gl_account_id)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Mapped</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                            @endif
                        </td>
                        <td class="pe-3">
                            <select name="bank_account_accounts[{{ $bankAccount->id }}]" class="form-select form-select-sm">
                                <option value="">Select asset / bank account</option>
                                @foreach($assetAccounts as $account)
                                    <option value="{{ $account->id }}" {{ (string) old("bank_account_accounts.{$bankAccount->id}", $bankAccount->gl_account_id) === (string) $account->id ? 'selected' : '' }}>
                                        {{ $accountLabel($account) }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No bank accounts found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1"></i> Save Finance Settings
        </button>
        <a href="{{ route('institute.dashboard') }}" class="btn btn-outline-secondary px-4">Back</a>
    </div>
</form>
@endsection
