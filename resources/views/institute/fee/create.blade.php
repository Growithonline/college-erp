@php
    $feeLayout = auth()->guard('staff')->check()
        ? 'staff.layout'
        : (auth()->guard('center')->check()
            ? 'center.layout'
            : (auth()->guard('partner')->check() ? 'partner.layout' : 'institute.layout'));
@endphp
@extends($feeLayout)
@section('title','Collect Fee')
@section('breadcrumb','Fee / Collect Fee')
@section('content')
@php
    $walletBlocked = (auth()->guard('center')->check() || auth()->guard('partner')->check())
        ? session('wallet_blocked')
        : null;
    $walletExtRoute = auth()->guard('center')->check()
        ? 'center.fee.wallet.request-extension'
        : (auth()->guard('partner')->check() ? 'partner.fee.wallet.request-extension' : null);
@endphp

{{-- Wallet blocked popup --}}
@if($walletBlocked)
@php $wbInsufficient = $walletBlocked['type'] === 'insufficient'; @endphp
<div class="modal fade" id="walletBlockedModal" tabindex="-1"
    @if(!$wbInsufficient) data-bs-backdrop="static" data-bs-keyboard="false" @endif>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-{{ $walletBlocked['type'] === 'expired' ? 'danger' : 'warning' }} text-{{ $walletBlocked['type'] === 'expired' ? 'white' : 'dark' }} py-2">
                <h6 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Fee Collection
                    @if($walletBlocked['type'] === 'expired') Window Expired
                    @elseif($walletBlocked['type'] === 'exhausted') Tokens Exhausted
                    @elseif($walletBlocked['type'] === 'insufficient') Insufficient Tokens
                    @else Suspended
                    @endif
                </h6>
                @if($wbInsufficient)
                <button type="button" class="btn-close {{ $walletBlocked['type'] === 'expired' ? 'btn-close-white' : '' }}"
                    data-bs-dismiss="modal" aria-label="Close"></button>
                @endif
            </div>
            <div class="modal-body">
                <p class="mb-3">{{ $walletBlocked['reason'] }}</p>

                @if(!session('extension_request_sent') && $walletExtRoute)
                <hr>
                <p class="small text-muted mb-2">Send a request to admin to
                    @if($walletBlocked['type'] === 'expired' || $walletBlocked['type'] === 'suspended') reopen the collection window.
                    @else add more tokens.
                    @endif
                </p>
                <form method="POST" action="{{ route($walletExtRoute) }}" id="walletExtForm">
                    @csrf
                    <input type="hidden" name="request_type"
                        value="{{ in_array($walletBlocked['type'], ['expired','suspended']) ? 'expiry_extension' : 'token_topup' }}">

                    @if(in_array($walletBlocked['type'], ['expired','suspended']))
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Request Days</label>
                            <input type="number" name="requested_days" class="form-control form-control-sm"
                                placeholder="e.g. 30" min="1" max="365">
                        </div>
                    @else
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Request Amount (₹)</label>
                            <input type="number" name="requested_amount" class="form-control form-control-sm"
                                placeholder="e.g. 50000" min="1">
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Reason / Message</label>
                        <textarea name="reason" class="form-control form-control-sm" rows="2"
                            placeholder="Briefly explain your request..." required></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-send me-1"></i> Send Request to Admin
                        </button>
                        @if($wbInsufficient)
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        @else
                        <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Go Back
                        </a>
                        @endif
                    </div>
                </form>
                @elseif(session('extension_request_sent'))
                    <div class="alert alert-success py-2 small mb-0">
                        <i class="bi bi-check-circle me-1"></i> {{ session('extension_request_sent') }}
                    </div>
                @endif
            </div>
            @if(!$wbInsufficient)
            <div class="modal-footer py-2">
                <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Go Back
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = new bootstrap.Modal(document.getElementById('walletBlockedModal'));
        modal.show();
    });
</script>
@endif

@if($errors->has('wallet_error'))
<div class="alert alert-danger alert-dismissible fade show py-2 mb-3" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i> {{ $errors->first('wallet_error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Wallet status widget + partial token warning (center/partner only) --}}
@php
    $feeWallet = null;
    $walletRemaining = null;
    if (auth()->guard('center')->check()) {
        $feeWallet = auth()->guard('center')->user()->wallet;
        $walletRemaining = $feeWallet?->remaining_tokens;
        $walletStatusRoute = 'center.fee.wallet.status';
    } elseif (auth()->guard('partner')->check()) {
        $feeWallet = auth()->guard('partner')->user()->wallet;
        $walletRemaining = $feeWallet?->remaining_tokens;
        $walletStatusRoute = 'partner.fee.wallet.status';
    }
@endphp

@if($feeWallet)
@php
    $fwExpired   = $feeWallet->isExpired();
    $fwExhausted = (float)$feeWallet->remaining_tokens <= 0;
    $fwLow       = !$fwExpired && !$fwExhausted && (float)$feeWallet->remaining_tokens < (float)$feeWallet->total_tokens * 0.15;
    $fwColor     = ($fwExpired || $fwExhausted) ? '#fef2f2' : ($fwLow ? '#fefce8' : '#f0fdf4');
    $fwBorder    = ($fwExpired || $fwExhausted) ? '#ef4444' : ($fwLow ? '#f59e0b' : '#22c55e');
@endphp
<div class="rounded-3 mb-3 px-3 py-2 d-flex align-items-center justify-content-between flex-wrap gap-2"
     style="background:{{ $fwColor }}; border:1px solid {{ $fwBorder }};">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <span class="fw-semibold small" style="color:{{ $fwBorder }};">
            <i class="bi bi-wallet2 me-1"></i> Fee Wallet
        </span>
        <span class="small text-muted">
            Balance:
            <strong class="{{ ($fwExhausted || $fwExpired) ? 'text-danger' : ($fwLow ? 'text-warning' : 'text-success') }}">
                ₹{{ number_format($feeWallet->remaining_tokens, 0) }}
            </strong>
        </span>
        <span class="small text-muted">
            Expires:
            <strong class="{{ $fwExpired ? 'text-danger' : 'text-dark' }}">
                {{ $feeWallet->expires_at?->format('d M Y') ?? '—' }}
            </strong>
            @if($fwExpired) <span class="badge bg-danger ms-1">Expired</span>
            @elseif($fwLow) <span class="badge bg-warning text-dark ms-1">Low Balance</span>
            @endif
        </span>
    </div>
    <a href="{{ route($walletStatusRoute) }}" class="text-muted small text-decoration-none">
        <i class="bi bi-arrow-right-circle me-1"></i>View Wallet
    </a>
</div>
@endif

@if(!is_null($walletRemaining) && (float)$walletRemaining > 0)
<script>const walletRemainingTokens = {{ (float)$walletRemaining }};</script>
@endif
{{-- Partial token warning — hidden by JS, uses d-none to avoid d-flex override issue --}}
<div id="walletTokenWarnBanner" class="alert alert-warning py-2 mb-3 align-items-center gap-2 d-none">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <span id="walletTokenWarnMsg"></span>
</div>

@php
    $feeRoutePrefix = auth()->guard('staff')->check()
        ? 'staff.fee'
        : (auth()->guard('center')->check()
            ? 'center.fee'
            : (auth()->guard('partner')->check() ? 'partner.fee' : 'fee'));
    $feeBackRoute = \Illuminate\Support\Facades\Route::has($feeRoutePrefix . '.index')
        ? $feeRoutePrefix . '.index'
        : $feeRoutePrefix . '.create';
    $feeCreateUrl = route($feeRoutePrefix . '.create');
    $feeStoreUrl = route($feeRoutePrefix . '.store');
    $feeBackUrl = route($feeBackRoute);
    $feeSearchUrl = \Illuminate\Support\Facades\Route::has($feeRoutePrefix . '.search-student')
        ? route($feeRoutePrefix . '.search-student')
        : route('fee.search-student');
    $lockPaymentDate = auth()->guard('staff')->check()
        || auth()->guard('center')->check()
        || auth()->guard('partner')->check();
    $defaultPaymentDate = now()->toDateString();
    $defaultPaymentDatetime = '';
    $paymentModeLabels = [
        'cash' => 'Cash',
        'upi' => 'UPI',
        'online' => 'Online Transfer',
        'cheque' => 'Cheque',
        'dd' => 'DD',
        'neft' => 'NEFT',
        'rtgs' => 'RTGS',
    ];
    $allowedPaymentModes = $allowedPaymentModes ?? array_keys($paymentModeLabels);
    $cashAllowed = in_array('cash', $allowedPaymentModes, true);
    $recentInvoices = $recentInvoices ?? collect();
@endphp

<style>
.fee-collect-table {
    width: 100%;
    min-width: 0;
    table-layout: fixed;
    font-size: 11.5px;
}
.fee-collect-table th,
.fee-collect-table td {
    vertical-align: middle;
    white-space: normal;
    padding: 0.55rem 0.4rem;
}
.fee-collect-table thead th {
    font-size: 11px;
    line-height: 1.15;
}
.fee-collect-table .fee-item-col {
    width: 34%;
    min-width: 0;
    white-space: normal;
}
.fee-collect-table .numeric-col {
    width: 9%;
}
.fee-collect-table .input-col {
    width: 12%;
}
.fee-collect-table .balance-col {
    width: 8%;
}
.fee-collect-table .input-group {
    flex-wrap: nowrap;
}
.fee-collect-table .input-group-text {
    padding: 0.25rem 0.45rem;
    font-size: 11px;
}
.fee-collect-table .form-control {
    padding: 0.25rem 0.35rem;
    font-size: 11px;
}
.fee-collect-table .fee-item-col .badge {
    display: inline-block;
    margin-top: 0.2rem;
}
.fee-collect-table .fee-item-col label {
    line-height: 1.25;
}
.fee-collect-scroll {
    overflow-x: visible;
}
.fee-summary-card {
    min-height: 82px;
}
.recent-history-list {
    max-height: 420px;
    overflow-y: auto;
}
.recent-history-item + .recent-history-item {
    border-top: 1px solid #e2e8f0;
}
@media (max-width: 1600px) {
    .fee-collect-table {
        font-size: 11px;
    }
    .fee-collect-table .fee-item-col {
        width: 31%;
    }
    .fee-collect-table .numeric-col {
        width: 8%;
    }
    .fee-collect-table .input-col {
        width: 13%;
    }
}
@media (max-width: 1399.98px) {
    .fee-collect-scroll {
        overflow-x: auto;
    }
    .fee-collect-table {
        min-width: 940px;
    }
}
</style>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Header --}}
@php
    $_isStaff   = auth()->guard('staff')->check();
    $_isCenter  = auth()->guard('center')->check();
    $_isPartner = auth()->guard('partner')->check();
    $showRoute    = $_isStaff   ? 'staff.admissions.show'      : ($_isCenter  ? 'center.students.show'        : ($_isPartner ? 'partner.students.show'        : 'admissions.show'));
    $walletRoute  = $_isStaff   ? 'staff.fee.wallet.student'   : ($_isCenter  ? 'center.fee.wallet.student'   : ($_isPartner ? 'partner.fee.wallet.student'   : 'fee.wallet.student'));
    $historyRoute = $_isStaff   ? 'staff.fee.student-history'  : ($_isCenter  ? 'center.fee.student-history'  : ($_isPartner ? 'partner.fee.student-history'  : 'fee.student-history'));
    $stmtPrefix   = $_isStaff   ? 'staff.statement'            : ($_isCenter  || $_isPartner ? null : 'statement');
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Collect Fee</h4>
        <small class="text-muted">Select a student to collect fee</small>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        @if(!empty($student))
            <a href="{{ route($showRoute, $student->id) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-person me-1"></i> Profile
            </a>
            <a href="{{ route($walletRoute, $student->id) }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-wallet2 me-1"></i> Wallet
            </a>
            <a href="{{ route($historyRoute, $student->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-receipt me-1"></i> Fee History
            </a>
            @if($stmtPrefix)
            <a href="{{ route($stmtPrefix . '.balance', ['student_id' => $student->id, 'print' => 'thermal']) }}" target="_blank" class="btn btn-outline-success btn-sm">
                <i class="bi bi-printer me-1"></i> Print Balance
            </a>
            <a href="{{ route($stmtPrefix . '.fee-record', ['student_id' => $student->id, 'print' => 'thermal']) }}" target="_blank" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-printer me-1"></i> Print Statement
            </a>
            @endif
        @endif
        <a href="{{ $feeBackUrl }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

@if(!$student)
{{-- No student â€” show search only --}}
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-3 d-flex align-items-center justify-content-between gap-2" style="background:#1e293b;color:white;">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-search me-2"></i>Student Search</h6>
                @if(!empty($feeSessions))
                <select id="feeSessionSelect" class="form-select form-select-sm flex-shrink-0"
                        style="max-width:150px;font-size:11px;background:rgba(255,255,255,0.12);border-color:rgba(255,255,255,0.3);color:#fff;">
                    @foreach($feeSessions as $sess)
                    <option value="{{ $sess->id }}"
                            style="background:#1e293b;color:#fff;"
                            {{ (int)($feeSessionId ?? 0) === $sess->id ? 'selected' : '' }}>
                        {{ $sess->name }}{{ $sess->is_active ? ' ●' : '' }}
                    </option>
                    @endforeach
                </select>
                @endif
            </div>
            <div class="card-body p-3">
                <input type="text" id="studentSearch" class="form-control mb-3"
                       placeholder="Name, Mobile or Student ID...">
                <div id="searchResults" class="list-group" style="max-height:400px;overflow-y:auto;"></div>
            </div>
        </div>
    </div>
</div>

@else
{{-- Student selected — show full UI --}}
@php
    $displayContext = $feeBreakup['context'] ?? [];
    $displayCoursePart = $displayContext['course_part'] ?? $student->coursePart ?? null;
    $displaySemester = $displayContext['semester'] ?? $student->current_semester ?? null;
    $isTerminalStudent = in_array($student->status ?? '', ['passed_out', 'backlog', 'failed', 'dropped']);
    $isPendingStudent  = ($student->status ?? '') === 'pending' && !($isAdmissionFeeFlow ?? false);
    $canApproveAdmission = $canApproveAdmission ?? false;
    $approvalRoute     = auth()->guard('staff')->check() ? 'staff.admissions.approvals.show' : 'admissions.approvals.show';
@endphp

@if($isPendingStudent)
<div class="card border-warning border-2 shadow-sm mb-4" style="border-left: 5px solid #fd7e14 !important;">
    <div class="card-body py-3 px-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:48px;height:48px;background:#fd7e14;">
                    <i class="bi bi-hourglass-split text-white fs-4"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fw-bold fs-6" style="color:#fd7e14;">Fee Collection Restricted</span>
                        <span class="badge" style="background:#fd7e14;">Pending Approval</span>
                    </div>
                    <div class="text-muted small">
                        This student's admission is pending approval. Fee collection is not allowed until the admission is approved.
                    </div>
                </div>
            </div>
            @if($canApproveAdmission)
            <div class="text-end flex-shrink-0">
                <a href="{{ route($approvalRoute, $student->id) }}"
                   class="btn btn-sm btn-warning text-white">
                    <i class="bi bi-shield-check me-1"></i> Review &amp; Approve Admission
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@elseif($isTerminalStudent)
<div class=”card border-danger border-2 shadow-sm mb-4” style=”border-left: 5px solid #dc3545 !important;”>
    <div class=”card-body py-3 px-4”>
        <div class=”d-flex align-items-center justify-content-between flex-wrap gap-3”>
            <div class=”d-flex align-items-center gap-3”>
                <div class=”rounded-circle bg-danger d-flex align-items-center justify-content-center flex-shrink-0”
                     style=”width:48px;height:48px;”>
                    <i class=”bi bi-slash-circle-fill text-white fs-4”></i>
                </div>
                <div>
                    <div class=”d-flex align-items-center gap-2 mb-1”>
                        <span class=”fw-bold fs-6 text-danger”>Fee Collection Blocked</span>
                        <span class=”badge bg-danger”>{{ ucwords(str_replace('_', ' ', $student->status)) }}</span>
                    </div>
                    <div class=”text-muted small”>
                        This student's academic status is terminal. New fee collection is not allowed.
                    </div>
                </div>
            </div>
            @if(isset($walletSummary) && ($walletSummary['balance'] ?? 0) < 0)
            <div class=”text-end flex-shrink-0”>
                <div class=”small text-muted mb-1”>Outstanding Due</div>
                <div class=”fw-bold text-danger fs-5”>₹ {{ number_format(abs($walletSummary['balance']), 2) }}</div>
                @if(isset($student))
                @php
                    $walletLinkRoute = auth()->guard('staff')->check() ? 'staff.fee.wallet.student' : (auth()->guard('center')->check() ? 'center.fee.wallet.student' : (auth()->guard('partner')->check() ? 'partner.fee.wallet.student' : 'fee.wallet.student'));
                @endphp
                <a href=”{{ route($walletLinkRoute, $student->id) }}”
                   class=”btn btn-outline-danger btn-sm mt-1”>
                    <i class=”bi bi-wallet me-1”></i> View Wallet
                </a>
                @endif
            </div>
            @else
            <div class=”text-end flex-shrink-0”>
                <span class=”badge bg-success px-3 py-2”>
                    <i class=”bi bi-check-circle me-1”></i> No Dues
                </span>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- Student Info Header (Image 2 style) --}}
