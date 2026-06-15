@extends('institute.layout')
@section('title', 'Contra Entries')
@section('breadcrumb', 'Finance / Wallet / Contra Entries')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-arrow-left-right me-2 text-info"></i>Contra Entries</h4>
        <small class="text-muted">Cash se bank mein deposit ka record — wallet balance affect nahi hota</small>
    </div>
    <a href="{{ route('finance.wallet.ledger') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Ledger
    </a>
</div>

<div class="row g-4">
    {{-- Left: Add Form --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold py-3">
                <i class="bi bi-plus-circle me-2 text-info"></i>New Contra Entry
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success py-2 small">{{ session('success') }}</div>
                @endif
                <form method="POST" action="{{ route('finance.wallet.contra.store') }}">
                    @csrf
                    <input type="hidden" name="session_id" value="{{ $sessionId }}">

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Date <span class="text-danger">*</span></label>
                        <input type="date" name="entry_date" class="form-control form-control-sm @error('entry_date') is-invalid @enderror"
                               value="{{ old('entry_date', now()->toDateString()) }}" required>
                        @error('entry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control form-control-sm @error('amount') is-invalid @enderror"
                               placeholder="0.00" step="0.01" min="1" value="{{ old('amount') }}" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Deposit to Bank <span class="text-danger">*</span></label>
                        <select name="to_bank_account_id" class="form-select form-select-sm @error('to_bank_account_id') is-invalid @enderror" required>
                            <option value="">-- Bank Select Karo --</option>
                            @foreach($bankAccounts as $ba)
                                <option value="{{ $ba->id }}" {{ old('to_bank_account_id') == $ba->id ? 'selected' : '' }}>
                                    {{ $ba->account_name ?? $ba->bank_name }} — {{ $ba->account_no }}
                                </option>
                            @endforeach
                        </select>
                        @error('to_bank_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Deposit Slip No.</label>
                        <input type="text" name="slip_no" class="form-control form-control-sm"
                               placeholder="e.g. DS/2026/001" value="{{ old('slip_no') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Description</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2"
                                  placeholder="Cash deposit details...">{{ old('description') }}</textarea>
                    </div>

                    <div class="alert alert-info py-2 small border-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Ye entry sirf <strong>record</strong> ke liye hai. Institute wallet ka balance change nahi hoga.
                    </div>

                    <button type="submit" class="btn btn-info btn-sm w-100 text-white">
                        <i class="bi bi-save me-1"></i> Save Contra Entry
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: List --}}
    <div class="col-md-8">
        <form method="GET" class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Session</label>
                        <select name="session_id" class="form-select form-select-sm">
                            @foreach($sessions as $s)
                                <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}{{ $s->is_active ? ' (Active)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">From</label>
                        <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">To</label>
                        <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </div>
        </form>

        {{-- Summary --}}
        @if($entries->isNotEmpty())
        <div class="alert alert-info border-0 shadow-sm py-2 small mb-3">
            <i class="bi bi-bank me-1"></i>
            Total cash deposited to bank: <strong>₹{{ number_format($totalAmount, 2) }}</strong>
        </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle small">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Deposited To</th>
                            <th>Slip No.</th>
                            <th>Description</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $e)
                        <tr>
                            <td class="text-muted">{{ ($entries->currentPage()-1)*$entries->perPage()+$loop->iteration }}</td>
                            <td class="text-nowrap">{{ $e->entry_date->format('d-m-Y') }}</td>
                            <td class="fw-semibold text-info">₹{{ number_format($e->amount, 2) }}</td>
                            <td>
                                <i class="bi bi-bank me-1 text-primary"></i>
                                {{ $e->bankAccount?->account_name ?? $e->bankAccount?->bank_name ?? '-' }}
                                <div class="text-muted" style="font-size:10px">{{ $e->bankAccount?->account_no }}</div>
                            </td>
                            <td class="text-muted">{{ $e->slip_no ?? '-' }}</td>
                            <td class="text-muted" style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                title="{{ $e->description }}">
                                {{ $e->description ?? '-' }}
                            </td>
                            <td>
                                <form method="POST"
                                      action="{{ route('finance.wallet.contra.destroy', $e) }}"
                                      onsubmit="return confirm('Is entry ko delete karo?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm py-0 px-2">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Koi contra entry nahi mili.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($entries->hasPages())
            <div class="card-footer bg-white d-flex justify-content-between py-2">
                <small class="text-muted">{{ $entries->firstItem() }}–{{ $entries->lastItem() }} of {{ $entries->total() }}</small>
                {{ $entries->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
