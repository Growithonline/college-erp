@php
    $admissionLayout = auth()->guard('staff')->check()
        ? 'staff.layout'
        : (auth()->guard('center')->check()
            ? 'center.layout'
            : (auth()->guard('partner')->check() ? 'partner.layout' : 'institute.layout'));
    $admissionRoutePrefix = auth()->guard('staff')->check()
        ? 'staff.admissions'
        : (auth()->guard('center')->check()
            ? 'center.admissions'
            : (auth()->guard('partner')->check() ? 'partner.admissions' : 'admissions'));
    $feeRoutePrefix = auth()->guard('staff')->check()
        ? 'staff.fee'
        : (auth()->guard('center')->check()
            ? 'center.fee'
            : (auth()->guard('partner')->check() ? 'partner.fee' : 'fee'));
    $admissionListUrl = auth()->guard('staff')->check()
        ? route('staff.admissions.index')
        : (auth()->guard('center')->check()
            ? route('center.students.index')
            : (auth()->guard('partner')->check() ? route('partner.students.index') : route('admissions.index')));
    $lockPaymentDate = auth()->guard('staff')->check()
        || auth()->guard('center')->check()
        || auth()->guard('partner')->check();
    $defaultPaymentDate = now()->toDateString();
    $paymentModeLabels = [
        'cash' => 'Cash',
        'upi' => 'UPI',
        'online' => 'Online',
        'cheque' => 'Cheque',
        'dd' => 'DD',
        'neft' => 'NEFT',
        'rtgs' => 'RTGS',
    ];
    $allowedPaymentModes = $allowedPaymentModes ?? array_keys($paymentModeLabels);
@endphp
@extends($admissionLayout)
@section('title', 'Fee Payment')
@section('breadcrumb', 'Admissions / Fee Payment')

@section('content')

{{-- Progress --}}
<div class="mb-3">
    <div class="d-flex align-items-center gap-2 small text-muted">
        <span class="badge bg-success">✓ Form Filled</span>
        <div style="height:2px;width:40px;background:#16a34a;"></div>
        <span class="badge bg-success">✓ Preview</span>
        <div style="height:2px;width:40px;background:#16a34a;"></div>
        <span class="badge bg-primary">3 Fee Payment</span>
        <div style="height:2px;width:40px;background:#cbd5e1;"></div>
        <span class="badge bg-secondary">4 Print</span>
    </div>
</div>

{{-- Student Info Header --}}
<div class="card border-0 shadow-sm mb-3"
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
                    {{ $student->stream->course->name ?? '' }} — {{ $student->stream->name ?? '' }}
                    &nbsp;|&nbsp; {{ $student->coursePart?->year_label ?? '' }}
                </div>
                <div class="opacity-75 small">
                    📱 {{ $student->mobile }} &nbsp;|&nbsp; Session: {{ $student->session->name ?? '' }}
                </div>
                @if($student->father_name || $student->mother_name)
                <div class="opacity-75 small">
                    @if($student->father_name)
                        👨 {{ $student->father_name }}
                    @endif
                    @if($student->father_name && $student->mother_name)
                        &nbsp;|&nbsp;
                    @endif
                    @if($student->mother_name)
                        👩 {{ $student->mother_name }}
                    @endif
                </div>
                @endif
            </div>
            <div class="col-auto text-end">
                @if($walletSummary['balance'] < 0)
                    <div style="font-size:13px;opacity:.75;">Total Due</div>
                    <div class="fw-bold fs-4 text-warning">₹ {{ number_format(abs($walletSummary['balance']), 2) }}</div>
                @else
                    <span class="badge bg-success fs-6">✓ No Dues</span>
                @endif
            </div>
        </div>
    </div>
</div>

@php
    // Build grouped fee items:
    // — Subject fees (e.g. "Geography — Subject Fee") combined into one row
    // — Practical fees (e.g. "Geography — Practical Fee") kept SEPARATE per subject
    $groupedItems = collect();
    $subjectTotal = 0;
    $pfType = $allFeeTypes->first(fn($ft) => stripos($ft->name, 'practical') !== false);

    $getHierarchy = function(string $label): int {
        $l = strtolower($label);
        if (str_contains($l, 'registration')) return 1;
        if (str_contains($l, 'course'))        return 2;
        if (str_contains($l, 'subject'))       return 3;
        if (str_contains($l, 'practical'))     return 4;
        if (str_contains($l, 'exam'))          return 5;
        if (str_contains($l, 'admit'))         return 6;
        if (str_contains($l, 'library'))       return 7;
        if (str_contains($l, 'transport'))     return 8;
        return 9;
    };

    foreach ($chargedFees as $txn) {
        $label = str_replace('Fee charged: ', '', $txn->des ?? '');
        if (str_ends_with($label, '— Subject Fee')) {
            $subjectTotal += $txn->debit;
        } elseif (str_ends_with($label, '— Practical Fee')) {
            // Per-subject practical fee — alag row
            $groupedItems->push([
                'label'       => $label,
                'amount'      => $txn->debit,
                'fee_type_id' => $pfType?->id,
                'hierarchy'   => 4,
            ]);
        } else {
            $matchedType = $allFeeTypes->first(fn($ft) => stripos($label, $ft->name) !== false);
            $groupedItems->push([
                'label'       => $label,
                'amount'      => $txn->debit,
                'fee_type_id' => $matchedType?->id,
                'hierarchy'   => $getHierarchy($label),
            ]);
        }
    }

    if ($subjectTotal > 0) {
        $sfType = $allFeeTypes->first(fn($ft) => stripos($ft->name, 'subject') !== false);
        $groupedItems->push([
            'label'       => 'Subject Fee (All Subjects)',
            'amount'      => $subjectTotal,
            'fee_type_id' => $sfType?->id,
            'hierarchy'   => 3,
        ]);
    }

    // Sort by hierarchy
    $groupedItems = $groupedItems->sortBy('hierarchy')->values();

    // Already paid — fee_name wise group
    $rawPaid = \App\Models\FeeInvoiceItem::whereHas('invoice', function ($q) use ($student) {
            $q->where('student_id', $student->id)
              ->where('academic_session_id', $student->academic_session_id)
              ->where('is_cancelled', false);
        })
        ->selectRaw('fee_name, SUM(amount) as paid_total, SUM(COALESCE(discount,0)) as discount_total')
        ->groupBy('fee_name')
        ->get();

    $sfPaid = 0; $sfDisc = 0;
    $otherPaidRows = collect();
    foreach ($rawPaid as $row) {
        $n = strtolower($row->fee_name);
        if (str_contains($n, 'subject fee') && !str_contains($n, 'all subjects') && !str_contains($n, 'practical')) {
            $sfPaid += (float)$row->paid_total; $sfDisc += (float)$row->discount_total;
        } elseif (str_contains($n, 'subject fee (all subjects)')) {
            $sfPaid += (float)$row->paid_total; $sfDisc += (float)$row->discount_total;
        } else {
            // Practical fees (per-subject) aur baki sab — exact name se match hoga
            $otherPaidRows->push($row);
        }
    }
    $alreadyPaid = $otherPaidRows->keyBy('fee_name');
    if ($sfPaid > 0 || $sfDisc > 0) {
        $alreadyPaid->put('Subject Fee (All Subjects)', (object)['paid_total' => $sfPaid, 'discount_total' => $sfDisc]);
    }

    $oldFeeItems = collect(old('fee_items', []));
    $oldFeeItemMap = $oldFeeItems
        ->filter(fn($item) => !empty($item['fee_name']) && empty($item['is_custom']))
        ->keyBy('fee_name');
    $oldCustomFeeItems = $oldFeeItems
        ->filter(fn($item) => !empty($item['is_custom']))
        ->values()
        ->all();

    $feeTotal = $groupedItems->sum('amount');

    // Hierarchy JS data
    $hierarchyData = [];
    foreach ($groupedItems as $i => $item) {
        $paidData  = $alreadyPaid->get($item['label']);
        $paidAmt   = (float) ($paidData?->paid_total   ?? 0);
        $paidDisc  = (float) ($paidData?->discount_total ?? 0);
        $remaining = max(0, $item['amount'] - $paidAmt - $paidDisc);
        if ($remaining > 0) {
            $hierarchyData[] = ['idx' => $i, 'remaining' => $remaining];
        }
    }
