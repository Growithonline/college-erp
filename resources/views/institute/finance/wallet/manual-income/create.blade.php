@extends('institute.layout')
@section('title', 'Add Manual Income')
@section('breadcrumb', 'Finance / Wallet / Manual Income / Add')

@section('content')
<div class="mb-4">
    <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-success"></i>Add Manual Income</h4>
    <small class="text-muted">Ye income wallet me automatically credit ho jayegi</small>
</div>

@if($categories->isEmpty())
<div class="alert alert-warning">
    Pehle <a href="{{ route('finance.wallet.income-categories.create') }}">income categories create karo</a>, phir income add karo.
</div>
@endif

<div class="card border-0 shadow-sm" style="max-width: 550px">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('finance.wallet.manual-income.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Session <span class="text-danger">*</span></label>
                <select name="academic_session_id" class="form-select @error('academic_session_id') is-invalid @enderror">
                    <option value="">-- Select Session --</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}"
                            {{ (old('academic_session_id', $activeSessionId) == $s->id) ? 'selected' : '' }}>
                            {{ $s->name }} {{ $s->is_active ? '(Active)' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('academic_session_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Income Category <span class="text-danger">*</span></label>
                <select name="income_category_id" class="form-select @error('income_category_id') is-invalid @enderror">
                    <option value="">-- Select Category --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('income_category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @error('income_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Amount (Rs) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" step="0.01" min="0.01"
                           class="form-control @error('amount') is-invalid @enderror"
                           value="{{ old('amount') }}" placeholder="0.00">
                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date"
                           class="form-control @error('date') is-invalid @enderror"
                           value="{{ old('date', now()->toDateString()) }}">
                    @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Receipt No</label>
                <input type="text" name="receipt_no" class="form-control"
                       value="{{ old('receipt_no') }}" placeholder="Optional">
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="2"
                          placeholder="Optional note">{{ old('description') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success" {{ $categories->isEmpty() ? 'disabled' : '' }}>
                    <i class="bi bi-check-lg me-1"></i> Add & Credit Wallet
                </button>
                <a href="{{ route('finance.wallet.manual-income.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
