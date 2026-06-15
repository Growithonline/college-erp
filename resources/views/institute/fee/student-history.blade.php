@php
    $isStaff = auth()->guard('staff')->check();
    $layout          = $isStaff ? 'staff.layout'              : 'institute.layout';
    $feeCreateRoute  = $isStaff ? 'staff.fee.create'          : 'fee.create';
    $showRoute       = $isStaff ? 'staff.admissions.show'     : 'admissions.show';
    $feeIndexRoute   = $isStaff ? 'staff.fee.index'           : 'fee.index';
    $receiptRoute    = $isStaff ? 'staff.fee.receipt'         : 'fee.receipt';
    $cancelRoute     = $isStaff ? 'staff.fee.cancel'          : 'fee.cancel';
    $walletRoute     = $isStaff ? 'staff.fee.wallet.student'  : 'fee.wallet.student';
    $canCollectFee   = !$isStaff || auth()->guard('staff')->user()?->canCollectFee();
    $canCancelFee    = !$isStaff || auth()->guard('staff')->user()?->canCancelFee();
    $canViewFeeWallet = !$isStaff || auth()->guard('staff')->user()?->canViewFeeWallet();
@endphp
@extends($layout)
@section('title','Fee History')
@section('breadcrumb','Fee / Student History')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Fee History</h4>
        <small class="text-muted">{{ $student->name }} — {{ $student->student_uid }}</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($canCollectFee)
        <a href="{{ route($feeCreateRoute, ['student_id' => $student->id]) }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus me-1"></i> Collect Fee
        </a>
        @endif
        @if($canViewFeeWallet)
        <a href="{{ route($walletRoute, $student) }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-wallet2 me-1"></i> Wallet
        </a>
        @endif
        <a href="{{ route($showRoute, $student) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-person me-1"></i> Student Profile
        </a>
        <a href="{{ route($feeIndexRoute) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