<div class="card border-0 shadow-sm mb-4"
     style="background:linear-gradient(135deg,#1e293b,#0f4c81);color:white;">
    <div class="card-body py-3">
        <div class="row align-items-center">
            <div class="col-auto">
                @if($student->photo)
                    <img src="{{ Storage::url($student->photo) }}"
                         style="width:60px;height:60px;object-fit:cover;border-radius:50%;border:3px solid rgba(255,255,255,0.3);">
                @else
                    <div style="width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;">
                        {{ strtoupper(substr($student->name, 0, 1)) }}
                    </div>
                @endif
            </div>
            <div class="col">
                <div class="fw-bold fs-5">{{ $student->name }}</div>
                <div class="opacity-75 small">
                    ID: <b>{{ $student->student_uid }}</b> &nbsp;|&nbsp;
                    {{ $student->stream->course->name ?? '' }} - {{ $student->stream->name ?? '' }}
                    @if($displayCoursePart) &nbsp;|&nbsp; {{ $displayCoursePart->year_label }}@if($displaySemester) / Sem {{ $displaySemester }}@endif @endif
                </div>
                <div class="opacity-75 small">
                    Mobile: {{ $student->mobile }} &nbsp;|&nbsp; Session: {{ $student->session->name ?? '' }}
                </div>
            </div>
            <div class="col-auto text-end">
                @php
                    $feeTotal = (float) ($feeBreakup['total'] ?? 0);
                    $headerDue = (float) ($walletSummary['total_due'] ?? 0);
                @endphp
                @if($headerDue > 0)
                    <div style="font-size:13px;opacity:.75;">Total Due</div>
                    <div class="fw-bold fs-4 text-warning">₹ {{ number_format($headerDue, 2) }}</div>
                @elseif($feeTotal > 0)
                    <span class="badge bg-success fs-6">No Dues</span>
                @else
                    <span class="badge bg-secondary fs-6 opacity-75">Fee not set</span>
                @endif
            </div>
        </div>
        {{-- Change student --}}
        <div class="mt-2 pt-2 border-top border-white border-opacity-25">
            <input type="text" id="studentSearch" class="form-control form-control-sm bg-white text-dark border-0"
                   placeholder="Search Student" style="color:#1e293b!important;">
            <div id="searchResults" class="list-group mt-1 position-absolute" style="z-index:100;min-width:300px;"></div>
        </div>
    </div>
</div>

{{-- ── Library Fine Alert ────────────────────────────────────────────── --}}
@if(isset($libraryFineData) && $libraryFineData)
<div class="alert border-0 shadow-sm mb-4 p-0 overflow-hidden" style="border-left:4px solid #0891b2!important;">
    <div class="d-flex align-items-start gap-3 p-3">
        <div class="rounded-3 flex-shrink-0 d-flex align-items-center justify-content-center"
             style="width:44px;height:44px;background:#e0f2fe;">
            <i class="bi bi-book fs-5" style="color:#0891b2;"></i>
        </div>
        <div class="flex-grow-1">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <div class="fw-bold" style="color:#0891b2;">Library Fine Pending</div>
                    <div class="text-muted small">
                        {{ $libraryFineData['transactions']->count() }} {{ $libraryFineData['transactions']->count() === 1 ? 'book' : 'books' }} with outstanding fine
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold fs-5 text-danger">₹ {{ number_format($libraryFineData['total_pending'], 2) }}</span>
                    @if($canCollectLibFine)
                        <button type="button" class="btn btn-sm text-white"
                                style="background:#0891b2;"
                                data-bs-toggle="modal" data-bs-target="#libFineModal">
                            <i class="bi bi-cash-coin me-1"></i>Collect Fine
                        </button>
                    @else
                        <a href="{{ route($libFineShowRoute, $libraryFineData['member']->id) }}"
                           class="btn btn-sm btn-outline-secondary" target="_blank">
                            <i class="bi bi-box-arrow-up-right me-1"></i>View
                        </a>
                    @endif
                </div>
            </div>
            {{-- Per-book quick list --}}
            <div class="mt-2 d-flex flex-wrap gap-2">
                @foreach($libraryFineData['transactions'] as $libTx)
                <span class="badge border fw-normal" style="background:#f0f9ff;color:#0369a1;font-size:11px;">
                    {{ $libTx->copy->book->title ?? 'Book' }}
                    <span class="ms-1 text-danger fw-bold">₹{{ number_format($libTx->pending_fine, 0) }}</span>
                </span>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ── Library Fine Collection Modal ──────────────────────────────────── --}}
