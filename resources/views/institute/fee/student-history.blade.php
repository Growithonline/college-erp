@php
    $isStaff   = auth()->guard('staff')->check();
    $isCenter  = auth()->guard('center')->check();
    $isPartner = auth()->guard('partner')->check();
    $layout          = $isStaff ? 'staff.layout'    : ($isCenter  ? 'center.layout'            : ($isPartner ? 'partner.layout'            : 'institute.layout'));
    $feeCreateRoute  = $isStaff ? 'staff.fee.create'           : ($isCenter  ? 'center.fee.create'           : ($isPartner ? 'partner.fee.create'           : 'fee.create'));
    $showRoute       = $isStaff ? 'staff.admissions.show'      : ($isCenter  ? 'center.students.show'        : ($isPartner ? 'partner.students.show'        : 'admissions.show'));
    $feeIndexRoute   = $isStaff ? 'staff.fee.index'            : ($isCenter  ? 'center.fee.index'            : ($isPartner ? 'partner.fee.index'            : 'fee.index'));
    $receiptRoute    = $isStaff ? 'staff.fee.receipt'          : ($isCenter  ? 'center.fee.receipt'          : ($isPartner ? 'partner.fee.receipt'          : 'fee.receipt'));
    $cancelRoute     = $isStaff ? 'staff.fee.cancel'           : (($isCenter || $isPartner) ? null           : 'fee.cancel');
    $walletRoute     = $isStaff ? 'staff.fee.wallet.student'   : ($isCenter  ? 'center.fee.wallet.student'   : ($isPartner ? 'partner.fee.wallet.student'   : 'fee.wallet.student'));
    $canCollectFee   = $isStaff ? (bool) auth()->guard('staff')->user()?->canCollectFee()    : ($isCenter ? (bool) auth()->guard('center')->user()?->canCollectFee()  : ($isPartner ? (bool) auth()->guard('partner')->user()?->canCollectFee() : true));
    $canCancelFee    = $isStaff ? (bool) auth()->guard('staff')->user()?->canCancelFee()     : (!$isCenter && !$isPartner);
    $canViewFeeWallet = $isStaff ? (bool) auth()->guard('staff')->user()?->canViewFeeWallet() : true;

    $totalFine      = $invoices->where('is_cancelled', false)->sum(fn($i) => $i->items->sum('fine'));
    $totalDiscount  = $invoices->where('is_cancelled', false)->sum(fn($i) => (float)($i->discount ?? 0));
    $overallDue     = $sessionBalances->sum(fn($sb) => (float)$sb->main_b < 0 ? abs((float)$sb->main_b) : 0);

    // Running due per receipt: total_charged = total_paid + current_due
    $totalCharged = $totalPaid + $overallDue;
    $runningDueMap = [];
    $rd = $totalCharged;
    foreach ($invoices->where('is_cancelled', false)->sortBy(fn($i) => $i->payment_date->timestamp * 1000000 + $i->id) as $_inv) {
        $rd -= (float) $_inv->paid_amount;
        $runningDueMap[$_inv->id] = max(0, round($rd, 2));
    }
@endphp
@extends($layout)
@section('title','Fee History')
@section('breadcrumb','Fee / Student History')
@section('content')