{{-- Student Card --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                     style="width:48px;height:48px;">
                    <i class="bi bi-person-fill text-primary fs-5"></i>
                </div>
            </div>
            <div class="col">
                <div class="fw-bold">{{ $student->name }}</div>
                <div class="small text-muted">
                    {{ $student->student_uid }} •
                    {{ $student->stream->course->name ?? '—' }} {{ $student->stream->name ?? '' }} •
                    <i class="bi bi-phone-fill"></i> {{ $student->mobile }}
                </div>
            </div>
            <div class="col-auto text-end">
                <div class="small text-muted">Total Paid</div>
                <div class="fw-bold fs-5 text-success">₹ {{ number_format($totalPaid) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Column Toggle + Invoices --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">
            <i class="bi bi-receipt me-2 text-primary"></i>All Receipts ({{ $invoices->count() }})
        </h6>
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-layout-three-columns me-1"></i>Columns
            </button>
            <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width:210px;" onclick="event.stopPropagation()">
                @foreach([
                    'hcol_invoice' => 'Invoice No & Time',
                    'hcol_date'    => 'Date',
                    'hcol_sem'     => 'Semester',
                    'hcol_items'   => 'Fee Items',
                    'hcol_mode'    => 'Mode',
                    'hcol_ref'     => 'Ref No',
                    'hcol_by'      => 'Collected By',
                    'hcol_collect' => 'Collection',
                    'hcol_fine'    => 'Fine',
                    'hcol_disc'    => 'Discount',
                    'hcol_total'   => 'Total Amt',
                ] as $col => $label)
                <li>
                    <label class="dropdown-item d-flex align-items-center gap-2 small py-1" style="cursor:pointer;">
                        <input type="checkbox" class="hcol-toggle" data-col="{{ $col }}"
                               onchange="toggleHCol('{{ $col }}', this.checked)"
                               {{ in_array($col, ['hcol_invoice','hcol_date','hcol_sem','hcol_items','hcol_mode','hcol_collect','hcol_total']) ? 'checked' : '' }}>
                        {{ $label }}
                    </label>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 hcol_invoice">Invoice No</th>
                        <th class="hcol_date">Date</th>
                        <th class="hcol_sem text-center">Sem</th>
                        <th class="hcol_items">Fee Items</th>
                        <th class="hcol_mode">Mode</th>
                        <th class="hcol_ref" style="display:none;">Ref No</th>
                        <th class="hcol_by" style="display:none;">Collected By</th>
                        <th class="hcol_collect text-end">Collection</th>
                        <th class="hcol_fine text-end" style="display:none;">Fine</th>
                        <th class="hcol_disc text-end" style="display:none;">Discount</th>
                        <th class="hcol_total text-end">Total Amt</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    @php
                        $modeColors = ['cash'=>'success','upi'=>'primary','online'=>'info','cheque'=>'warning','dd'=>'secondary'];
                        $color = $modeColors[$inv->payment_mode] ?? 'secondary';
                    @endphp
                    <tr class="{{ $inv->is_cancelled ? 'table-danger opacity-75' : '' }}">
                        <td class="ps-3 hcol_invoice">
                            <span class="badge bg-light text-dark border">{{ $inv->invoice_no }}</span>
                            <div class="text-muted" style="font-size:10px;">
                                {{ $inv->created_at?->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                            </div>
                            @if($inv->is_cancelled)
                                <span class="badge bg-danger" style="font-size:9px;">Cancelled</span>
                            @endif
                        </td>
                        <td class="hcol_date small">{{ $inv->payment_date->format('d M Y') }}</td>
                        <td class="hcol_sem text-center">
                            @if($inv->semester)
                                <span class="badge bg-primary bg-opacity-10 text-primary border" style="font-size:10px;">S{{ $inv->semester }}</span>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td class="hcol_items small">
                            @foreach($inv->items as $item)
                                <span class="badge bg-light text-dark border me-1">{{ $item->fee_name }}</span>
                            @endforeach
                        </td>
                        <td class="hcol_mode">
                            <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} border border-{{ $color }}">
                                {{ strtoupper($inv->payment_mode) }}
                            </span>
                        </td>
                        <td class="hcol_ref small text-muted" style="display:none;">{{ $inv->transaction_ref ?? '—' }}</td>
                        <td class="hcol_by small" style="display:none;">{{ $inv->collected_by ?? '—' }}</td>
                        <td class="hcol_collect text-end fw-bold text-success">₹ {{ number_format($inv->paid_amount) }}</td>
                        <td class="hcol_fine text-end small" style="display:none;">
                            @php $invFine = $inv->items->sum('fine'); @endphp
                            @if($invFine > 0)
                                <span class="text-warning fw-semibold">+₹{{ number_format($invFine) }}</span>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td class="hcol_disc text-end small" style="display:none;">
                            @if(($inv->discount ?? 0) > 0)
                                <span class="text-danger fw-semibold">-₹{{ number_format($inv->discount) }}</span>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td class="hcol_total text-end fw-bold">₹ {{ number_format($inv->paid_amount + ($inv->discount ?? 0)) }}</td>
                        <td class="text-center">
                            <a href="{{ route($receiptRoute, [$student->id, $inv->id]) }}"
                               class="btn btn-sm btn-outline-primary" target="_blank" title="Print Receipt">
                                <i class="bi bi-printer"></i>
                            </a>
                            @if(!$inv->is_cancelled && $canCancelFee)
                            <button type="button" class="btn btn-sm btn-outline-danger ms-1"
                                    onclick="showCancelModal('{{ $inv->invoice_no }}', '{{ route($cancelRoute, [$student->id, $inv->id]) }}')">
                                <i class="bi bi-x-circle"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt fs-2 d-block mb-2"></i>Koi fee collection nahi mili
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($invoices->isNotEmpty())
                <tfoot class="table-light">
                    <tr>
                        <td colspan="7" class="text-end fw-bold ps-3">Total Collected:</td>
                        <td class="text-end fw-bold text-success">₹ {{ number_format($totalPaid) }}</td>
                        <td class="hcol_fine text-end fw-bold text-warning" style="display:none;">
                            +₹ {{ number_format($invoices->sum(fn($i) => $i->items->sum('fine'))) }}
                        </td>
                        <td class="hcol_disc text-end" style="display:none;"></td>
                        <td class="text-end fw-bold">₹ {{ number_format($invoices->sum(fn($i) => $i->paid_amount + ($i->discount ?? 0))) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- Cancel Modal --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-x-circle me-2"></i>Invoice Cancel Karo</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="cancelForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning border-0 py-2 small mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Invoice <strong id="cancelInvoiceNo"></strong> cancel hone ke baad wapas nahi ho sakta.
                        Student ka due amount automatically restore ho jaayega.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Cancel Reason <span class="text-danger">*</span></label>
                        <textarea name="cancel_reason" id="cancelReason" class="form-control" rows="3" required
                                  placeholder="Cancel karne ka reason likhein..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Wapas Jao</button>
                    <button type="submit" class="btn btn-danger btn-sm">Haan, Cancel Karo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const saved = JSON.parse(localStorage.getItem('feeHistColPrefs') || '{}');
    document.querySelectorAll('.hcol-toggle').forEach(cb => {
        const col = cb.dataset.col;
        if (saved[col] !== undefined) { cb.checked = saved[col]; toggleHCol(col, saved[col], false); }
        else { toggleHCol(col, cb.checked, false); }
    });
});
function toggleHCol(col, visible, save = true) {
    document.querySelectorAll('.' + col).forEach(el => { el.style.display = visible ? '' : 'none'; });
    if (save) {
        const saved = JSON.parse(localStorage.getItem('feeHistColPrefs') || '{}');
        saved[col] = visible;
        localStorage.setItem('feeHistColPrefs', JSON.stringify(saved));
    }
}
function showCancelModal(invoiceNo, actionUrl) {
    document.getElementById('cancelInvoiceNo').textContent = invoiceNo;
    document.getElementById('cancelForm').action = actionUrl;
    document.getElementById('cancelReason').value = '';
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>
@endsection