<div class="modal fade" id="libFineModal" tabindex="-1" aria-labelledby="libFineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-3" style="background:#0891b2;color:white;">
                <h6 class="modal-title fw-bold mb-0" id="libFineModalLabel">
                    <i class="bi bi-book me-2"></i>Collect Library Fine
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                {{-- Alert area --}}
                <div id="libFineAlert" class="d-none mb-3"></div>

                {{-- Per-book amounts --}}
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-2">Book</th>
                                <th class="text-center">Accession No</th>
                                <th class="text-end">Fine</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Pending</th>
                                <th class="text-end pe-2" style="min-width:110px;">Pay Now (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($libraryFineData['transactions'] as $libTx)
                            <tr>
                                <td class="ps-2 small fw-semibold">{{ $libTx->copy->book->title ?? 'Book' }}</td>
                                <td class="text-center small text-muted">{{ $libTx->copy->accession_no ?? '—' }}</td>
                                <td class="text-end small">₹{{ number_format($libTx->fine_amount, 2) }}</td>
                                <td class="text-end small text-success">₹{{ number_format($libTx->fine_paid, 2) }}</td>
                                <td class="text-end small fw-bold text-danger">₹{{ number_format($libTx->pending_fine, 2) }}</td>
                                <td class="text-end pe-2">
                                    <input type="number"
                                           class="form-control form-control-sm text-end lib-fine-input"
                                           style="width:100px;display:inline-block;"
                                           data-txn-id="{{ $libTx->id }}"
                                           data-max="{{ $libTx->pending_fine }}"
                                           min="0"
                                           max="{{ $libTx->pending_fine }}"
                                           step="0.01"
                                           value="{{ $libTx->pending_fine }}"
                                           placeholder="0.00">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-semibold">
                            <tr>
                                <td colspan="4" class="ps-2">Total Paying</td>
                                <td class="text-end text-danger">₹ {{ number_format($libraryFineData['total_pending'], 2) }}</td>
                                <td class="text-end pe-2 text-primary" id="libFineTotalDisplay">
                                    ₹ {{ number_format($libraryFineData['total_pending'], 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Payment details --}}
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                        <select id="libFinePayMode" class="form-select form-select-sm">
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
                            <option value="online">Online Transfer</option>
                            <option value="neft">NEFT/RTGS</option>
                            <option value="cheque">Cheque</option>
                            <option value="dd">DD</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="libFineDateWrap">
                        <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" id="libFineDate" class="form-control form-control-sm"
                               value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-4 d-none" id="libFineDatetimeWrap">
                        <label class="form-label small fw-semibold">Payment Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" id="libFineDatetime" class="form-control form-control-sm"
                               value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="col-md-8 d-none" id="libFineRefWrap">
                        <label class="form-label small fw-semibold">Transaction Reference <span class="text-danger">*</span></label>
                        <input type="text" id="libFineRef" class="form-control form-control-sm"
                               placeholder="UPI ref / cheque no / transaction ID">
                    </div>
                    @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                    <div class="col-md-4 d-none" id="libFineBankWrap">
                        <label class="form-label small fw-semibold">Bank Account</label>
                        <select id="libFineBank" class="form-select form-select-sm">
                            <option value="">— Select —</option>
                            @foreach($bankAccounts as $ba)
                            <option value="{{ $ba->id }}">{{ $ba->bank_name }} - {{ $ba->account_no }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Receipt No</label>
                        <input type="text" id="libFineReceipt" class="form-control form-control-sm"
                               value="LIB-RCP-{{ now()->format('Ymd') }}-{{ $libraryFineData['member']->id }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Remarks</label>
                        <input type="text" id="libFineRemarks" class="form-control form-control-sm"
                               placeholder="Optional note">
                    </div>
                </div>

            </div>
            <div class="modal-footer border-top py-2">
                <div class="me-auto small text-muted">
                    Total: <strong class="text-danger" id="libFineTotalFooter">₹ {{ number_format($libraryFineData['total_pending'], 2) }}</strong>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm text-white" style="background:#0891b2;"
                        id="libFineSubmitBtn"
                        data-url="{{ route($libFineCollectRoute, $libraryFineData['member']->id) }}">
                    <span id="libFineSubmitSpinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                    <i class="bi bi-cash-coin me-1" id="libFineSubmitIcon"></i>
                    Collect Fine
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── Fee Plan Installment Summary ── --}}
@if(isset($feePlanInfo) && $feePlanInfo)
@php
    $fp            = $feePlanInfo['plan'];
    $instAmts      = $feePlanInfo['installmentAmounts'];
    $totalFeeP     = $feePlanInfo['totalFee'];
    $totalPaidP    = $feePlanInfo['totalPaid'];
    $totalDueSoFar = $feePlanInfo['totalDueSoFar'];
    $nextDueInst   = $feePlanInfo['nextDueInst'];
    $nextDueAmount = $feePlanInfo['nextDueAmount'];
    $fillAmount    = $feePlanInfo['fillAmount'] ?? $nextDueAmount;
    $isOverdue     = $feePlanInfo['overdue'];
    $cumulative    = 0;
@endphp

{{-- Next Due Installment Alert --}}
@if($nextDueInst)
<div class="alert {{ $isOverdue ? 'alert-danger' : 'alert-warning' }} py-2 mb-2 d-flex align-items-center justify-content-between gap-2">
    <div>
        <i class="bi bi-exclamation-circle me-1"></i>
        <strong>Next Due:</strong> {{ $nextDueInst->label }}
        &nbsp;—&nbsp;
        <span class="fw-bold">₹ {{ number_format($nextDueAmount, 0) }}</span>
        <span class="text-muted small ms-2">({{ $nextDueInst->dueTriggerLabel() }})</span>
        @if($isOverdue)
        <span class="badge bg-danger ms-2">Overdue</span>
        @endif
    </div>
    <button type="button" class="btn btn-sm btn-dark fw-semibold"
            onclick="applyInstallmentAmount({{ $fillAmount }})"
            title="Pre-fill all fee fields to cover all currently due installments">
        <i class="bi bi-lightning-fill me-1"></i>Fill ₹ {{ number_format($fillAmount, 0) }}
    </button>
</div>
@elseif($totalFeeP > 0 && $totalPaidP >= $totalFeeP - 0.5)
<div class="alert alert-success py-2 mb-2">
    <i class="bi bi-check-circle me-1"></i>
    <strong>All installments paid.</strong> Fee is fully cleared.
</div>
@endif

{{-- Installment Progress Bar --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-semibold small"><i class="bi bi-layers me-1 text-primary"></i>Fee Plan: {{ $fp->name }}</span>
            <span class="small text-muted">Total: ₹ {{ number_format($totalFeeP, 0) }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-2">
            @foreach($fp->installments as $inst)
            @php
                $amt        = (float) ($instAmts[$inst->installment_number] ?? 0);
                $cumulative += $amt;
                $isPaid     = $totalPaidP >= $cumulative - 0.5;
                $isDue      = $inst->isDue($student);
                $isNext     = $nextDueInst && $inst->installment_number === $nextDueInst->installment_number;
            @endphp
            <span class="badge border
                {{ $isPaid  ? 'bg-success text-white' : ($isNext ? 'bg-warning text-dark border-warning' : ($isDue ? 'bg-danger bg-opacity-10 text-danger border-danger' : 'bg-light text-muted border-secondary')) }}"
                style="font-size:11px; padding:5px 8px;">
                @if($isPaid)<i class="bi bi-check-circle me-1"></i>
                @elseif($isNext)<i class="bi bi-clock me-1"></i>
                @elseif(!$isDue)<i class="bi bi-lock me-1"></i>
                @endif
                {{ $inst->label }}: ₹ {{ number_format($amt, 0) }}
                @if(!$isDue)<small class="opacity-75">(not due yet)</small>@endif
            </span>
            @endforeach
        </div>
        <div class="d-flex gap-3" style="font-size:11px;">
            <span>Paid: <strong class="text-success">₹ {{ number_format($totalPaidP, 0) }}</strong></span>
            <span>Due now: <strong class="text-warning">₹ {{ number_format(max(0, $totalDueSoFar - $totalPaidP), 0) }}</strong></span>
            <span>Remaining total: <strong class="text-danger">₹ {{ number_format(max(0, $totalFeeP - $totalPaidP), 0) }}</strong></span>
        </div>
    </div>
</div>
@endif

<div class="row g-4">

    {{-- LEFT: Fee Breakup --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-list-ul me-2 text-primary"></i>Fee Breakup
                </h6>
            </div>
            <div class="card-body p-0">
                @if($feeBreakup && !empty($feeBreakup['items']))
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach($feeBreakup['items'] as $item)
                        <tr>
                            <td class="small ps-3">
                                {{ $item['label'] }}
                                @if(($item['type'] ?? null) === 'previous_due')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border ms-1" style="font-size:10px;">Carry Forward</span>
                                @endif
                            </td>
                            <td class="text-end pe-3 fw-semibold text-danger small">
                                ₹ {{ number_format($item['amount'], 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td class="ps-3 fw-bold">Total Charged</td>
                            <td class="text-end pe-3 fw-bold">
                                ₹ {{ number_format($feeBreakup['total'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
                @else
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-info-circle d-block mb-1 fs-4"></i>
                    No fee rules configured.
                </div>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-clock-history me-2 text-info"></i>Previous Fee History
                </h6>
                <span class="badge bg-light text-dark border">{{ $recentInvoices->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($recentInvoices->isNotEmpty())
                    <div class="recent-history-list">
                        @foreach($recentInvoices as $historyInvoice)
                            @php
                                $historyFine = (float) $historyInvoice->items->sum(fn($historyItem) => (float) ($historyItem->fine ?? 0));
                            @endphp
                            <div class="recent-history-item px-3 py-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold small">{{ $historyInvoice->invoice_no }}</div>
                                        <div class="text-muted" style="font-size:11px;">
                                            {{ $historyInvoice->payment_date?->format('d M Y') ?? '-' }}
                                            @if($historyInvoice->semester)
                                                • Sem {{ $historyInvoice->semester }}
                                            @endif
                                            @if($historyInvoice->session?->name)
                                                • {{ $historyInvoice->session->name }}
                                            @endif
                                        </div>
                                    </div>
                                    @if($historyInvoice->is_cancelled)
                                        <span class="badge bg-danger">Cancelled</span>
                                    @else
                                        <span class="badge bg-success bg-opacity-10 text-success border">Paid</span>
                                    @endif
                                </div>
                                <div class="mt-2 d-flex flex-wrap gap-1">
                                    @foreach($historyInvoice->items->take(4) as $historyItem)
                                        <span class="badge bg-light text-dark border">{{ $historyItem->fee_name }}</span>
                                    @endforeach
                                    @if($historyInvoice->items->count() > 4)
                                        <span class="badge bg-light text-muted border">+{{ $historyInvoice->items->count() - 4 }} more</span>
                                    @endif
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-4">
                                        <div class="small text-muted">Collect</div>
                                        <div class="fw-semibold text-success">₹ {{ number_format((float) $historyInvoice->paid_amount, 0) }}</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted">Discount</div>
                                        <div class="fw-semibold text-warning">₹ {{ number_format((float) ($historyInvoice->discount ?? 0), 0) }}</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="small text-muted">Fine</div>
                                        <div class="fw-semibold text-danger">₹ {{ number_format($historyFine, 0) }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted py-4 small">
                        <i class="bi bi-receipt fs-4 d-block mb-1"></i>
                        No previous fee receipts found for this student.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- RIGHT: Collect Fee Form --}}
    <div class="col-lg-8">
    @if($isPendingStudent)
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:64px;height:64px;background:#fff3cd;">
                    <i class="bi bi-hourglass-split fs-3" style="color:#fd7e14;"></i>
                </div>
                <h6 class="fw-bold mb-2">Admission Pending Approval</h6>
                <p class="text-muted small mb-3">
                    Fee collection is disabled for this student.<br>
                    Please approve the admission first to enable fee collection.
                </p>
                @if($canApproveAdmission)
                <a href="{{ route($approvalRoute, $student->id) }}" class="btn btn-warning text-white btn-sm">
                    <i class="bi bi-shield-check me-1"></i> Review &amp; Approve Admission
                </a>
                @endif
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 fw-semibold small d-flex align-items-center gap-2">
                    <i class="bi bi-cash-coin me-2 text-success"></i>Collect Fee Now
                    @if(isset($staffMaxDiscount) && $staffMaxDiscount === 0)
                        <span class="badge bg-danger" style="font-size:10px;font-weight:600;">
                            <i class="bi bi-x-circle me-1"></i>No Discount Allowed
                        </span>
                    @elseif(isset($staffFeeAllowedTypes) && $staffFeeAllowedTypes !== null)
                        <span class="badge bg-warning text-dark" style="font-size:10px;font-weight:600;">
                            <i class="bi bi-percent me-1"></i>Discount: Per item
                        </span>
                    @elseif(isset($staffMaxDiscount) && $staffMaxDiscount < 100)
                        <span class="badge bg-warning text-dark" style="font-size:10px;font-weight:600;">
                            <i class="bi bi-percent me-1"></i>Discount limit: {{ $staffMaxDiscount }}%
                        </span>
                    @endif
                </h6>
                <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                    <small class="text-muted">One-time pay:</small>
                    <div class="input-group input-group-sm" style="width:150px;">
                        <span class="input-group-text bg-warning text-dark fw-bold">₹</span>
                        <input type="number" id="oneTimePay" class="form-control"
                               placeholder="Amount..." min="0" step="1">
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold"
                            onclick="clearAllFeeInputs()">
                        <i class="bi bi-eraser me-1"></i>Clear
                    </button>
                    <button type="button" class="btn btn-warning btn-sm fw-semibold"
                            onclick="applyOneTimePay()">
                        <i class="bi bi-lightning-fill me-1"></i>Fill
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
            <form method="POST" action="{{ $feeStoreUrl }}" id="feeForm">
            @csrf
            <input type="hidden" name="student_id" value="{{ $student->id }}">

            @if($errors->any())
            <div class="alert alert-danger border-0 rounded-0 mb-0 px-3 py-2" id="formErrorBanner">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
                    <div>
                        <div class="fw-semibold mb-1" style="font-size:13px;">Please fix the following errors before submitting:</div>
                        <ul class="mb-0 ps-3" style="font-size:12px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endif

            {{-- Fee Items Table --}}
            <div class="table-responsive fee-collect-scroll">
            <table class="table table-sm align-middle mb-3 fee-collect-table">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px;" class="text-center">✓</th>
                        <th class="fee-item-col">Fee Item</th>
                        <th class="text-end text-muted small numeric-col">Total</th>
                        <th class="text-end text-muted small numeric-col">Paid</th>
                        <th class="text-end text-muted small numeric-col">Prev<br>Disc</th>
                        <th class="text-end text-muted small numeric-col">Remaining</th>
                        <th class="text-end input-col" style="color:#1d4ed8;">Collect ₹</th>
                        <th class="text-end input-col" style="color:#dc2626;">Fine ₹</th>
                        <th class="text-end input-col" style="color:#d97706;">Disc. ₹</th>
                        <th class="text-end text-muted small balance-col">Balance</th>
                    </tr>
                </thead>
                <tbody id="feeItemsBody">
                @php $idx = 0; @endphp

                @php
                    $collectItems = $feeBreakup['grouped_items'] ?? $feeBreakup['items'] ?? [];
                @endphp
                @if(!empty($collectItems))
                    @foreach($collectItems as $item)
                    @php
                        $feeName      = $item['label'];
                        $amount       = (float) $item['amount'];
                        $feeTypeId    = $item['fee_type_id'] ?? null;
                        // Blocked if restriction active and fee_type_id is not in allowed list.
                        // Null fee_type_id items (previous_due, combined groups) are pre-filtered by controller.
                        $isBlocked    = $staffCollectFeeTypeIds !== null
                            && $feeTypeId !== null
                            && !in_array((int)$feeTypeId, $staffCollectFeeTypeIds, true);
                        // null = no per-item config (allow all); array = whitelist of allowed fee_type_ids
                        $discAllowed  = $staffFeeAllowedTypes === null || ($feeTypeId && in_array($feeTypeId, $staffFeeAllowedTypes));
                        $itemMaxDisc  = ($isBlocked || !$discAllowed) ? 0 : ($staffMaxDiscount ?? 100);
                        $paidData     = $alreadyPaid->get($feeName);
                        $paidAmt      = (float) ($paidData?->paid_total ?? $paidData ?? 0);
                        $paidDisc     = (float) ($paidData?->discount_total ?? 0);
                        $prevFine     = (float) ($fineByFee[$feeName] ?? 0);
                        // Use buildPendingRows result directly — already accounts for fine correctly
                        $remaining    = isset($pendingByFee[$feeName])
                            ? (float) $pendingByFee[$feeName]
                            : max(0, $amount + $prevFine - $paidAmt - $paidDisc);
                        $baseRemaining = max(0, $amount - $paidAmt - $paidDisc);
                        $pendingFine  = max(0, $remaining - $baseRemaining);
                        $baseFullyPaid = ($paidAmt + $paidDisc) >= $amount && $amount > 0;
                        $fullyPaid     = $remaining <= 0 && $amount > 0;
                        $finePending   = $pendingFine > 0;
                        $partial       = !$baseFullyPaid && ($paidAmt + $paidDisc) > 0 && $remaining > 0;
                        $isChecked     = !$isBlocked && $remaining > 0;
                        $initCollect   = 0;
                        $initFine      = 0; // Never pre-fill: pendingFine is already-charged; chargeFineItems adds new charges only
                        $initBalance   = $remaining;
                    @endphp
                    @if($isBlocked)
                        @php $idx++; @endphp
                        @continue
                    @endif
                    <tr class="{{ $fullyPaid ? 'table-success' : ($finePending ? 'table-warning bg-opacity-25' : '') }}">
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input fee-check"
                                   id="ck-{{ $idx }}"
                                   name="fee_items[{{ $idx }}][checked]" value="1"
                                   {{ $isChecked ? 'checked' : '' }}
                                   onchange="toggleRow({{ $idx }})">
                        </td>
                        <td class="fee-item-col">
                            <input type="hidden" name="fee_items[{{ $idx }}][item_key]" value="{{ $item['item_key'] ?? '' }}">
                            <input type="hidden" name="fee_items[{{ $idx }}][fee_type_id]" value="{{ $item['fee_type_id'] ?? '' }}">
                            <input type="hidden" name="fee_items[{{ $idx }}][subject_id]" value="{{ $item['subject_id'] ?? '' }}">
                            <input type="hidden" name="fee_items[{{ $idx }}][item_type]" value="{{ $item['type'] ?? '' }}">
                            <input type="hidden" name="fee_items[{{ $idx }}][fee_name]" value="{{ $feeName }}">
                            <input type="hidden" name="fee_items[{{ $idx }}][total_fee]" value="{{ number_format($amount, 2, '.', '') }}">
                            <input type="hidden" name="fee_items[{{ $idx }}][transport_allocation_id]" value="{{ $item['transport_allocation_id'] ?? '' }}">
                            <label for="ck-{{ $idx }}" class="mb-0 small fw-semibold {{ !$isChecked ? 'text-muted' : '' }}" id="lbl-{{ $idx }}">
                                {{ $feeName }}
                            </label>
                            @if(($item['type'] ?? null) === 'previous_due')
                                <span class="badge bg-danger bg-opacity-10 text-danger border ms-1" style="font-size:10px;">Previous Due</span>
                            @endif
                            @if($fullyPaid)
                                <span class="badge bg-success ms-1" style="font-size:10px;">✓ Paid</span>
                            @elseif($finePending)
                                <span class="badge bg-warning text-dark ms-1" style="font-size:10px;">Fine Due ₹{{ number_format($pendingFine) }}</span>
                            @elseif($partial)
                                <span class="badge bg-warning text-dark ms-1" style="font-size:10px;">Partial - Paid ₹{{ number_format($paidAmt) }}</span>
                            @elseif($amount > 0)
                                <span class="badge bg-primary bg-opacity-10 text-primary border ms-1" style="font-size:10px;">Assigned ₹{{ number_format($amount) }}</span>
                            @endif
                        </td>
                        <td class="text-end text-muted small">{{ number_format($amount) }}</td>
                        <td class="text-end small {{ $paidAmt > 0 ? 'text-success' : 'text-muted' }}">
                            {{ $paidAmt > 0 ? number_format($paidAmt) : '—' }}
                        </td>
                        <td class="text-end small">
                            @if($paidDisc > 0)
                                <span class="text-success fw-semibold">{{ number_format($paidDisc) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end small fw-semibold {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">
                            {{ $remaining > 0 ? number_format($remaining) : '0' }}
                        </td>
                        <td class="text-end">
                            <div class="input-group input-group-sm justify-content-end">
                                <span class="input-group-text">₹</span>
                                <input type="number"
                                       name="fee_items[{{ $idx }}][amount]"
                                       class="form-control fee-amount text-end"
                                       id="amt-{{ $idx }}"
                                       value="{{ $initCollect }}"
                                       min="0" step="1"
                                       style="max-width:72px;"
                                       {{ !$isChecked ? 'disabled' : '' }}
                                       data-remaining="{{ $baseRemaining }}"
                                       data-pending-fine="{{ $pendingFine }}"
                                       data-max-disc="{{ $itemMaxDisc }}"
                                       oninput="updateBalance({{ $idx }})">
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="input-group input-group-sm justify-content-end">
                                <span class="input-group-text" style="background:#fef2f2;border-color:#f87171;color:#dc2626;font-weight:600;">₹</span>
                                <input type="number"
                                       name="fee_items[{{ $idx }}][fine]"
                                       class="form-control fee-fine text-end"
                                       id="fine-{{ $idx }}"
                                       value="{{ number_format($initFine, 0, '.', '') }}"
                                       data-initial-fine="{{ number_format($initFine, 0, '.', '') }}"
                                       min="0" step="1"
                                       style="max-width:72px;border-color:#f87171;font-weight:600;color:#dc2626;"
                                       {{ !$isChecked ? 'disabled' : '' }}
                                       oninput="onFineChange({{ $idx }})"
                                       onblur="normalizeRowValues({{ $idx }})">
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="input-group input-group-sm justify-content-end">
                                <span class="input-group-text" style="background:#fff8e1;border-color:#f59e0b;color:#d97706;font-weight:600;">₹</span>
                                <input type="number"
                                       name="fee_items[{{ $idx }}][discount]"
                                       class="form-control fee-disc text-end"
                                       id="disc-{{ $idx }}"
                                       value="0"
                                       min="0" step="1"
                                       style="max-width:72px;border-color:#f59e0b;font-weight:600;color:#d97706;{{ $itemMaxDisc <= 0 ? 'background:#f1f5f9;cursor:not-allowed;' : '' }}"
                                       {{ (!$isChecked || $itemMaxDisc <= 0) ? 'disabled' : '' }}
                                       oninput="onDiscChange({{ $idx }})"
                                       onblur="normalizeRowValues({{ $idx }})">
                            </div>
                        </td>
                        <td class="text-end fw-semibold small" id="bal-{{ $idx }}">
                            <span class="{{ $initBalance > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($initBalance) }}
                            </span>
                        </td>
                    </tr>
                    @php $idx++; @endphp
                    @endforeach

                @else
                    @foreach($allFeeTypes as $ft)
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input fee-check"
                                   id="ck-{{ $idx }}"
                                   name="fee_items[{{ $idx }}][checked]"
                                   value="1"
                                   onchange="toggleRow({{ $idx }})">
                        </td>
                        <td class="fee-item-col">
                            <input type="hidden" name="fee_items[{{ $idx }}][fee_type_id]" value="{{ $ft->id }}">
                            <input type="hidden" name="fee_items[{{ $idx }}][fee_name]" value="{{ $ft->name }}">
                            <label for="ck-{{ $idx }}" class="mb-0 small text-muted" id="lbl-{{ $idx }}">
                                {{ $ft->name }}
                            </label>
                        </td>
                        <td class="text-muted text-end small">—</td>
                        <td class="text-muted text-end small">—</td>
                        <td class="text-muted text-end small">—</td>
                        <td class="text-muted text-end small">—</td>
                        <td class="text-end">
                            <div class="input-group input-group-sm justify-content-end">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="fee_items[{{ $idx }}][amount]"
                                       class="form-control fee-amount text-end" id="amt-{{ $idx }}"
                                       value="0" min="0" step="1" style="max-width:72px;" disabled
                                       data-remaining="0"
                                       oninput="updateBalance({{ $idx }})">
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="input-group input-group-sm justify-content-end">
                                <span class="input-group-text" style="background:#fef2f2;border-color:#f87171;color:#dc2626;font-weight:600;">₹</span>
                                <input type="number" name="fee_items[{{ $idx }}][fine]"
                                       class="form-control fee-fine text-end" id="fine-{{ $idx }}"
                                       value="0" data-initial-fine="0" min="0" step="1" style="max-width:72px;border-color:#f87171;font-weight:600;color:#dc2626;" disabled
                                       oninput="onFineChange({{ $idx }})"
                                       onblur="normalizeRowValues({{ $idx }})">
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="input-group input-group-sm justify-content-end">
                                <span class="input-group-text text-warning">₹</span>
                                <input type="number" name="fee_items[{{ $idx }}][discount]"
                                       class="form-control fee-disc text-end" id="disc-{{ $idx }}"
                                       value="0" min="0" step="1" style="max-width:72px;" disabled
                                       oninput="updateBalance({{ $idx }})"
                                       onblur="normalizeRowValues({{ $idx }})">
                            </div>
                        </td>
                        <td class="text-muted text-end small" id="bal-{{ $idx }}">—</td>
                    </tr>
                    @php $idx++; @endphp
                    @endforeach
                @endif
                </tbody>
            </table>
            </div>{{-- end table-responsive --}}
            {{-- Custom Fee Row --}}
            <div id="manualRows" class="px-3 pt-2"></div>
            <div class="px-3 pt-1 pb-2">
                @if(isset($staffCollectFeeTypeIds) && $staffCollectFeeTypeIds !== null)
                    <div class="small text-muted">
                        Custom fee items are disabled because fee item access is restricted for this staff member.
                    </div>
                @else
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addManualRow()">
                        <i class="bi bi-plus me-1"></i> Custom Fee Item
                    </button>
                @endif
            </div>

            {{-- Summary --}}
            <div class="row g-2 align-items-stretch mb-3 px-3 pb-1">
                <div class="col-6 col-xl-3">
                    <div class="p-2 rounded text-center fee-summary-card d-flex flex-column justify-content-center" style="background:#f1f5f9;">
                        <div style="font-size:10px;color:#64748b;">Total Paid</div>
                        <div class="fw-bold text-primary small" id="totalCollectDisplay">₹ 0</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="p-2 rounded text-center fee-summary-card d-flex flex-column justify-content-center" style="background:#fef2f2;">
                        <div style="font-size:10px;color:#b91c1c;">Total Fine</div>
                        <div class="fw-bold text-danger small" id="totalFineDisplay">₹ 0</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="p-2 rounded text-center fee-summary-card d-flex flex-column justify-content-center" style="background:#fffbeb;">
                        <div style="font-size:10px;color:#92400e;">Total Discount</div>
                        <div class="fw-bold text-warning small" id="totalDiscDisplay">₹ 0</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="p-3 rounded text-center text-white fw-bold fee-summary-card d-flex flex-column justify-content-center"
                         style="background:#1e293b;">
                        <div style="font-size:12px;opacity:.75;">Total Due</div>
                        <div class="fs-5" id="totalDisplay">₹ 0</div>
                    </div>
                </div>
            </div>
            {{-- Payment Details --}}
            <div class="row g-3 px-3 pb-3">
                {{-- Semester Selector --}}
                @php
                    $currentSem = $student->current_semester ?? $activeSession?->current_semester ?? 1;
                    // Odd session â†’ Sem 1,3,5... | Even session â†’ Sem 2,4,6...
                    $availableSems = [$currentSem];
                @endphp
                <div class="col-12">
                    <label class="form-label small fw-semibold">
                        Fee Kis Semester Ke Liye? <span class="text-danger">*</span>
                    </label>
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach($availableSems as $sem)
                        <div class="form-check form-check-inline border rounded px-3 py-2"
                             style="cursor:pointer;">
                            <input class="form-check-input" type="radio"
                                   name="semester" id="sem{{ $sem }}"
                                   value="{{ $sem }}"
                                   {{ $loop->first ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="sem{{ $sem }}">
                                Semester {{ $sem }}
                                @if($sem == $currentSem)
                                    <span class="badge bg-primary ms-1" style="font-size:9px;">Current</span>
                                @endif
                            </label>
                        </div>
                        @endforeach
                    </div>
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        Session: {{ $activeSession?->name }} — Current semester fee and carry-forward dues will be collected together.
                    </div>
                </div>

                @error('fee_items')
                <div class="col-12">
                    <div class="alert alert-danger py-2 small border-0 mb-0">
                        <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                    </div>
                </div>
                @enderror

                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                    <select name="payment_mode" id="paymentMode"
                            class="form-select form-select-sm @error('payment_mode') is-invalid @enderror"
                            onchange="handlePaymentModeChange()" required>
                        @foreach($allowedPaymentModes as $mode)
                        <option value="{{ $mode }}" {{ old('payment_mode', $allowedPaymentModes[0] ?? '') === $mode ? 'selected' : '' }}>
                            {{ $paymentModeLabels[$mode] ?? strtoupper($mode) }}
                        </option>
                        @endforeach
                    </select>
                    @error('payment_mode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6" id="bankAccountWrap" style="display:none;">
                    <label class="form-label small fw-semibold">Bank Account</label>
                    <select name="bank_account_id" id="bankAccountSelect"
                            class="form-select form-select-sm @error('bank_account_id') is-invalid @enderror">
                        <option value="">Select Bank Account</option>
                        @foreach($bankAccounts as $ba)
                        <option value="{{ $ba->id }}"
                                data-modes="{{ $bankModeOverride ?? ($ba->allowed_payment_modes ?? 'cash,upi,online,cheque,dd,neft,rtgs') }}"
                                {{ old('bank_account_id') == $ba->id ? 'selected' : '' }}>
                            {{ $ba->bank_name }}
                            @if($ba->account_number) — {{ $ba->account_number }} @endif
                        </option>
                        @endforeach
                    </select>
                    @error('bank_account_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" id="paymentDateLabel">Payment Date <span class="text-danger">*</span></label>
                    @if($lockPaymentDate)
                    <input type="hidden" name="payment_date" value="{{ $defaultPaymentDate }}">
                    <div id="cashDateDisplay">
                        <input type="text" class="form-control form-control-sm"
                               value="{{ \Carbon\Carbon::parse($defaultPaymentDate)->format('d-m-Y') }}" readonly>
                        <div class="form-text">Auto set to today's date for this panel.</div>
                    </div>
                    <div id="nonCashDatetimeDisplay" style="display:none;">
                        <input type="datetime-local" name="payment_datetime" id="paymentDatetime"
                               class="form-control form-control-sm @error('payment_datetime') is-invalid @enderror"
                               value="{{ old('payment_datetime', $defaultPaymentDatetime) }}">
                        @error('payment_datetime')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="form-text"><i class="bi bi-clock me-1"></i>Enter the actual payment time.</div>
                        @enderror
                    </div>
                    @else
                    <div id="cashDateDisplay">
                        <input type="date" name="payment_date" id="cashDateInput"
                               class="form-control form-control-sm @error('payment_date') is-invalid @enderror"
                               value="{{ old('payment_date', $defaultPaymentDate) }}" required>
                        @error('payment_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div id="nonCashDatetimeDisplay" style="display:none;">
                        <input type="datetime-local" name="payment_datetime" id="paymentDatetime"
                               class="form-control form-control-sm @error('payment_datetime') is-invalid @enderror"
                               value="{{ old('payment_datetime', $defaultPaymentDatetime) }}"
                               disabled>
                        @error('payment_datetime')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="form-text"><i class="bi bi-clock me-1"></i>Enter the actual payment date and time.</div>
                        @enderror
                    </div>
                    @endif
                </div>
                <div class="col-md-6" id="refField" style="display:none;">
                    <label class="form-label small fw-semibold" id="refLabel">Transaction Ref / UTR <span class="text-danger">*</span></label>
                    <input type="text" name="transaction_ref" id="transactionRefInput"
                           class="form-control form-control-sm @error('transaction_ref') is-invalid @enderror"
                           placeholder="Reference number..."
                           value="{{ old('transaction_ref') }}">
                    @error('transaction_ref')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6" id="bankField" style="display:none;">
                    <label class="form-label small fw-semibold">Bank Name</label>
                    <input type="text" name="bank_name" class="form-control form-control-sm"
                           placeholder="Bank name..."
                           value="{{ old('bank_name') }}">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Remarks (optional)</label>
                    <input type="text" name="remarks" class="form-control form-control-sm"
                           placeholder="Add a note..."
                           value="{{ old('remarks') }}">
                </div>
            </div>

            <div id="discountLimitWarn" class="alert alert-danger py-2 small mx-3 mt-3 mb-0" style="display:none;"></div>

            <div class="d-flex gap-2 mt-4 px-3 pb-3 flex-wrap">
                <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-check-circle me-2"></i>Collect & Print Receipt
                </button>
                @if($isAdmissionFeeFlow ?? false)
                @php
                    $skipFeeRoute = match(true) {
                        auth()->guard('staff')->check()   => 'staff.admissions.skip-fee-payment',
                        auth()->guard('center')->check()  => 'center.admissions.skip-fee-payment',
                        auth()->guard('partner')->check() => 'partner.admissions.skip-fee-payment',
                        default                           => 'admissions.skip-fee-payment',
                    };
                @endphp
                <button type="button" class="btn btn-warning px-4"
                        data-bs-toggle="modal" data-bs-target="#skipFeeModal">
                    <i class="bi bi-forward me-2"></i>Skip & Submit
                </button>

                {{-- Skip Fee Confirmation Modal --}}
                <div class="modal fade" id="skipFeeModal" tabindex="-1" aria-labelledby="skipFeeModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold" id="skipFeeModalLabel">
                                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>Skip Fee Collection?
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body pt-2">
                                <p class="mb-1">The student's fee will <strong>not</strong> be collected now.</p>
                                <p class="text-muted small mb-0">You can collect the fee later from the student's profile.</p>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    Cancel
                                </button>
                                <a href="{{ route($skipFeeRoute, $student->id) }}" class="btn btn-warning px-4">
                                    <i class="bi bi-forward me-2"></i>Yes, Skip & Submit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <a href="{{ $feeBackUrl }}" class="btn btn-outline-secondary">Cancel</a>
            </div>

            </form>
            </div>
        </div>
    @endif
    </div>

</div>
@endif

<script>
let manualCount = {{ $idx ?? 0 }};
let oneTimePayLocked = false;
const staffMaxDiscountPct = {{ isset($staffMaxDiscount) && $staffMaxDiscount !== null ? (int)$staffMaxDiscount : 'null' }};

@php
// Hierarchy data for JS — only items with remaining > 0
$hierarchyData = [];
$jsItems = $feeBreakup['grouped_items'] ?? $feeBreakup['items'] ?? [];
foreach ($jsItems as $i => $item) {
    $feeName   = $item['label'];
    $totalFee  = (float) $item['amount'];
    $paidData  = $alreadyPaid->get($feeName);
    $paidAmt   = (float) ($paidData?->paid_total    ?? $paidData ?? 0);
    $paidDisc  = (float) ($paidData?->discount_total ?? 0);
    $prevFine  = (float) ($fineByFee[$feeName] ?? 0);
    $rem       = isset($pendingByFee[$feeName])
        ? (float) $pendingByFee[$feeName]
        : max(0, $totalFee + $prevFine - $paidAmt - $paidDisc);
    if ($rem > 0) {
        $hierarchyData[] = ['idx' => $i, 'remaining' => $rem];
    }
}
@endphp
@php
    $globalAllowedModes = $allowedPaymentModes ?? ['cash', 'upi', 'online', 'cheque', 'dd', 'neft', 'rtgs'];
@endphp
const hierarchyItems = @json($hierarchyData);
const totalDueCap    = {{ $headerDue ?? 0 }};
const globallyAllowedModes = @json($globalAllowedModes);
const paymentModeLabels = {
    cash: 'Cash',
    upi: 'UPI',
    online: 'Online Transfer',
    cheque: 'Cheque',
    dd: 'DD',
    neft: 'NEFT',
    rtgs: 'RTGS',
};

function getNumber(value) {
    return parseFloat(value) || 0;
}

function normalizeMoneyInput(input) {
    if (!input) return 0;

    const raw = String(input.value ?? '').trim();
    if (raw === '' || raw === '-' || raw === '.' || raw === '-.') {
        input.value = '0';
        return 0;
    }

    const parsed = parseFloat(raw);
    if (!Number.isFinite(parsed) || parsed < 0) {
        input.value = '0';
        return 0;
    }

    input.value = String(parsed);
    return parsed;
}

function getEnabledTotal(selector) {
    let total = 0;
    document.querySelectorAll(selector).forEach((input) => {
        if (!input.disabled) total += getNumber(input.value);
    });
    return total;
}

function getRowElements(i) {
    return {
        checkbox: document.getElementById('ck-' + i),
        amount: document.getElementById('amt-' + i),
        fine: document.getElementById('fine-' + i),
        discount: document.getElementById('disc-' + i),
        balance: document.getElementById('bal-' + i),
        label: document.getElementById('lbl-' + i),
    };
}

function getRowState(i) {
    const els = getRowElements(i);
    const baseRemaining = getNumber(els.amount?.dataset.remaining);
    // pendingFine = fine already charged in a prior invoice but not yet fully collected (display only)
    const pendingFine = getNumber(els.amount?.dataset.pendingFine);
    const maxDiscPct = parseFloat(els.amount?.dataset.maxDisc ?? 100);
    const collect = normalizeMoneyInput(els.amount);
    const fine = normalizeMoneyInput(els.fine); // new fine being added today
    const initialFine = getNumber(els.fine?.dataset.initialFine);
    const discount = normalizeMoneyInput(els.discount);
    const payable = Math.max(0, baseRemaining + pendingFine + fine);

    return {
        ...els,
        baseRemaining,
        pendingFine,
        maxDiscPct,
        collect,
        fine,
        initialFine,
        discount,
        payable,
    };
}

function normalizeRowValues(i) {
    const state = getRowState(i);
    if (!state.amount) return;
    updateBalance(i);
}

function updateRowDisplay(i, balance) {
    const { balance: balanceEl } = getRowElements(i);
    if (!balanceEl) return;
    balanceEl.dataset.balval = balance;  // store raw value for calcTotal
    balanceEl.innerHTML = `<span class="${balance > 0 ? 'text-danger' : 'text-success'}">${balance.toLocaleString('en-IN')}</span>`;
}

function getDynamicCollectCap() {
    let fineDelta = 0;
    document.querySelectorAll('.fee-fine').forEach((input) => {
        if (input.disabled) return;
        fineDelta += getNumber(input.value) - getNumber(input.dataset.initialFine);
    });

    return Math.max(0, totalDueCap + fineDelta - getEnabledTotal('.fee-disc'));
}

function lockAllCollectFields() {
    oneTimePayLocked = true;
    hierarchyItems.forEach(item => {
        const el = document.getElementById('amt-' + item.idx);
        if (el && !el.disabled) {
            el.setAttribute('readonly', '');
            el.style.backgroundColor = '#f1f5f9';
            el.style.cursor = 'not-allowed';
            el.title = 'Set via one-time pay — change fine/discount or clear one-time pay and refill';
        }
    });
}

function unlockAllCollectFields() {
    oneTimePayLocked = false;
    hierarchyItems.forEach(item => {
        const el = document.getElementById('amt-' + item.idx);
        if (el) {
            el.removeAttribute('readonly');
            el.style.backgroundColor = '';
            el.style.cursor = '';
            el.title = '';
        }
    });
}

function applyInstallmentAmount(amount) {
    const el = document.getElementById('oneTimePay');
    if (!el) return;
    el.value = amount;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    applyOneTimePay();
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function applyOneTimePay() {
    let amount = getNumber(document.getElementById('oneTimePay')?.value);
    if (amount <= 0) return;

    amount = Math.min(amount, getDynamicCollectCap());

    hierarchyItems.forEach((item) => {
        const els = getRowElements(item.idx);
        const state = getRowState(item.idx);
        if (!state.checkbox || !state.amount) return;

        const keepChecked = item.remaining > 0 || state.discount > 0 || state.fine > 0;
        state.checkbox.checked = keepChecked;
        state.amount.disabled = !keepChecked;
        if (els.discount) els.discount.disabled = !keepChecked;
        if (els.fine) els.fine.disabled = !keepChecked;
        state.amount.value = 0;
        updateBalance(item.idx);
    });

    for (const item of hierarchyItems) {
        if (amount <= 0) break;
        const els = getRowElements(item.idx);
        const state = getRowState(item.idx);
        if (!state.checkbox || !state.amount) continue;

        state.checkbox.checked = true;
        state.amount.disabled = false;
        if (els.discount) els.discount.disabled = false;
        if (els.fine) els.fine.disabled = false;

        const rowCap = Math.max(0, item.remaining + (state.fine - state.initialFine) - state.discount);
        const toFill = Math.min(amount, rowCap);
        state.amount.value = toFill;
        amount -= toFill;
        updateBalance(item.idx);
    }

    // Rows with no amount allocated stay active if they carry fine/discount adjustments.
    hierarchyItems.forEach(item => {
        const els = getRowElements(item.idx);
        if (!els.checkbox || !els.amount) return;
        const hasAdjustment = getNumber(els.fine?.value) > 0 || getNumber(els.discount?.value) > 0;
        if (getNumber(els.amount.value) <= 0 && !hasAdjustment) {
            els.checkbox.checked = false;
            els.amount.disabled  = true;
            els.amount.value     = 0;
            if (els.fine)     { els.fine.disabled = true;     els.fine.value = 0; }
            if (els.discount) { els.discount.disabled = true; els.discount.value = 0; }
            updateBalance(item.idx);
        } else if (hasAdjustment) {
            els.checkbox.checked = true;
            els.amount.disabled = false;
            if (els.fine) els.fine.disabled = false;
            if (els.discount) els.discount.disabled = false;
            updateBalance(item.idx);
        }
    });

    calcTotal();
    lockAllCollectFields();
}

function clearAllFeeInputs() {
    const oneTimeEl = document.getElementById('oneTimePay');
    if (oneTimeEl) {
        oneTimeEl.value = '';
    }

    unlockAllCollectFields();

    hierarchyItems.forEach((item) => {
        const els = getRowElements(item.idx);
        if (!els.checkbox || !els.amount) return;

        const shouldEnable = item.remaining > 0;
        els.checkbox.checked = shouldEnable;
        els.amount.disabled = !shouldEnable;
        els.amount.value = 0;

        if (els.fine) {
            els.fine.disabled = !shouldEnable;
            els.fine.value = 0;
        }

        if (els.discount) {
            els.discount.disabled = !shouldEnable;
            els.discount.value = 0;
        }

        els.label?.classList.toggle('text-muted', !shouldEnable);
        updateBalance(item.idx);
    });

    document.querySelectorAll('#manualRows .fee-amount, #manualRows .fee-fine, #manualRows .fee-disc').forEach((input) => {
        input.value = 0;
    });

    document.querySelectorAll('#manualRows .fee-check').forEach((checkbox) => {
        checkbox.checked = false;
    });

    calcTotal();
}

function onDiscChange(i) {
    const els = getRowElements(i);
    const state = getRowState(i);
    if (!state.amount || !els.discount) return;

    let discount = state.discount;

    // Cap 1: discount cannot exceed payable (remaining + fine) for this row
    if (discount > state.payable) {
        discount = state.payable;
        els.discount.value = discount;
    }

    // Cap 2: per-row discount % limit (set by admin per fee item or global fallback)
    if (state.maxDiscPct < 100 && state.payable > 0) {
        const maxAllowed = Math.floor(state.payable * state.maxDiscPct / 100);
        if (discount > maxAllowed) {
            discount = maxAllowed;
            els.discount.value = discount;
        }
    }

    updateBalance(i);
    if (oneTimePayLocked) redistributeOneTimePay();
}

function onFineChange(i) {
    const els = getRowElements(i);
    const state = getRowState(i);
    if (!state.amount || !els.fine || state.amount.disabled) return;

    if (state.fine < 0) {
        els.fine.value = 0;
    }

    // Never auto-change collect when user manually enters fine.
    // updateBalance will cap collect if it exceeds the new payable.
    updateBalance(i);
    if (oneTimePayLocked) redistributeOneTimePay();
}

function redistributeOneTimePay() {
    const oneTimeEl = document.getElementById('oneTimePay');
    if (!oneTimePayLocked || !oneTimeEl || !oneTimeEl.value) return;

    let targetTotal = getNumber(oneTimeEl.value);
    if (targetTotal <= 0) return;

    targetTotal = Math.min(targetTotal, getDynamicCollectCap());

    hierarchyItems.forEach((item) => {
        const els = getRowElements(item.idx);
        const state = getRowState(item.idx);
        if (!state.checkbox || !state.amount) return;

        const keepChecked = item.remaining > 0 || state.discount > 0 || state.fine > 0;
        state.checkbox.checked = keepChecked;
        state.amount.disabled = !keepChecked;
        if (els.discount) els.discount.disabled = !keepChecked;
        if (els.fine) els.fine.disabled = !keepChecked;
        state.amount.value = 0;
        updateBalance(item.idx);
    });

    let amount = targetTotal;
    for (const item of hierarchyItems) {
        if (amount <= 0) break;
        const els = getRowElements(item.idx);
        const state = getRowState(item.idx);
        if (!state.checkbox || !state.amount) continue;

        state.checkbox.checked = true;
        state.amount.disabled = false;
        if (els.discount) els.discount.disabled = false;
        if (els.fine) els.fine.disabled = false;

        const rowCap = Math.max(0, item.remaining + (state.fine - state.initialFine) - state.discount);
        const toFill = Math.min(amount, rowCap);
        state.amount.value = toFill;
        amount -= toFill;
        updateBalance(item.idx);
    }

    // Rows with no amount allocated stay active if they carry fine/discount adjustments.
    hierarchyItems.forEach(item => {
        const els = getRowElements(item.idx);
        if (!els.checkbox || !els.amount) return;
        const hasAdjustment = getNumber(els.fine?.value) > 0 || getNumber(els.discount?.value) > 0;
        if (getNumber(els.amount.value) <= 0 && !hasAdjustment) {
            els.checkbox.checked = false;
            els.amount.disabled  = true;
            els.amount.value     = 0;
            if (els.fine)     { els.fine.disabled = true;     els.fine.value = 0; }
            if (els.discount) { els.discount.disabled = true; els.discount.value = 0; }
            updateBalance(item.idx);
        } else if (hasAdjustment) {
            els.checkbox.checked = true;
            els.amount.disabled = false;
            if (els.fine) els.fine.disabled = false;
            if (els.discount) els.discount.disabled = false;
            updateBalance(item.idx);
        }
    });

    calcTotal();
    if (oneTimePayLocked) lockAllCollectFields();
}

function toggleRow(i) {
    // Use getRowElements (DOM elements), NOT getRowState (fine/discount are numbers there)
    const els = getRowElements(i);
    if (!els.checkbox || !els.amount) return;

    const enabled = els.checkbox.checked;
    els.amount.disabled   = !enabled;
    if (els.fine)     els.fine.disabled     = !enabled;
    if (els.discount) els.discount.disabled = !enabled;
    if (enabled && oneTimePayLocked) {
        els.amount.setAttribute('readonly', '');
        els.amount.style.backgroundColor = '#f1f5f9';
        els.amount.style.cursor = 'not-allowed';
    } else if (!enabled) {
        els.amount.removeAttribute('readonly');
        els.amount.style.backgroundColor = '';
        els.amount.style.cursor = '';
    }

    if (!enabled) {
        els.amount.value = 0;
        if (els.fine)     els.fine.value     = 0;
        if (els.discount) els.discount.value = 0;
    }

    els.label?.classList.toggle('text-muted', !enabled);
    updateBalance(i);
}

function updateBalance(i) {
    const els = getRowElements(i);
    const state = getRowState(i);
    if (!state.amount || !state.balance) return;

    let collect = state.collect;
    let discount = state.discount;
    // payable = base fee remaining + already-charged unpaid fine + any new fine added today
    const payable = Math.max(0, state.baseRemaining + state.pendingFine + state.fine);

    if (discount > payable) {
        discount = payable;
        if (els.discount) els.discount.value = discount;
    }

    // Re-apply per-row % cap in case fine changed and lowered the allowed max
    if (state.maxDiscPct < 100 && payable > 0) {
        const maxAllowed = Math.floor(payable * state.maxDiscPct / 100);
        if (discount > maxAllowed) {
            discount = maxAllowed;
            if (els.discount) els.discount.value = discount;
        }
    }

    if (collect + discount > payable) {
        collect = Math.max(0, payable - discount);
        state.amount.value = collect;
    }

    // Row balance = base remaining + pending fine (prior) + new fine - collect - discount
    const balance = Math.max(0, state.baseRemaining + state.pendingFine + state.fine - collect - discount);
    updateRowDisplay(i, balance);
    calcTotal();
}

function calcTotal() {
    const totalCollect = getEnabledTotal('.fee-amount');
    const totalFine    = getEnabledTotal('.fee-fine');
    const totalDisc    = getEnabledTotal('.fee-disc');

    // Sum per-row balances — prevents cross-row absorption of excess discounts
    let totalDue = 0;
    document.querySelectorAll('[id^="bal-"]').forEach(el => {
        const val = parseFloat(el.dataset.balval);
        if (!isNaN(val) && val > 0) totalDue += val;
    });

    const tc = document.getElementById('totalCollectDisplay');
    const tf = document.getElementById('totalFineDisplay');
    const td = document.getElementById('totalDiscDisplay');
    const tp = document.getElementById('totalDisplay');
    if (tc) tc.textContent = '₹ ' + totalCollect.toLocaleString('en-IN');
    if (tf) tf.textContent = totalFine > 0 ? '₹ ' + totalFine.toLocaleString('en-IN') : '₹ 0';
    if (td) td.textContent = totalDisc > 0 ? '₹ ' + totalDisc.toLocaleString('en-IN') : '₹ 0';
    if (tp) tp.textContent = '₹ ' + totalDue.toLocaleString('en-IN');

    const warnEl = document.getElementById('discountLimitWarn');
    if (warnEl) warnEl.style.display = 'none';

    checkWalletTokenWarning(totalCollect);
}

function checkWalletTokenWarning(amount) {
    const banner = document.getElementById('walletTokenWarnBanner');
    if (!banner || typeof walletRemainingTokens === 'undefined' || walletRemainingTokens <= 0) return;
    if (amount > 0 && amount > walletRemainingTokens) {
        banner.classList.remove('d-none');
        banner.classList.add('d-flex');
        const msgEl = document.getElementById('walletTokenWarnMsg');
        if (msgEl) {
            msgEl.textContent = 'Only ₹' + walletRemainingTokens.toLocaleString('en-IN') + ' tokens remaining. Reduce collection amount to ₹' + walletRemainingTokens.toLocaleString('en-IN') + ' or less.';
        }
    } else {
        banner.classList.add('d-none');
        banner.classList.remove('d-flex');
    }
}
function ensurePaymentModeOptions() {
    const pmSel = document.getElementById('paymentMode');
    if (!pmSel) return;

    globallyAllowedModes.forEach(mode => {
        if ([...pmSel.options].some(option => option.value === mode)) return;
        const option = document.createElement('option');
        option.value = mode;
        option.textContent = paymentModeLabels[mode] || mode;
        pmSel.appendChild(option);
    });
}

function syncBankAccountOptions() {
    const mode = document.getElementById('paymentMode')?.value || 'cash';
    const wrap = document.getElementById('bankAccountWrap');
    const select = document.getElementById('bankAccountSelect');
    if (!wrap || !select) return;

    const showBankAccount = mode !== 'cash';
    wrap.style.display = showBankAccount ? 'block' : 'none';
    select.required = showBankAccount;

    let hasVisibleSelectedOption = !showBankAccount && !select.value;
    Array.from(select.options).forEach((option, index) => {
        if (index === 0) {
            option.hidden = false;
            return;
        }

        const modes = String(option.dataset.modes || '')
            .split(',')
            .map(value => value.trim())
            .filter(Boolean);
        const matchesMode = !showBankAccount || !modes.length || modes.includes(mode);
        option.hidden = !matchesMode;
        if (matchesMode && option.value === select.value) {
            hasVisibleSelectedOption = true;
        }
    });

    if (!hasVisibleSelectedOption) {
        select.value = '';
    }
}

// â”€â”€ Payment mode fields toggle â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function togglePaymentFields() {
    const mode = document.getElementById('paymentMode')?.value;
    const ref  = document.getElementById('refField');
    const bank = document.getElementById('bankField');
    const refInput = document.getElementById('transactionRefInput');
    const cashDisplay    = document.getElementById('cashDateDisplay');
    const nonCashDisplay = document.getElementById('nonCashDatetimeDisplay');
    const paymentDatetimeInput = document.getElementById('paymentDatetime');
    const paymentDateLabel = document.getElementById('paymentDateLabel');
    if (!ref) return;

    const isNonCash = mode !== 'cash';

    // Toggle datetime display for all panels
    if (cashDisplay && nonCashDisplay) {
        cashDisplay.style.display    = isNonCash ? 'none'  : 'block';
        nonCashDisplay.style.display = isNonCash ? 'block' : 'none';
        if (paymentDatetimeInput) paymentDatetimeInput.required = isNonCash;
    }

    // Admin (non-locked) path: toggle required + sync payment_date from datetime
    const cashDateInputEl = document.getElementById('cashDateInput');
    if (cashDateInputEl) {
        cashDateInputEl.required = !isNonCash;
        const _n = new Date();
        const _today = _n.getFullYear() + '-' + String(_n.getMonth()+1).padStart(2,'0') + '-' + String(_n.getDate()).padStart(2,'0');
        if (!isNonCash) {
            // Cash: lock to today only — no past, no future
            cashDateInputEl.min   = _today;
            cashDateInputEl.max   = _today;
            cashDateInputEl.value = _today;
        } else {
            // Non-cash: remove lock so any date can be entered
            cashDateInputEl.removeAttribute('min');
            cashDateInputEl.removeAttribute('max');
        }
        if (paymentDatetimeInput) {
            paymentDatetimeInput.disabled = !isNonCash;
            if (isNonCash) {
                const _t = String(_n.getHours()).padStart(2,'0') + ':' + String(_n.getMinutes()).padStart(2,'0');
                if (!paymentDatetimeInput.value) {
                    paymentDatetimeInput.value = _today + 'T' + _t;
                }
                paymentDatetimeInput.max = _today + 'T' + _t;
                cashDateInputEl.value = paymentDatetimeInput.value.split('T')[0];
            }
        }
    }
    // Locked panel (staff/center/partner): today's date only, any past time on today
    if (!cashDateInputEl && isNonCash && paymentDatetimeInput) {
        const _n = new Date();
        const _d = _n.getFullYear() + '-' + String(_n.getMonth()+1).padStart(2,'0') + '-' + String(_n.getDate()).padStart(2,'0');
        const _t = String(_n.getHours()).padStart(2,'0') + ':' + String(_n.getMinutes()).padStart(2,'0');
        if (!paymentDatetimeInput.value) {
            paymentDatetimeInput.value = _d + 'T' + _t;
        }
        paymentDatetimeInput.min = _d + 'T00:00';
        paymentDatetimeInput.max = _d + 'T' + _t;
    }
    if (paymentDateLabel) {
        paymentDateLabel.innerHTML = isNonCash
            ? 'Payment Date &amp; Time <span class="text-danger">*</span> <small class="text-muted fw-normal">(Actual payment time)</small>'
            : 'Payment Date <span class="text-danger">*</span>';
    }

    if (['cheque', 'dd'].includes(mode)) {
        ref.style.display  = 'block';
        bank.style.display = 'block';
        if (refInput) {
            refInput.placeholder = mode === 'cheque' ? 'Cheque number...' : 'DD number...';
            refInput.required    = true;
        }
        const lbl = ref.querySelector('label');
        if (lbl) lbl.innerHTML = (mode === 'cheque' ? 'Cheque No' : 'DD No') + ' <span class="text-danger">*</span>';
    } else if (['upi', 'online', 'neft', 'rtgs'].includes(mode)) {
        ref.style.display  = 'block';
        bank.style.display = 'none';
        if (refInput) {
            refInput.placeholder = 'Transaction Ref / UTR...';
            refInput.required    = true;
        }
        const lbl = ref.querySelector('label');
        if (lbl) lbl.innerHTML = 'Transaction Ref / UTR <span class="text-danger">*</span>';
    } else {
        ref.style.display  = 'none';
        bank.style.display = 'none';
        if (refInput) refInput.required = false;
    }
}

function handlePaymentModeChange() {
    syncBankAccountOptions();
    togglePaymentFields();
}

// â”€â”€ Add manual row â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function addManualRow() {
    const i = manualCount++;
    document.getElementById('manualRows').insertAdjacentHTML('beforeend', `
    <div class="row g-2 align-items-center mb-2" id="manual-${i}">
        <div class="col-1 text-center">
            <input type="checkbox" class="form-check-input" checked disabled>
            <input type="hidden" name="fee_items[${i}][checked]" value="1">
        </div>
        <input type="hidden" name="fee_items[${i}][fee_type_id]" value="">
        <input type="hidden" name="fee_items[${i}][is_custom]" value="1">
        <div class="col-5">
            <input type="text" name="fee_items[${i}][fee_name]"
                   class="form-control form-control-sm" placeholder="Fee name..." required>
        </div>
        <div class="col-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text">₹</span>
                <input type="number" name="fee_items[${i}][amount]"
                       class="form-control fee-amount" value="0" min="0" oninput="calcTotal()" style="max-width:92px;">
            </div>
        </div>
        <div class="col-2">
            <div class="input-group input-group-sm">
                <span class="input-group-text text-warning">₹</span>
                <input type="number" name="fee_items[${i}][discount]"
                       class="form-control fee-disc" value="0" min="0" oninput="calcTotal()" style="max-width:92px;">
            </div>
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="document.getElementById('manual-${i}').remove();calcTotal()">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>`);
}

// â”€â”€ Student Search â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Sync payment_date (admin path) + enforce today's date (locked panel) when datetime-local changes
document.getElementById('paymentDatetime')?.addEventListener('input', function () {
    const cashDateEl = document.getElementById('cashDateInput');
    if (cashDateEl && this.value) {
        cashDateEl.value = this.value.split('T')[0];
    }
    // Locked panel: if date was manually changed, silently reset to today
    if (!cashDateEl && this.value) {
        const _n = new Date();
        const _d = _n.getFullYear() + '-' + String(_n.getMonth()+1).padStart(2,'0') + '-' + String(_n.getDate()).padStart(2,'0');
        if (this.value.split('T')[0] !== _d) {
            this.value = _d + 'T' + (this.value.split('T')[1] || String(_n.getHours()).padStart(2,'0') + ':' + String(_n.getMinutes()).padStart(2,'0'));
        }
    }
});

// Unlock collect fields when oneTimePay is cleared
document.getElementById('oneTimePay')?.addEventListener('input', function () {
    unlockAllCollectFields();
    if (!this.value || getNumber(this.value) <= 0) {
        unlockAllCollectFields();
    }
});

let searchTimer;
function getSelectedSessionId() {
    const sel = document.getElementById('feeSessionSelect');
    return sel ? sel.value : '';
}

function runStudentSearch() {
    clearTimeout(searchTimer);
    const input = document.getElementById('studentSearch');
    if (!input) return;
    const q = input.value.trim();
    if (q.length < 2) { document.getElementById('searchResults').innerHTML = ''; return; }
    searchTimer = setTimeout(() => {
        const sessionId = getSelectedSessionId();
        const sessionParam = sessionId ? `&session_id=${sessionId}` : '';
        fetch(`${@json($feeSearchUrl)}?q=${encodeURIComponent(q)}${sessionParam}`)
            .then(r => r.json())
            .then(data => {
                const box = document.getElementById('searchResults');
                if (!data.length) {
                    box.innerHTML = '<div class="list-group-item text-muted small">No students found</div>';
                    return;
                }
                const sidParam = sessionId ? `&session_id=${sessionId}` : '';
                box.innerHTML = data.map(s => `
                    <a href="${@json($feeCreateUrl)}?student_id=${s.id}${sidParam}"
                       class="list-group-item list-group-item-action py-2">
                        <div class="fw-semibold small">${s.name}
                            <span class="text-muted fw-normal ms-1" style="font-size:10px;">${s.student_uid}</span>
                        </div>
                        <div class="text-muted" style="font-size:11px;">
                            ${s.course}${s.stream ? ' · ' + s.stream : ''} &bull; ${s.mobile}
                        </div>
                        ${(s.father_name || s.mother_name) ? `<div class="text-muted" style="font-size:10px;">
                            ${s.father_name ? 'F: ' + s.father_name : ''}${s.father_name && s.mother_name ? ' &bull; ' : ''}${s.mother_name ? 'M: ' + s.mother_name : ''}
                        </div>` : ''}
                    </a>`).join('');
            });
    }, 300);
}

document.getElementById('studentSearch')?.addEventListener('input', runStudentSearch);

document.getElementById('feeSessionSelect')?.addEventListener('change', function () {
    document.getElementById('searchResults').innerHTML = '';
    runStudentSearch();
});

// â”€â”€ Init â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Initialize per-row balances so data-balval is set before calcTotal runs
document.querySelectorAll('[id^="bal-"]').forEach(el => {
    const idx = parseInt(el.id.replace('bal-', ''));
    updateBalance(idx);
});
calcTotal();
ensurePaymentModeOptions();
handlePaymentModeChange();

// Scroll to error banner if present
const errorBanner = document.getElementById('formErrorBanner');
if (errorBanner) {
    errorBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// ── Library Fine Modal ────────────────────────────────────────────────
@if(isset($libraryFineData) && $libraryFineData)
(function () {
    const cashModes    = ['cash'];
    const nonCashModes = ['upi', 'online', 'neft', 'cheque', 'dd'];
    const refModes     = ['upi', 'online', 'neft', 'cheque', 'dd'];

    // ── Recalculate total from per-book inputs ──
    function recalcLibTotal() {
        let total = 0;
        document.querySelectorAll('.lib-fine-input').forEach(inp => {
            const val = parseFloat(inp.value) || 0;
            const max = parseFloat(inp.dataset.max) || 0;
            inp.classList.toggle('is-invalid', val < 0 || val > max);
            total += Math.min(val, max);
        });
        const fmt = '₹ ' + total.toFixed(2);
        const td  = document.getElementById('libFineTotalDisplay');
        const ft  = document.getElementById('libFineTotalFooter');
        if (td) td.textContent = fmt;
        if (ft) ft.textContent = fmt;
    }

    document.querySelectorAll('.lib-fine-input').forEach(inp => {
        inp.addEventListener('input', recalcLibTotal);
    });

    // ── Payment mode toggle ──
    document.getElementById('libFinePayMode')?.addEventListener('change', function () {
        const mode       = this.value;
        const isNonCash  = nonCashModes.includes(mode);
        const needsRef   = refModes.includes(mode);
        const dateWrap   = document.getElementById('libFineDateWrap');
        const dtWrap     = document.getElementById('libFineDatetimeWrap');
        const refWrap    = document.getElementById('libFineRefWrap');
        const bankWrap   = document.getElementById('libFineBankWrap');

        dateWrap?.classList.toggle('d-none', isNonCash);
        dtWrap?.classList.toggle('d-none', !isNonCash);
        refWrap?.classList.toggle('d-none', !needsRef);
        bankWrap?.classList.toggle('d-none', !isNonCash);
    });

    // ── Submit via AJAX ──
    document.getElementById('libFineSubmitBtn')?.addEventListener('click', function () {
        const btn     = this;
        const spinner = document.getElementById('libFineSubmitSpinner');
        const icon    = document.getElementById('libFineSubmitIcon');
        const alert   = document.getElementById('libFineAlert');

        // Collect items
        const items = [];
        let totalAmt = 0;
        let hasError = false;

        document.querySelectorAll('.lib-fine-input').forEach(inp => {
            const amt = parseFloat(inp.value) || 0;
            const max = parseFloat(inp.dataset.max) || 0;
            if (amt < 0 || amt > max + 0.001) {
                inp.classList.add('is-invalid');
                hasError = true;
                return;
            }
            if (amt > 0) {
                items.push({ transaction_id: inp.dataset.txnId, amount: amt.toFixed(2) });
                totalAmt += amt;
            }
        });

        if (hasError) {
            showLibAlert('danger', 'Some amounts are invalid. Amount cannot exceed the maximum pending amount.');
            return;
        }
        if (items.length === 0 || totalAmt <= 0) {
            showLibAlert('danger', 'Please enter an amount for at least one book.');
            return;
        }

        const mode      = document.getElementById('libFinePayMode')?.value || 'cash';
        const isNonCash = nonCashModes.includes(mode);
        const needsRef  = refModes.includes(mode);

        const payDate = isNonCash
            ? null
            : (document.getElementById('libFineDate')?.value || '');
        const payDt   = isNonCash
            ? (document.getElementById('libFineDatetime')?.value || '')
            : null;
        const ref     = document.getElementById('libFineRef')?.value?.trim() || '';

        if (!isNonCash && !payDate) {
            showLibAlert('danger', 'Payment date is required.');
            return;
        }
        if (isNonCash && !payDt) {
            showLibAlert('danger', 'Payment date & time is required.');
            return;
        }
        if (needsRef && !ref) {
            showLibAlert('danger', 'Transaction reference is required.');
            return;
        }

        // Build FormData
        const fd = new FormData();
        fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}');
        items.forEach((item, i) => {
            fd.append(`items[${i}][transaction_id]`, item.transaction_id);
            fd.append(`items[${i}][amount]`,         item.amount);
        });
        fd.append('payment_mode', mode);
        if (payDate)  fd.append('payment_date',     payDate);
        if (payDt)    fd.append('payment_datetime',  payDt);
        if (ref)      fd.append('transaction_ref',   ref);

        const bankId  = document.getElementById('libFineBank')?.value;
        const receipt = document.getElementById('libFineReceipt')?.value?.trim();
        const remarks = document.getElementById('libFineRemarks')?.value?.trim();
        if (bankId)  fd.append('bank_account_id', bankId);
        if (receipt) fd.append('receipt_no',      receipt);
        if (remarks) fd.append('remarks',          remarks);

        // Submit
        btn.disabled = true;
        spinner?.classList.remove('d-none');
        icon?.classList.add('d-none');
        alert?.classList.add('d-none');

        fetch(btn.dataset.url, { method: 'POST', body: fd })
            .then(res => {
                if (res.redirected || res.ok) {
                    // Success — reload page to refresh Total Due
                    window.location.reload();
                } else {
                    return res.text().then(text => {
                        // Try parsing Laravel validation error
                        try {
                            const json = JSON.parse(text);
                            const msgs = Object.values(json.errors || {}).flat().join(' ');
                            showLibAlert('danger', msgs || 'Collection failed. Please try again.');
                        } catch {
                            showLibAlert('danger', 'Collection failed. Please try again.');
                        }
                        btn.disabled = false;
                        spinner?.classList.add('d-none');
                        icon?.classList.remove('d-none');
                    });
                }
            })
            .catch(() => {
                showLibAlert('danger', 'Network error. Please check your connection and try again.');
                btn.disabled = false;
                spinner?.classList.add('d-none');
                icon?.classList.remove('d-none');
            });
    });

    function showLibAlert(type, msg) {
        const el = document.getElementById('libFineAlert');
        if (!el) return;
        el.className = `alert alert-${type} py-2 small`;
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    recalcLibTotal();
})();
@endif
</script>
@endsection