@endphp

<div class="row g-4">

    {{-- LEFT: Fee Breakup --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2">
                <h6 class="mb-0 fw-semibold small"><i class="bi bi-list-ul me-2 text-primary"></i>Fee Breakup (Total)</h6>
            </div>
            <div class="card-body p-0">
                @if($chargedFees->isNotEmpty())
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach($chargedFees as $txn)
                        <tr>
                            <td class="small ps-3">{{ str_replace('Fee charged: ', '', $txn->des) }}</td>
                            <td class="text-end pe-3 fw-semibold text-danger small">₹ {{ number_format($txn->debit, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td class="ps-3 fw-bold small">Total Charged</td>
                            <td class="text-end pe-3 fw-bold small">₹ {{ number_format($walletSummary['total_charged'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
                @else
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-info-circle d-block mb-1 fs-4"></i>
                    No fee rules have been configured.
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- RIGHT: Collect Fee Form --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold small d-flex align-items-center gap-2">
                    <i class="bi bi-cash-coin me-2 text-success"></i>Collect Fee Now
                    @if(isset($staffMaxDiscount) && $staffMaxDiscount === 0)
                        <span class="badge bg-danger" style="font-size:10px;font-weight:600;">
                            <i class="bi bi-x-circle me-1"></i>No Discount Allowed
                        </span>
                    @elseif(isset($staffMaxDiscount) && $staffMaxDiscount < 100)
                        <span class="badge bg-warning text-dark" style="font-size:10px;font-weight:600;">
                            <i class="bi bi-percent me-1"></i>Discount limit: {{ $staffMaxDiscount }}%
                        </span>
                    @endif
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted">One-time pay:</small>
                    <div class="input-group input-group-sm" style="width:150px;">
                        <span class="input-group-text bg-warning text-dark fw-bold">₹</span>
                        <input type="number" id="oneTimePay" class="form-control"
                               placeholder="Amount..." min="0" step="1">
                    </div>
                    <button type="button" class="btn btn-warning btn-sm fw-semibold"
                            onclick="applyOneTimePay()">
                        <i class="bi bi-lightning-fill me-1"></i>Fill
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold"
                            onclick="clearAllFields()" title="Sabhi fields clear karo">
                        <i class="bi bi-x-circle me-1"></i>All Clear
                    </button>
                </div>
            </div>
            <div class="card-body p-0">

            <form method="POST" action="{{ route($feeRoutePrefix . '.store') }}" id="feeForm">
            @csrf
            <input type="hidden" name="student_id" value="{{ $student->id }}">
            @php session(['from_admission_fee_payment' => true]) @endphp

            @if($errors->any())
            <div class="alert alert-danger border-0 rounded-0 mb-0 px-3 py-2" id="formErrorBanner">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
                    <div>
                        <div class="fw-semibold mb-1" style="font-size:13px;">Fee submit nahi hua — please fix karo:</div>
                        <ul class="mb-0 ps-3" style="font-size:12px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endif

            {{-- Fee Table --}}
            <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" style="font-size:12px; min-width:780px;">
                <thead style="background:#f1f5f9;">
                    <tr>
                        <th style="width:36px;" class="text-center ps-2">✓</th>
                        <th>Fee Item</th>
                        <th class="text-end" style="width:70px;">Total</th>
                        <th class="text-end" style="width:65px;">Paid</th>
                        <th class="text-end" style="width:75px;">Remaining</th>
                        <th class="text-end" style="width:105px; color:#1d4ed8;">Collect ₹</th>
                        <th class="text-end" style="width:60px; color:#6b7280; font-size:11px;">Prev<br>Disc</th>
                        <th class="text-end" style="width:100px; color:#dc2626;">Fine ₹</th>
                        <th class="text-end" style="width:100px; color:#d97706;">Disc. ₹</th>
                        <th class="text-end" style="width:65px;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                @php $idx = 0; @endphp
                @foreach($groupedItems as $item)
                @php
                    $feeName   = $item['label'];
                    $totalFee  = (float) $item['amount'];
                    $paidData  = $alreadyPaid->get($feeName);
                    $oldItem   = $oldFeeItemMap->get($feeName, []);
                    $paidAmt   = (float) ($paidData?->paid_total ?? 0);
                    $paidDisc  = (float) ($paidData?->discount_total ?? 0);
                    $remaining = max(0, $totalFee - $paidAmt - $paidDisc);
                    $fullyPaid = ($paidAmt + $paidDisc) >= $totalFee && $totalFee > 0;
                    $partial   = ($paidAmt + $paidDisc) > 0 && $remaining > 0;
                    $isChecked = $oldFeeItems->isNotEmpty()
                        ? array_key_exists('checked', (array) $oldItem)
                        : $remaining > 0;
                    $fineValue = max(0, (float) ($oldItem['fine'] ?? 0));
                    $feeTypeId    = $item['fee_type_id'] ?? null;
                    $itemMaxDisc  = (!isset($staffFeeAllowedTypes) || $staffFeeAllowedTypes === null || ($feeTypeId && in_array($feeTypeId, $staffFeeAllowedTypes)))
                        ? ($staffMaxDiscount ?? 100)
                        : 0;
                    $discValue = max(0, (float) ($oldItem['discount'] ?? 0));
                    $collectValue = max(0, (float) ($oldItem['amount'] ?? 0));
                    $rowCap = max(0, $remaining + $fineValue - $discValue);
                    if ($collectValue > $rowCap) {
                        $collectValue = $rowCap;
                    }
                @endphp
                <tr class="{{ $fullyPaid ? 'table-success' : '' }}">
                    <td class="text-center ps-2">
                        <input type="checkbox" class="form-check-input fee-check"
                               id="ck-{{ $idx }}"
                               name="fee_items[{{ $idx }}][checked]"
                               value="1"
                               {{ $isChecked ? 'checked' : '' }}
                               {{ $fullyPaid ? 'disabled' : '' }}
                               onchange="toggleRow({{ $idx }})">
                    </td>
                    <td>
                        <input type="hidden" name="fee_items[{{ $idx }}][fee_type_id]" value="{{ $item['fee_type_id'] ?? '' }}">
                        <input type="hidden" name="fee_items[{{ $idx }}][fee_name]" value="{{ $feeName }}">
                        <input type="hidden" name="fee_items[{{ $idx }}][total_fee]" value="{{ number_format($totalFee, 2, '.', '') }}">
                        <label for="ck-{{ $idx }}" class="mb-0 fw-semibold small {{ $fullyPaid ? 'text-muted' : '' }}" id="lbl-{{ $idx }}">
                            {{ $feeName }}
                        </label>
                        @if($fullyPaid)
                            <span class="badge bg-success ms-1" style="font-size:9px;">✓ Paid</span>
                        @elseif($partial)
                            <span class="badge bg-warning text-dark ms-1" style="font-size:9px;">Partial</span>
                        @else
                            <span class="badge bg-primary bg-opacity-10 text-primary border ms-1" style="font-size:9px;">Due</span>
                        @endif
                    </td>
                    <td class="text-end text-muted small">{{ number_format($totalFee) }}</td>
                    <td class="text-end small {{ $paidAmt > 0 ? 'text-success' : 'text-muted' }}">
                        {{ $paidAmt > 0 ? number_format($paidAmt) : '—' }}
                    </td>
                    <td class="text-end small fw-semibold {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $remaining > 0 ? number_format($remaining) : '0' }}
                    </td>
                    {{-- Collect Amount --}}
                    <td class="text-end">
                        <div class="input-group input-group-sm justify-content-end">
                            <span class="input-group-text">₹</span>
                            <input type="number"
                                   name="fee_items[{{ $idx }}][amount]"
                                   class="form-control fee-amount text-end"
                                   id="amt-{{ $idx }}"
                                   value="{{ number_format($collectValue, 2, '.', '') }}"
                                   min="0" step="1" style="max-width:75px;"
                                   {{ (!$isChecked || $fullyPaid) ? 'disabled' : '' }}
                                   data-remaining="{{ $remaining }}"
                                   data-assigned="{{ number_format($remaining, 2, '.', '') }}"
                                   oninput="updateBalance({{ $idx }})">
                        </div>
                    </td>
                    {{-- Previously given discount (read-only) --}}
                    <td class="text-end small">
                        @if($paidDisc > 0)
                            <span class="text-success fw-semibold">{{ number_format($paidDisc) }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    {{-- Per-item Fine --}}
                    <td class="text-end">
                        <div class="input-group input-group-sm justify-content-end">
                            <span class="input-group-text" style="background:#fef2f2;border-color:#f87171;color:#dc2626;font-weight:600;">₹</span>
                            <input type="number"
                                   name="fee_items[{{ $idx }}][fine]"
                                   class="form-control fee-fine text-end"
                                   id="fine-{{ $idx }}"
                                   value="{{ number_format($fineValue, 2, '.', '') }}" min="0" step="0.01"
                                   style="max-width:80px;border-color:#f87171;font-weight:600;color:#dc2626;"
                                   {{ (!$isChecked || $fullyPaid) ? 'disabled' : '' }}
                                   oninput="onFineInput({{ $idx }})"
                                   onchange="onFineChange({{ $idx }})">
                        </div>
                    </td>
                    {{-- Per-item Discount --}}
                    <td class="text-end">
                        <div class="input-group input-group-sm justify-content-end">
                            <span class="input-group-text" style="background:#fff8e1;border-color:#f59e0b;color:#d97706;font-weight:600;">₹</span>
                            <input type="number"
                                   name="fee_items[{{ $idx }}][discount]"
                                   class="form-control fee-disc text-end"
                                   id="disc-{{ $idx }}"
                                   value="{{ number_format($itemMaxDisc > 0 ? $discValue : 0, 2, '.', '') }}" min="0" max="{{ $remaining }}" step="0.01"
                                   style="max-width:80px;border-color:#f59e0b;font-weight:600;color:#d97706;"
                                   data-max-disc="{{ $itemMaxDisc }}"
                                   {{ (!$isChecked || $fullyPaid || $itemMaxDisc <= 0) ? 'disabled' : '' }}
                                   oninput="onDiscInput({{ $idx }})"
                                   onchange="onDiscChange({{ $idx }})">
                        </div>
                    </td>
                    {{-- Balance --}}
                    <td class="text-end fw-semibold small" id="bal-{{ $idx }}">
                        @php $initBal = max(0, $remaining + $fineValue - $discValue - ($isChecked ? $collectValue : 0)); @endphp
                        <span class="{{ $initBal > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($initBal) }}
                        </span>
                    </td>
                </tr>
                @php $idx++; @endphp
                @endforeach
                </tbody>
            </table>
            </div>

            {{-- Custom Fee + One-time --}}
            <div id="manualRows" class="px-3 pt-2"></div>
            <div class="px-3 pt-1 pb-2">
                @if(isset($staffCollectFeeTypeIds) && $staffCollectFeeTypeIds !== null)
                    <div class="small text-muted">
                        Custom fee items are disabled because fee item access is restricted for this account.
                    </div>
                @else
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addManualRow()">
                        <i class="bi bi-plus me-1"></i> Custom Fee Item
                    </button>
                @endif
            </div>

            {{-- Summary --}}
            <div class="border-top p-3">
                <div class="row g-2 mb-3">
                    <div class="col-md-3 col-6">
                        <div class="rounded p-2 text-center" style="background:#f1f5f9;">
                            <div style="font-size:10px;color:#64748b;">Total Collected</div>
                            <div class="fw-bold text-primary small" id="totalCollectDisplay">₹ 0</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="rounded p-2 text-center" style="background:#fef2f2;">
                            <div style="font-size:10px;color:#b91c1c;">Total Fine</div>
                            <div class="fw-bold text-danger small" id="totalFineDisplay">₹ 0</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="rounded p-2 text-center" style="background:#fffbeb;">
                            <div style="font-size:10px;color:#92400e;">Total Discount</div>
                            <div class="fw-bold text-warning small" id="totalDiscDisplay">₹ 0</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="rounded p-2 text-center" style="background:#1e293b;">
                            <div style="font-size:10px;color:rgba(255,255,255,.7);">Total Due</div>
                            <div class="fw-bold text-white fs-5" id="totalDisplay">₹ 0</div>
                        </div>
                    </div>
                </div>

                {{-- Semester Selector --}}
                @php
                    $currentSem = $activeSession?->current_semester ?? 1;
                    $totalSems  = ($student->stream->course->duration ?? 1) * 2;
                    $isNewAdmission = session('from_admission_fee_payment');
                    if ($isNewAdmission) {
                        // New admission: sirf current semester (Semester 1) dikhao
                        $availableSems = [$currentSem];
                    } else {
                        $availableSems = [$currentSem];
                        if ($currentSem % 2 == 1 && $currentSem + 1 <= $totalSems) {
                            $availableSems[] = $currentSem + 1;
                        } elseif ($currentSem % 2 == 0 && $currentSem - 1 >= 1) {
                            $availableSems = [$currentSem - 1, $currentSem];
                        }
                    }
                @endphp
                <div class="row g-3 mb-2">
                    <div class="col-12">
                        <label class="form-label small fw-semibold">
                            Fee Kis Semester Ke Liye? <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach($availableSems as $sem)
                            <div class="form-check form-check-inline border rounded px-3 py-2">
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
                            Session: {{ $activeSession?->name }} — Only semesters from this session are allowed.
                        </div>
                    </div>
                </div>

                {{-- Payment Mode + Bank Account --}}
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                        <select name="payment_mode" id="paymentMode" class="form-select form-select-sm"
                                onchange="handlePaymentModeChange()" required>
                            @foreach($allowedPaymentModes as $mode)
                            <option value="{{ $mode }}" {{ old('payment_mode') === $mode ? 'selected' : '' }}>
                                {{ $paymentModeLabels[$mode] ?? strtoupper($mode) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6" id="bankAccountWrap" style="display:none;">
                        <label class="form-label small fw-semibold">Bank Account <span class="text-danger bank-required-marker" style="display:none;">*</span></label>
                        <select name="bank_account_id" id="bankAccountSelect" class="form-select form-select-sm" onchange="onBankAccountChange()">
                            <option value="">Select Bank Account</option>
                            @foreach($bankAccounts as $ba)
                            <option value="{{ $ba->id }}"
                                    data-modes="{{ $ba->allowed_payment_modes ?? 'cash,upi,online,cheque,dd,neft,rtgs' }}">
                                {{ $ba->bank_name }}@if($ba->account_number) — {{ $ba->account_number }}@endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
                        @if($lockPaymentDate)
                        <input type="hidden" name="payment_date" value="{{ $defaultPaymentDate }}">
                        <input type="text" class="form-control form-control-sm"
                               value="{{ \Carbon\Carbon::parse($defaultPaymentDate)->format('d-m-Y') }}" readonly>
                        <div class="form-text">Auto set to today's date for this panel.</div>
                        @else
                        <input type="date" name="payment_date" class="form-control form-control-sm"
                               value="{{ $defaultPaymentDate }}" required>
                        @endif
                    </div>
                    <div class="col-md-6" id="refField" style="display:none;">
                        <label class="form-label small fw-semibold" id="refLabel">Transaction Ref <span class="text-danger">*</span></label>
                        <input type="text" name="transaction_ref" id="transactionRefInput" class="form-control form-control-sm"
                               placeholder="Txn / UTR / Cheque no.">
                    </div>
                    <div class="col-md-6" id="bankField" style="display:none;">
                        <label class="form-label small fw-semibold">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control form-control-sm"
                               placeholder="Bank name...">
                    </div>
                    <div class="col-md-6" id="datetimeField" style="display:none;">
                        <label class="form-label small fw-semibold">Payment Date &amp; Time <span class="text-danger">*</span> <span class="text-muted fw-normal">(Actual payment time)</span></label>
                        <input type="datetime-local" name="payment_datetime" id="paymentDatetimeInput"
                               class="form-control form-control-sm"
                               value="">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Remarks (optional)</label>
                        <input type="text" name="remarks" class="form-control form-control-sm"
                               placeholder="Koi note...">
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success flex-grow-1">
                        <i class="bi bi-check-circle me-2"></i>Collect Fee & Print Receipt
                    </button>
                    <a href="{{ route($admissionRoutePrefix . '.print-all', $student->id) }}"
                       class="btn btn-outline-secondary">
                        <i class="bi bi-printer me-1"></i> Skip, Print Only
                    </a>
                </div>
            </div>

            </form>
            </div>
        </div>

        <div class="mt-2 text-center">
            <a href="{{ $admissionListUrl }}" class="text-muted small">
                <i class="bi bi-arrow-right me-1"></i>Collect fee later — Go to Admissions List
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
@php
    $globalAllowedModes = $allowedPaymentModes ?? ['cash', 'upi', 'online', 'cheque', 'dd', 'neft', 'rtgs'];
@endphp
const hierarchyItems = @json($hierarchyData);
const totalDueCap = {{ abs(min(0, $walletSummary['balance'])) }};
const globallyAllowedModes = @json($globalAllowedModes);
const oldCustomFeeItems = @json($oldCustomFeeItems);
const paymentModeLabels = {
    cash: 'Cash',
    upi: 'UPI',
    online: 'Online',
    cheque: 'Cheque',
    dd: 'DD',
    neft: 'NEFT',
    rtgs: 'RTGS',
};
let manualCount = {{ $idx }};

// ── One-Time Pay — hierarchy wise fill, discount already deduct karke ─
function applyOneTimePay() {
    let amount = parseFloat(document.getElementById('oneTimePay').value) || 0;
    if (amount <= 0) return;

    // Cap — collect + discount <= totalDueCap
    let currentDisc = 0;
    document.querySelectorAll('.fee-disc').forEach(inp => {
        if (!inp.disabled) currentDisc += parseFloat(inp.value) || 0;
    });
    const maxCollect = Math.max(0, totalDueCap - currentDisc);
    amount = Math.min(amount, maxCollect);

    // Pehle sab reset karo
    hierarchyItems.forEach(item => {
        const ck  = document.getElementById('ck-'  + item.idx);
        const amt = document.getElementById('amt-' + item.idx);
        if (!ck || !amt) return;
        ck.checked   = false;
        amt.disabled = true;
        amt.value    = 0;
        updateBalance(item.idx);
    });

    // Hierarchy order mein fill karo — fine add karke, discount deduct karke Collect fill karo
    for (const item of hierarchyItems) {
        if (amount <= 0) break;
        const ck   = document.getElementById('ck-'   + item.idx);
        const amt  = document.getElementById('amt-'  + item.idx);
        const fine = document.getElementById('fine-' + item.idx);
        const disc = document.getElementById('disc-' + item.idx);
        if (!ck || !amt || ck.disabled) continue;

        const fineVal    = parseFloat(fine?.value)  || 0;
        const discVal    = parseFloat(disc?.value)  || 0;
        // Is row ke liye kitna collect karna hai = remaining + fine - discount
        const canCollect = Math.max(0, item.remaining + fineVal - discVal);
        const toFill     = Math.min(amount, canCollect);

        ck.checked   = true;
        amt.disabled = false;
        amt.value    = toFill;
        amount      -= toFill;
        updateBalance(item.idx);
    }
    calcTotal();
}

// ── Disc change — auto update collect amount
function onDiscChange(i) {
    const amtEl  = document.getElementById("amt-"  + i);
    const discEl = document.getElementById("disc-" + i);
    if (!amtEl || !discEl || amtEl.disabled) return;
    const remaining = parseFloat(amtEl.dataset.remaining) || 0;
    let   disc      = parseFloat(discEl.value) || 0;

    // Disc remaining se zyada nahi ho sakta
    if (disc > remaining) {
        disc = remaining;
        discEl.value = disc;
    }

    amtEl.value = Math.max(0, remaining - disc);
    updateBalance(i);
    redistributeOneTimePay();
}

function redistributeOneTimePay() {
    const oneTimeEl = document.getElementById('oneTimePay');
    if (!oneTimeEl || !oneTimeEl.value) return;
    let targetTotal = parseFloat(oneTimeEl.value) || 0;
    if (targetTotal <= 0) return;

    // Cap — collect <= totalDueCap - currentDisc
    let currentDisc = 0;
    document.querySelectorAll('.fee-disc').forEach(inp => {
        if (!inp.disabled) currentDisc += parseFloat(inp.value) || 0;
    });
    const maxCollect = Math.max(0, totalDueCap - currentDisc);
    targetTotal = Math.min(targetTotal, maxCollect);
    // Step 1: Sab collect reset karo (disc preserve karo)
    hierarchyItems.forEach(item => {
        const ck  = document.getElementById('ck-'  + item.idx);
        const amt = document.getElementById('amt-' + item.idx);
        if (!ck || !amt || ck.disabled) return;
        ck.checked   = false;
        amt.disabled = true;
        amt.value    = 0;
        updateBalance(item.idx);
    });

    // Step 2: targetTotal ko hierarchy wise fill karo (fine add, disc consider karke)
    let amount = targetTotal;
    for (const item of hierarchyItems) {
        if (amount <= 0) break;
        const ck   = document.getElementById('ck-'   + item.idx);
        const amt  = document.getElementById('amt-'  + item.idx);
        const fine = document.getElementById('fine-' + item.idx);
        const disc = document.getElementById('disc-' + item.idx);
        if (!ck || !amt || ck.disabled) continue;
        const fineVal    = parseFloat(fine?.value)  || 0;
        const discVal    = parseFloat(disc?.value)  || 0;
        const canCollect = Math.max(0, item.remaining + fineVal - discVal);
        const toFill     = Math.min(amount, canCollect);
        ck.checked   = true;
        amt.disabled = false;
        amt.value    = toFill;
        amount      -= toFill;
        updateBalance(item.idx);
    }
    calcTotal();
}


// ── Toggle row ──────────────────────────────────────────────────────
function toggleRow(i) {
    const ck   = document.getElementById('ck-'   + i);
    const amt  = document.getElementById('amt-'  + i);
    const disc = document.getElementById('disc-' + i);
    const lbl  = document.getElementById('lbl-'  + i);
    if (!ck || !amt) return;
    amt.disabled  = !ck.checked;
    if (disc) {
        const maxDiscPct = parseFloat(disc.dataset.maxDisc ?? 100);
        disc.disabled = !ck.checked || maxDiscPct <= 0;
    }
    if (!ck.checked) { amt.value = 0; if (disc) disc.value = 0; }
    lbl?.classList.toggle('text-muted', !ck.checked);
    updateBalance(i);
}

// ── Update single row balance ────────────────────────────────────────
function updateBalance(i) {
    const amtEl  = document.getElementById('amt-'  + i);
    const discEl = document.getElementById('disc-' + i);
    const balEl  = document.getElementById('bal-'  + i);
    if (!amtEl || !balEl) return;

    const remaining = parseFloat(amtEl.dataset.remaining) || 0;
    let   collect   = parseFloat(amtEl.value)   || 0;
    let   discount  = parseFloat(discEl?.value)  || 0;

    // Per-row cap: collect + discount <= remaining
    if (collect + discount > remaining) {
        collect = Math.max(0, remaining - discount);
        amtEl.value = collect;
    }

    const balance = Math.max(0, remaining - collect - discount);
    balEl.innerHTML = `<span class="${balance > 0 ? 'text-danger' : 'text-success'}">${balance.toLocaleString('en-IN')}</span>`;
    calcTotal();
}

// ── Calc totals — Total Payable = Collect + Discount ────────────────
function calcTotal() {
    let totalCollect = 0, totalDisc = 0;
    document.querySelectorAll('.fee-amount').forEach(inp => {
        if (!inp.disabled) totalCollect += parseFloat(inp.value) || 0;
    });
    document.querySelectorAll('.fee-disc').forEach(inp => {
        if (!inp.disabled) totalDisc += parseFloat(inp.value) || 0;
    });
    // Manual rows
    document.querySelectorAll('.manual-amount').forEach(inp => { totalCollect += parseFloat(inp.value) || 0; });
    document.querySelectorAll('.manual-disc').forEach(inp => { totalDisc += parseFloat(inp.value) || 0; });

    const payable = totalCollect + totalDisc;
    const tc = document.getElementById('totalCollectDisplay');
    const td = document.getElementById('totalDiscDisplay');
    const tp = document.getElementById('totalDisplay');
    if (tc) tc.textContent = '₹ ' + totalCollect.toLocaleString('en-IN');
    if (td) td.textContent = totalDisc > 0 ? '₹ ' + totalDisc.toLocaleString('en-IN') : '₹ 0';
    if (tp) tp.textContent = '₹ ' + payable.toLocaleString('en-IN');
}

// ── Add manual row ───────────────────────────────────────────────────
function addManualRow() {
    const i = manualCount++;
    document.getElementById('manualRows').insertAdjacentHTML('beforeend', `
    <div class="row g-1 align-items-center mb-1" id="manual-${i}">
        <div class="col-auto">
            <input type="checkbox" class="form-check-input" checked disabled>
            <input type="hidden" name="fee_items[${i}][checked]" value="1">
            <input type="hidden" name="fee_items[${i}][fee_type_id]" value="">
            <input type="hidden" name="fee_items[${i}][is_custom]" value="1">
        </div>
        <div class="col">
            <input type="text" name="fee_items[${i}][fee_name]"
                   class="form-control form-control-sm" placeholder="Fee name..." required>
        </div>
        <div class="col-auto">
            <div class="input-group input-group-sm">
                <span class="input-group-text" style="font-size:10px;">₹ Amt</span>
                <input type="number" name="fee_items[${i}][amount]"
                       class="form-control form-control-sm manual-amount" value="0" min="0" style="width:70px;" oninput="calcTotal()">
            </div>
        </div>
        <div class="col-auto">
            <div class="input-group input-group-sm">
                <span class="input-group-text" style="font-size:10px;">₹ Disc</span>
                <input type="number" name="fee_items[${i}][discount]"
                       class="form-control form-control-sm manual-disc" value="0" min="0" style="width:65px;" oninput="calcTotal()">
            </div>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-sm btn-outline-danger px-2"
                    onclick="document.getElementById('manual-${i}').remove();calcTotal()">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>`);
}

// ── Payment mode toggle ──────────────────────────────────────────────
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

function isCashMode(mode) {
    return mode === 'cash' || mode === '';
}

function syncBankAccountRequirement() {
    const mode = document.getElementById('paymentMode')?.value || '';
    const bankWrap = document.getElementById('bankAccountWrap');
    const bankSelect = document.getElementById('bankAccountSelect');
    const requiredMarker = document.querySelector('.bank-required-marker');
    const needsBank = !isCashMode(mode);

    if (bankWrap) {
        bankWrap.style.display = needsBank ? 'block' : 'none';
    }

    if (requiredMarker) {
        requiredMarker.style.display = needsBank ? 'inline' : 'none';
    }

    if (bankSelect) {
        bankSelect.required = needsBank;
        bankSelect.disabled = !needsBank;
        if (!needsBank) {
            bankSelect.value = '';
        }
    }
}

function onBankAccountChange() {
    const sel   = document.getElementById('bankAccountSelect');
    const mode  = document.getElementById('paymentMode')?.value || '';
    if (!sel) return;

    if (isCashMode(mode)) {
        sel.value = '';
    }

    togglePaymentFields();
}

function togglePaymentFields() {
    const mode          = document.getElementById('paymentMode')?.value;
    const refField      = document.getElementById('refField');
    const bankField     = document.getElementById('bankField');
    const datetimeField = document.getElementById('datetimeField');
    const refInput      = document.getElementById('transactionRefInput');
    const dtInput       = document.getElementById('paymentDatetimeInput');
    const lbl           = document.getElementById('refLabel');
    if (!refField) return;

    const isNonCash = mode && mode !== 'cash';

    if (['cheque','dd'].includes(mode)) {
        refField.style.display  = 'block';
        bankField.style.display = 'block';
        lbl.innerHTML = (mode === 'cheque' ? 'Cheque No' : 'DD No') + ' <span class="text-danger">*</span>';
    } else if (['upi','online','neft','rtgs'].includes(mode)) {
        refField.style.display  = 'block';
        bankField.style.display = 'none';
        lbl.innerHTML = 'Transaction Ref / UTR <span class="text-danger">*</span>';
    } else {
        refField.style.display  = 'none';
        bankField.style.display = 'none';
    }

    // Payment Date & Time — mandatory for non-cash; auto-set date to today when shown
    if (datetimeField) datetimeField.style.display = isNonCash ? 'block' : 'none';
    if (isNonCash && dtInput) {
        const now     = new Date();
        const today   = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0');
        const timePart = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
        dtInput.value = today + 'T' + timePart;
        // Lock date to today — only allow time changes
        dtInput.min = today + 'T00:00';
        dtInput.max = today + 'T' + timePart;
    }
    if (refInput)  refInput.required  = isNonCash;
    if (dtInput)   dtInput.required   = isNonCash;
}

// Enforce today's date on non-cash payment — prevent back-date even if typed manually
document.getElementById('paymentDatetimeInput')?.addEventListener('input', function () {
    const n = new Date();
    const d = n.getFullYear() + '-' + String(n.getMonth()+1).padStart(2,'0') + '-' + String(n.getDate()).padStart(2,'0');
    if (this.value && this.value.split('T')[0] !== d) {
        this.value = d + 'T' + (this.value.split('T')[1] || String(n.getHours()).padStart(2,'0') + ':' + String(n.getMinutes()).padStart(2,'0'));
    }
});

function handlePaymentModeChange() {
    syncBankAccountRequirement();
    onBankAccountChange();
}

// Override payment math with fine-aware logic and restore custom rows on validation errors.
function feeNumber(value) {
    return Math.max(0, parseFloat(value) || 0);
}

function getRowState(index) {
    return {
        checkbox: document.getElementById(`ck-${index}`),
        amountInput: document.getElementById(`amt-${index}`),
        fineInput: document.getElementById(`fine-${index}`),
        discountInput: document.getElementById(`disc-${index}`),
        label: document.getElementById(`lbl-${index}`),
        balanceCell: document.getElementById(`bal-${index}`),
    };
}

function rowAssigned(state) {
    return feeNumber(state.amountInput?.dataset.assigned);
}

function rowCap(state) {
    return Math.max(0, rowAssigned(state) + feeNumber(state.fineInput?.value) - feeNumber(state.discountInput?.value));
}

function updateBalance(index) {
    const state = getRowState(index);
    if (!state.amountInput || !state.balanceCell) return;

    const checked = Boolean(state.checkbox?.checked);
    const assigned = rowAssigned(state);
    let collect = feeNumber(state.amountInput.value);
    let fine = feeNumber(state.fineInput?.value);
    let discount = feeNumber(state.discountInput?.value);

    const maxDiscPct  = parseFloat(state.discountInput?.dataset.maxDisc ?? 100);
    const discountCap = maxDiscPct <= 0
        ? 0
        : Math.min(assigned + fine, (assigned + fine) * maxDiscPct / 100);
    if (discount > discountCap) {
        discount = discountCap;
        if (state.discountInput) state.discountInput.value = discount.toFixed(2);
    }

    const maxCollect = Math.max(0, assigned + fine - discount);
    if (collect > maxCollect) {
        collect = maxCollect;
        state.amountInput.value = collect.toFixed(2);
    }

    const balance = Math.max(0, assigned + fine - discount - (checked ? collect : 0));
    state.balanceCell.innerHTML = `<span class="${balance > 0 ? 'text-danger' : 'text-success'}">${balance.toLocaleString('en-IN')}</span>`;
    calcTotal();
}

function toggleRow(index) {
    const state = getRowState(index);
    if (!state.checkbox || !state.amountInput) return;

    const checked = state.checkbox.checked;
    [state.amountInput, state.fineInput, state.discountInput].forEach(input => {
        if (input) input.disabled = !checked;
    });

    if (!checked) {
        state.amountInput.value = '0.00';
        if (state.fineInput) state.fineInput.value = '0.00';
        if (state.discountInput) state.discountInput.value = '0.00';
    }

    state.label?.classList.toggle('text-muted', !checked);
    updateBalance(index);
}

function distributeOneTimePay(targetTotal) {
    let amount = Math.max(0, targetTotal);

    hierarchyItems.forEach(item => {
        const state = getRowState(item.idx);
        if (!state.checkbox || !state.amountInput || state.checkbox.disabled) return;

        state.checkbox.checked = false;
        state.amountInput.disabled = true;
        if (state.fineInput) state.fineInput.disabled = true;
        if (state.discountInput) state.discountInput.disabled = true;
        state.amountInput.value = '0.00';
        updateBalance(item.idx);
    });

    for (const item of hierarchyItems) {
        if (amount <= 0) break;

        const state = getRowState(item.idx);
        if (!state.checkbox || !state.amountInput || state.checkbox.disabled) continue;

        const maxCollect = rowCap(state);
        if (maxCollect <= 0) continue;

        const toFill = Math.min(amount, maxCollect);
        state.checkbox.checked = true;
        state.amountInput.disabled = false;
        if (state.fineInput) state.fineInput.disabled = false;
        if (state.discountInput) state.discountInput.disabled = false;
        state.amountInput.value = toFill.toFixed(2);
        amount -= toFill;
        updateBalance(item.idx);
    }

    calcTotal();
}

function applyOneTimePay() {
    const amount = feeNumber(document.getElementById('oneTimePay')?.value);
    if (amount <= 0) return;
    distributeOneTimePay(amount);
}

function redistributeOneTimePay() {
    const amount = feeNumber(document.getElementById('oneTimePay')?.value);
    if (amount <= 0) return;
    distributeOneTimePay(amount);
}

function onFineInput(index) {
    updateBalance(index);
}

function onFineChange(index) {
    updateBalance(index);
    redistributeOneTimePay();
}

function onDiscInput(index) {
    const discEl = document.getElementById(`disc-${index}`);
    if (discEl) {
        const maxDiscPct = parseFloat(discEl.dataset.maxDisc ?? 100);
        if (maxDiscPct <= 0) {
            discEl.value = '0.00';
        } else if (maxDiscPct < 100) {
            const amtEl  = document.getElementById(`amt-${index}`);
            const fineEl = document.getElementById(`fine-${index}`);
            const assigned = parseFloat(amtEl?.dataset.assigned ?? 0);
            const fine     = parseFloat(fineEl?.value ?? 0);
            const maxAllowed = (assigned + fine) * maxDiscPct / 100;
            if (parseFloat(discEl.value) > maxAllowed) {
                discEl.value = maxAllowed.toFixed(2);
            }
        }
    }
    updateBalance(index);
}

function onDiscChange(index) {
    onDiscInput(index);
    redistributeOneTimePay();
}

function calcTotal() {
    let totalCollect = 0;
    let totalFine = 0;
    let totalDisc = 0;

    document.querySelectorAll('.fee-check').forEach((checkbox, index) => {
        if (!checkbox.checked) return;
        totalCollect += feeNumber(document.getElementById(`amt-${index}`)?.value);
        totalFine += feeNumber(document.getElementById(`fine-${index}`)?.value);
        totalDisc += feeNumber(document.getElementById(`disc-${index}`)?.value);
    });

    document.querySelectorAll('.manual-row').forEach(row => {
        totalCollect += feeNumber(row.querySelector('.manual-amount')?.value);
        totalFine += feeNumber(row.querySelector('.manual-fine')?.value);
        totalDisc += feeNumber(row.querySelector('.manual-disc')?.value);
    });

    const remainingAfter = Math.max(0, totalDueCap - totalCollect + totalFine - totalDisc);
    const tc = document.getElementById('totalCollectDisplay');
    const tf = document.getElementById('totalFineDisplay');
    const td = document.getElementById('totalDiscDisplay');
    const tp = document.getElementById('totalDisplay');
    if (tc) tc.textContent = `₹ ${totalCollect.toLocaleString('en-IN')}`;
    if (tf) tf.textContent = `₹ ${totalFine.toLocaleString('en-IN')}`;
    if (td) td.textContent = `₹ ${totalDisc.toLocaleString('en-IN')}`;
    if (tp) tp.textContent = `₹ ${remainingAfter.toLocaleString('en-IN')}`;
}

function addManualRow(item = {}) {
    const index = manualCount++;
    const feeName = item.fee_name || '';
    const amount = feeNumber(item.amount).toFixed(2);
    const fine = feeNumber(item.fine).toFixed(2);
    const disc = feeNumber(item.discount).toFixed(2);

    document.getElementById('manualRows').insertAdjacentHTML('beforeend', `
    <div class="row g-1 align-items-center mb-1 manual-row" id="manual-${index}">
        <div class="col-auto">
            <input type="checkbox" class="form-check-input" checked disabled>
            <input type="hidden" name="fee_items[${index}][checked]" value="1">
            <input type="hidden" name="fee_items[${index}][fee_type_id]" value="">
            <input type="hidden" name="fee_items[${index}][is_custom]" value="1">
        </div>
        <div class="col">
            <input type="text" name="fee_items[${index}][fee_name]"
                   class="form-control form-control-sm" placeholder="Fee name..." value="${feeName}" required>
        </div>
        <div class="col-auto">
            <div class="input-group input-group-sm">
                <span class="input-group-text" style="font-size:10px;">₹ Amt</span>
                <input type="number" name="fee_items[${index}][amount]"
                       class="form-control form-control-sm manual-amount" value="${amount}" min="0" step="0.01" style="width:75px;" oninput="calcTotal()">
            </div>
        </div>
        <div class="col-auto">
            <div class="input-group input-group-sm">
                <span class="input-group-text" style="font-size:10px;color:#dc2626;">₹ Fine</span>
                <input type="number" name="fee_items[${index}][fine]"
                       class="form-control form-control-sm manual-fine" value="${fine}" min="0" step="0.01" style="width:75px;" oninput="calcTotal()">
            </div>
        </div>
        <div class="col-auto">
            <div class="input-group input-group-sm">
                <span class="input-group-text" style="font-size:10px;">₹ Disc</span>
                <input type="number" name="fee_items[${index}][discount]"
                       class="form-control form-control-sm manual-disc" value="${disc}" min="0" step="0.01" style="width:75px;" oninput="calcTotal()">
            </div>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-sm btn-outline-danger px-2"
                    onclick="document.getElementById('manual-${index}').remove();calcTotal()">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>`);
}

function clearAllFields() {
    document.getElementById('oneTimePay').value = '';
    hierarchyItems.forEach(item => {
        const state = getRowState(item.idx);
        if (!state.checkbox || state.checkbox.disabled) return;
        state.checkbox.checked = false;
        [state.amountInput, state.fineInput, state.discountInput].forEach(inp => {
            if (inp) { inp.disabled = true; inp.value = '0.00'; }
        });
        state.label?.classList.add('text-muted');
        updateBalance(item.idx);
    });
    document.querySelectorAll('.manual-row').forEach(row => row.remove());
    calcTotal();
}

document.addEventListener('DOMContentLoaded', () => {
    oldCustomFeeItems.forEach(item => addManualRow(item));
    document.querySelectorAll('.fee-check').forEach((_, index) => updateBalance(index));
    calcTotal();
    ensurePaymentModeOptions();
    handlePaymentModeChange();
});
</script>
@endpush

@endsection
