@extends('staff.layout')
@section('title','Fee Collections')
@section('breadcrumb','Fee / Collections')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Fee Collections</h4>
        <small class="text-muted">Aaj ka total: <strong class="text-success">₹ {{ number_format($todayTotal) }}</strong></small>
    </div>
    <a href="{{ route('staff.fee.create') }}" class="btn btn-success btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Collect Fee
    </a>
</div>

{{-- Filter --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Date Filter</label>
                <input type="date" name="date" class="form-control form-control-sm"
                       value="{{ request('date', today()->toDateString()) }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Invoice No</th>
                    <th>Student</th>
                    <th>Date</th>
                    <th class="text-center">Sem</th>
                    <th class="text-center">Mode</th>
                    <th class="text-end pe-3">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                <tr>
                    <td class="ps-3">
                        <span class="small fw-semibold text-primary">{{ $inv->invoice_no }}</span>
                        @if($inv->is_cancelled)
                            <span class="badge bg-danger ms-1" style="font-size:9px;">Cancelled</span>
                        @endif
                    </td>
                    <td class="small">
                        <div class="fw-semibold">{{ $inv->student?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $inv->student?->student_uid }}</div>
                    </td>
                    <td class="small text-muted">{{ $inv->payment_date?->format('d M Y') }}</td>
                    <td class="text-center">
                        @if($inv->semester)
                            <span class="badge bg-primary bg-opacity-10 text-primary border" style="font-size:10px;">S{{ $inv->semester }}</span>
                        @else <span class="text-muted">—</span> @endif
                    </td>
                    <td class="text-center">
                        @php $modeColors = ['cash'=>'success','upi'=>'primary','online'=>'info','cheque'=>'warning','dd'=>'secondary','neft'=>'warning','rtgs'=>'danger']; @endphp
                        <span class="badge bg-{{ $modeColors[$inv->payment_mode] ?? 'secondary' }}">
                            {{ strtoupper($inv->payment_mode) }}
                        </span>
                    </td>
                    <td class="text-end pe-3 fw-semibold small {{ $inv->is_cancelled ? 'text-muted text-decoration-line-through' : 'text-success' }}">
                        ₹ {{ number_format($inv->paid_amount) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-inbox d-block fs-3 mb-2"></i>Is date ka koi record nahi
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
    <div class="card-footer bg-white border-top py-2">{{ $invoices->links() }}</div>
    @endif
</div>
@endsection