<style>
.hist-table th {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #6b7280;
    padding: 10px 12px;
    white-space: nowrap;
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
}
.hist-table td {
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
    font-size: 13px;
}
.hist-table tbody tr:hover { background: #f8faff; }
.hist-table tbody tr.cancelled-row { background: #fff5f5; }
.hist-table tbody tr.cancelled-row:hover { background: #fee2e2; }
.hist-table tfoot td {
    padding: 11px 12px;
    background: #f9fafb;
    border-top: 2px solid #e5e7eb;
    font-size: 13px;
}
.mode-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
    letter-spacing: 0.06em;
}
.fee-item-tag {
    display: inline-block;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 4px;
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
    margin: 1px 2px 1px 0;
}
.sem-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: #eff6ff;
    color: #2563eb;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid #bfdbfe;
}
.invoice-no-tag {
    font-size: 11px;
    font-weight: 600;
    color: #1f2937;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 5px;
    padding: 2px 7px;
    font-family: 'SFMono-Regular', Consolas, monospace;
}
.hist-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid;
    font-size: 13px;
    transition: all 0.15s;
    text-decoration: none;
    cursor: pointer;
    background: transparent;
}
.hist-action-btn.print { color: #2563eb; border-color: #bfdbfe; }
.hist-action-btn.print:hover { background: #eff6ff; }
.hist-action-btn.cancel { color: #dc2626; border-color: #fca5a5; }
.hist-action-btn.cancel:hover { background: #fff1f0; }
.mono { font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', monospace; }
.student-info-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #fff 60%);
    border: 1px solid #e0f2fe;
    border-radius: 12px;
}
.stat-chip {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-end;
    min-width: 110px;
}
</style>

{{-- ── Page Header ── --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Fee History</h4>
        <small class="text-muted">{{ $student->name }} — {{ $student->student_uid }}</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($canCollectFee)
        <a href="{{ route($feeCreateRoute, ['student_id' => $student->id]) }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Collect Fee
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

{{-- ── Student Info Card ── --}}
<div class="card border-0 shadow-sm mb-4 student-info-card">
    <div class="card-body p-3">
        <div class="d-flex align-items-start gap-3">
            {{-- Avatar --}}
            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:52px;height:52px;">
                <i class="bi bi-person-fill text-primary" style="font-size:22px;"></i>
            </div>

            {{-- Student details --}}
            <div class="flex-grow-1">
                <div class="fw-bold" style="font-size:15px;color:#111827;">{{ $student->name }}</div>
                <div class="text-muted small mt-1">
                    {{ $student->student_uid }}
                    @if($student->stream?->course)
                        &nbsp;•&nbsp; {{ $student->stream->course->name }}
                        @if($student->stream->name) ({{ $student->stream->name }}) @endif
                    @endif
                    @if($student->mobile)
                        &nbsp;•&nbsp; <i class="bi bi-phone-fill"></i> {{ $student->mobile }}
                    @endif
                </div>

                {{-- Father & Mother --}}
                <div class="mt-2 d-flex flex-wrap gap-4">
                    @if($student->father_name)
                    <div>
                        <div style="font-size:10px;font-weight:600;letter-spacing:.05em;color:#9ca3af;text-transform:uppercase;margin-bottom:2px;">Father</div>
                        <div style="font-size:15px;font-weight:700;color:#111827;line-height:1.3;">{{ $student->father_name }}</div>
                        @if($student->father_mobile)
                            <div style="font-size:12px;color:#6b7280;margin-top:1px;"><i class="bi bi-phone-fill me-1" style="font-size:10px;"></i>{{ $student->father_mobile }}</div>
                        @endif
                    </div>
                    @endif
                    @if($student->mother_name)
                    @if($student->father_name)
                        <div style="width:1px;background:#e5e7eb;align-self:stretch;"></div>
                    @endif
                    <div>
                        <div style="font-size:10px;font-weight:600;letter-spacing:.05em;color:#9ca3af;text-transform:uppercase;margin-bottom:2px;">Mother</div>
                        <div style="font-size:15px;font-weight:700;color:#111827;line-height:1.3;">{{ $student->mother_name }}</div>
                        @if($student->mother_mobile)
                            <div style="font-size:12px;color:#6b7280;margin-top:1px;"><i class="bi bi-phone-fill me-1" style="font-size:10px;"></i>{{ $student->mother_mobile }}</div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            {{-- Stats --}}
            <div class="d-flex gap-3 flex-shrink-0">
                <div class="stat-chip">
                    <span class="text-muted" style="font-size:10px;letter-spacing:.04em;font-weight:600;">TOTAL PAID</span>
                    <span class="mono fw-bold text-success" style="font-size:17px;">₹ {{ number_format($totalPaid) }}</span>
                </div>
                @if($overallDue > 0)
                <div class="stat-chip">
                    <span class="text-muted" style="font-size:10px;letter-spacing:.04em;font-weight:600;">TOTAL DUE</span>
                    <span class="mono fw-bold text-danger" style="font-size:17px;">₹ {{ number_format($overallDue) }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Invoices Table ── --}}
<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
    <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center"
         style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);">
        <div class="d-flex align-items-center gap-2">
            <div style="width:30px;height:30px;border-radius:7px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-receipt text-white" style="font-size:14px;"></i>
            </div>
            <span class="text-white fw-semibold" style="font-size:14px;">All Receipts</span>
            <span style="background:rgba(255,255,255,0.2);color:#fff;font-size:11px;font-weight:600;padding:2px 10px;border-radius:20px;">
                {{ $invoices->count() }}
            </span>
        </div>
        <div class="dropdown">
            <button class="btn btn-sm" type="button" data-bs-toggle="dropdown"
                    style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3);font-size:12px;">
                <i class="bi bi-layout-three-columns me-1"></i>Columns
            </button>
            <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width:200px;" onclick="event.stopPropagation()">
                @foreach([
                    'hcol_invoice' => 'Invoice No & Time',
                    'hcol_date'    => 'Date',
                    'hcol_sem'     => 'Semester',
                    'hcol_items'   => 'Fee Items',
                    'hcol_mode'    => 'Mode',
                    'hcol_ref'     => 'Ref No',
                    'hcol_by'      => 'Collected By',
                ] as $col => $label)
                <li>
                    <label class="dropdown-item d-flex align-items-center gap-2 small py-1" style="cursor:pointer;">
                        <input type="checkbox" class="hcol-toggle" data-col="{{ $col }}"
                               onchange="toggleHCol('{{ $col }}', this.checked)"
                               {{ in_array($col, ['hcol_invoice','hcol_date','hcol_sem','hcol_items','hcol_mode','hcol_by']) ? 'checked' : '' }}>
                        {{ $label }}
                    </label>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table hist-table mb-0">
                <thead>
                    <tr>
                        <th class="hcol_invoice">Invoice No</th>
                        <th class="hcol_date">Date</th>
                        <th class="hcol_sem text-center">Sem</th>
                        <th class="hcol_items">Fee Items</th>
                        <th class="hcol_mode">Mode</th>
                        <th class="hcol_ref" style="display:none;">Ref No</th>
                        <th class="hcol_by">Collected By</th>
                        <th class="text-end" style="color:#16a34a;">Collection</th>
                        <th class="text-end" style="color:#d97706;">Fine</th>
                        <th class="text-end" style="color:#7c3aed;">Discount</th>
                        <th class="text-end" style="color:#dc2626;">Due</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    @php
                        $modeColors = [
                            'cash'   => ['bg'=>'#f0fdf4','color'=>'#15803d','border'=>'#86efac'],
                            'upi'    => ['bg'=>'#eff6ff','color'=>'#1d4ed8','border'=>'#93c5fd'],
                            'online' => ['bg'=>'#ecfeff','color'=>'#0e7490','border'=>'#67e8f9'],
                            'cheque' => ['bg'=>'#fffbeb','color'=>'#b45309','border'=>'#fcd34d'],
                            'dd'     => ['bg'=>'#f9fafb','color'=>'#374151','border'=>'#d1d5db'],
                        ];
                        $mc = $modeColors[$inv->payment_mode] ?? $modeColors['dd'];
                        $invFine = (float) $inv->items->sum('fine');
                        $invDisc = (float) ($inv->discount ?? 0);
                        // remaining_due: exact snapshot saved at collection time; fallback to running calculation
                        $invDue = $inv->is_cancelled ? null : ($inv->remaining_due !== null ? (float) $inv->remaining_due : ($runningDueMap[$inv->id] ?? null));
                    @endphp
                    <tr class="{{ $inv->is_cancelled ? 'cancelled-row' : '' }}">
                        {{-- Invoice No --}}
                        <td class="hcol_invoice">
                            <span class="invoice-no-tag">{{ $inv->invoice_no }}</span>
                            <div style="font-size:10px;color:#9ca3af;margin-top:3px;">
                                {{ $inv->created_at?->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                            </div>
                            @if($inv->is_cancelled)
                                <span style="font-size:10px;font-weight:700;color:#dc2626;background:#fee2e2;border:1px solid #fca5a5;padding:1px 7px;border-radius:4px;display:inline-block;margin-top:2px;">CANCELLED</span>
                            @endif
                        </td>

                        {{-- Date --}}
                        <td class="hcol_date">
                            <div style="font-weight:600;color:#1f2937;">{{ $inv->payment_date->format('d M') }}</div>
                            <div style="font-size:10px;color:#9ca3af;">{{ $inv->payment_date->format('Y') }}</div>
                            @if($inv->payment_datetime && $inv->payment_mode !== 'cash')
                                <div style="font-size:10px;color:#6b7280;" title="Payment received time">
                                    <i class="bi bi-clock"></i> {{ $inv->payment_datetime->setTimezone('Asia/Kolkata')->format('h:i A') }}
                                </div>
                            @endif
                        </td>

                        {{-- Sem --}}
                        <td class="hcol_sem text-center">
                            @if($inv->semester)
                                <span class="sem-badge">S{{ $inv->semester }}</span>
                            @else
                                <span style="color:#d1d5db;">—</span>
                            @endif
                        </td>

                        {{-- Fee Items --}}
                        <td class="hcol_items">
                            @foreach($inv->items as $item)
                                <span class="fee-item-tag">{{ $item->fee_name }}</span>
                            @endforeach
                        </td>

                        {{-- Mode --}}
                        <td class="hcol_mode">
                            <span class="mode-badge"
                                  style="background:{{ $mc['bg'] }};color:{{ $mc['color'] }};border:1px solid {{ $mc['border'] }};">
                                {{ strtoupper($inv->payment_mode) }}
                            </span>
                        </td>

                        {{-- Ref No --}}
                        <td class="hcol_ref" style="display:none;color:#6b7280;font-size:12px;">
                            {{ $inv->transaction_ref ?? '—' }}
                        </td>

                        {{-- Collected By --}}
                        <td class="hcol_by" style="color:#374151;">
                            {{ $inv->collected_by ?? '—' }}
                        </td>

                        {{-- Collection --}}
                        <td class="text-end">
                            <span class="mono fw-bold text-success" style="font-size:13px;">
                                ₹ {{ number_format($inv->paid_amount) }}
                            </span>
                        </td>

                        {{-- Fine --}}
                        <td class="text-end">
                            @if($invFine > 0)
                                <span class="mono fw-semibold" style="color:#d97706;">+₹ {{ number_format($invFine) }}</span>
                            @else
                                <span style="color:#d1d5db;">—</span>
                            @endif
                        </td>

                        {{-- Discount --}}
                        <td class="text-end">
                            @if($invDisc > 0)
                                <span class="mono fw-semibold" style="color:#7c3aed;">-₹ {{ number_format($invDisc) }}</span>
                            @else
                                <span style="color:#d1d5db;">—</span>
                            @endif
                        </td>

                        {{-- Due --}}
                        <td class="text-end">
                            @if($inv->is_cancelled)
                                <span style="font-size:10px;font-weight:600;color:#9ca3af;">—</span>
                            @elseif($invDue > 0)
                                <span class="mono fw-bold text-danger" style="font-size:13px;">₹ {{ number_format($invDue) }}</span>
                            @else
                                <span style="font-size:11px;font-weight:600;color:#16a34a;">
                                    <i class="bi bi-check-circle-fill"></i> Cleared
                                </span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route($receiptRoute, [$student->id, $inv->id]) }}"
                                   class="hist-action-btn print" target="_blank" title="Print Receipt">
                                    <i class="bi bi-printer"></i>
                                </a>
                                @if(!$inv->is_cancelled && $canCancelFee)
                                <button type="button" class="hist-action-btn cancel"
                                        onclick="showCancelModal('{{ $inv->invoice_no }}', '{{ route($cancelRoute, [$student->id, $inv->id]) }}')"
                                        title="Cancel Invoice">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center py-5" style="color:#9ca3af;">
                            <div style="width:52px;height:52px;border-radius:50%;background:#f3f4f6;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                                <i class="bi bi-receipt" style="font-size:22px;"></i>
                            </div>
                            <div style="font-size:14px;font-weight:500;">Koi fee collection nahi mili</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>

                @if($invoices->isNotEmpty())
                <tfoot>
                    <tr>
                        {{-- empty cells for toggleable columns --}}
                        <td class="hcol_invoice"></td>
                        <td class="hcol_date"></td>
                        <td class="hcol_sem"></td>
                        <td class="hcol_items"></td>
                        <td class="hcol_mode"></td>
                        <td class="hcol_ref" style="display:none;"></td>
                        <td class="hcol_by text-end fw-bold" style="color:#374151;letter-spacing:.04em;font-size:12px;">TOTAL</td>

                        {{-- Collection --}}
                        <td class="text-end">
                            <span class="mono fw-bold text-success" style="font-size:14px;">
                                ₹ {{ number_format($totalPaid) }}
                            </span>
                        </td>

                        {{-- Fine --}}
                        <td class="text-end">
                            @if($totalFine > 0)
                                <span class="mono fw-semibold" style="color:#d97706;font-size:13px;">
                                    +₹ {{ number_format($totalFine) }}
                                </span>
                            @else
                                <span style="color:#d1d5db;">—</span>
                            @endif
                        </td>

                        {{-- Discount --}}
                        <td class="text-end">
                            @if($totalDiscount > 0)
                                <span class="mono fw-semibold" style="color:#7c3aed;font-size:13px;">
                                    -₹ {{ number_format($totalDiscount) }}
                                </span>
                            @else
                                <span style="color:#d1d5db;">—</span>
                            @endif
                        </td>

                        {{-- Due --}}
                        <td class="text-end">
                            @if($overallDue > 0)
                                <span class="mono fw-bold text-danger" style="font-size:14px;">
                                    ₹ {{ number_format($overallDue) }}
                                </span>
                                <div style="font-size:9px;font-weight:700;color:#dc2626;letter-spacing:.06em;margin-top:1px;">OUTSTANDING</div>
                            @else
                                <span style="font-size:11px;font-weight:700;color:#16a34a;letter-spacing:.04em;">
                                    <i class="bi bi-check-circle-fill"></i> CLEAR
                                </span>
                            @endif
                        </td>

                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- ── Cancel Modal ── --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px;overflow:hidden;">
            <div class="modal-header py-3" style="background:linear-gradient(135deg,#7f1d1d,#dc2626);">
                <h6 class="modal-title fw-bold text-white mb-0">
                    <i class="bi bi-x-circle me-2"></i>Invoice Cancel Karo
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="cancelForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="d-flex align-items-start gap-3 p-3 mb-3"
                         style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;">
                        <i class="bi bi-exclamation-triangle-fill text-warning mt-1" style="font-size:16px;flex-shrink:0;"></i>
                        <div style="font-size:13px;">
                            Invoice <strong id="cancelInvoiceNo" class="text-danger"></strong> cancel hone ke baad
                            wapas nahi ho sakta. Student ka due amount automatically restore ho jaayega.
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Cancel Reason <span class="text-danger">*</span></label>
                        <textarea name="cancel_reason" id="cancelReason" class="form-control" rows="3" required
                                  placeholder="Cancel karne ka reason likhein..." style="font-size:13px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 border-top" style="background:#f9fafb;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Wapas Jao</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-semibold">
                        <i class="bi bi-x-circle me-1"></i>Haan, Cancel Karo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const saved = JSON.parse(localStorage.getItem('feeHistColPrefs') || '{}');
    document.querySelectorAll('.hcol-toggle').forEach(cb => {
        const col = cb.dataset.col;
        if (saved[col] !== undefined) {
            cb.checked = saved[col];
            toggleHCol(col, saved[col], false);
        } else {
            toggleHCol(col, cb.checked, false);
        }
    });
});

function toggleHCol(col, visible, save = true) {
    document.querySelectorAll('.' + col).forEach(el => {
        el.style.display = visible ? '' : 'none';
    });
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
