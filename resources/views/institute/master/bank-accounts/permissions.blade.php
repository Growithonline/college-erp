@extends('institute.layout')
@section('title', 'Payment Permissions')
@section('breadcrumb', 'Master / Bank Accounts / Permissions')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Payment Permissions</h4>
        <small class="text-muted">Set allowed payment modes and bank accounts for Staff, Centers, and Partners</small>
    </div>
    <a href="{{ route('master.bank-accounts.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Bank Accounts
    </a>
</div>

<div class="alert alert-info border-0 py-2 mb-3 small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Default:</strong> If no record exists for a user, <em>all modes and all banks</em> are allowed.
    To restrict access, uncheck the relevant options and save.
</div>

@if($accounts->isEmpty())
<div class="alert alert-warning border-0">
    <i class="bi bi-exclamation-triangle me-2"></i>
    No bank accounts found —
    <a href="{{ route('master.bank-accounts.create') }}">Add Bank Account</a>
</div>
@endif

<form method="POST" action="{{ route('master.bank-accounts.permissions.save') }}">
@csrf

@php
    $groups = [
        ['type' => 'staff',   'label' => 'Staff Members',    'color' => '#1D9E75', 'icon' => 'bi-person-badge', 'users' => $staff],
        ['type' => 'center',  'label' => 'Centers',          'color' => '#185FA5', 'icon' => 'bi-building',     'users' => $centers],
        ['type' => 'partner', 'label' => 'Channel Partners', 'color' => '#854F0B', 'icon' => 'bi-people',       'users' => $partners],
    ];
@endphp

@foreach($groups as $group)
@if($group['users']->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header py-2 d-flex align-items-center gap-2"
         style="background:{{ $group['color'] }}18; border-left: 4px solid {{ $group['color'] }};">
        <i class="bi {{ $group['icon'] }} fs-6" style="color:{{ $group['color'] }};"></i>
        <span class="fw-semibold" style="color:{{ $group['color'] }};">{{ $group['label'] }}</span>
        <span class="badge ms-1 rounded-pill" style="background:{{ $group['color'] }}22; color:{{ $group['color'] }}; font-size:10px;">
            {{ $group['users']->count() }}
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-top mb-0" style="font-size:13px;">
                <thead style="background:#f8fafc; font-size:11px; text-transform:uppercase; letter-spacing:.4px;">
                    <tr>
                        <th class="ps-3 py-2" style="min-width:160px; width:18%;">Name</th>
                        <th class="py-2" style="min-width:310px; width:42%;">
                            Payment Modes
                            <span class="text-muted fw-normal ms-1" style="font-size:10px;">(uncheck = block)</span>
                        </th>
                        <th class="py-2" style="min-width:240px;">
                            Bank Accounts
                            <span class="text-muted fw-normal ms-1" style="font-size:10px;">(uncheck = block)</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group['users'] as $user)
                    @php
                        $key        = $group['type'].'-'.$user->id;
                        $perm       = $perms[$key] ?? null;
                        $savedModes = $perm?->allowed_modes ?? array_keys($allModes);
                        $savedBanks = $perm?->allowed_bank_ids ?? $accounts->pluck('id')->toArray();
                        $isUnrestricted = $perm === null;
                    @endphp
                    <tr class="{{ $loop->last ? '' : 'border-bottom' }}">
                        <td class="ps-3 py-2">
                            <div class="fw-semibold">{{ $user->name }}</div>
                            @if($user->email ?? false)
                            <div class="text-muted" style="font-size:10px;">{{ $user->email }}</div>
                            @endif
                            @if($isUnrestricted)
                            <span class="badge bg-success-subtle text-success border border-success-subtle mt-1" style="font-size:9px;">
                                <i class="bi bi-unlock me-1"></i>Unrestricted
                            </span>
                            @else
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle mt-1" style="font-size:9px;">
                                <i class="bi bi-lock me-1"></i>Custom
                            </span>
                            @endif
                        </td>

                        <td class="py-2">
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <button type="button" class="btn btn-link btn-sm p-0 text-success" style="font-size:10px;"
                                        onclick="setAll('modes-{{ $key }}', true)">All</button>
                                <span class="text-muted" style="font-size:10px;">/</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-danger" style="font-size:10px;"
                                        onclick="setAll('modes-{{ $key }}', false)">None</button>
                            </div>
                            <div class="d-flex flex-wrap gap-1" id="modes-{{ $key }}">
                                @foreach($allModes as $modeKey => $modeLabel)
                                @php $mChecked = in_array($modeKey, $savedModes); @endphp
                                <label class="perm-chip {{ $mChecked ? 'chip-mode-on' : 'chip-off' }}"
                                       id="lbl-{{ $key }}-{{ $modeKey }}">
                                    <input type="checkbox"
                                           name="users[{{ $key }}][modes][]"
                                           value="{{ $modeKey }}"
                                           {{ $mChecked ? 'checked' : '' }}
                                           onchange="toggleChip(this, 'mode')">
                                    {{ $modeLabel }}
                                </label>
                                @endforeach
                            </div>
                        </td>

                        <td class="py-2">
                            @if($accounts->isEmpty())
                                <span class="text-muted" style="font-size:11px;">No bank accounts</span>
                            @else
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <button type="button" class="btn btn-link btn-sm p-0 text-success" style="font-size:10px;"
                                        onclick="setAll('banks-{{ $key }}', true)">All</button>
                                <span class="text-muted" style="font-size:10px;">/</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-danger" style="font-size:10px;"
                                        onclick="setAll('banks-{{ $key }}', false)">None</button>
                            </div>
                            <div class="d-flex flex-column gap-1" id="banks-{{ $key }}">
                                @foreach($accounts as $acc)
                                @php $bChecked = in_array($acc->id, $savedBanks); @endphp
                                <label class="perm-chip {{ $bChecked ? 'chip-bank-on' : 'chip-off' }}"
                                       id="lbl-bank-{{ $key }}-{{ $acc->id }}">
                                    <input type="checkbox"
                                           name="users[{{ $key }}][banks][]"
                                           value="{{ $acc->id }}"
                                           {{ $bChecked ? 'checked' : '' }}
                                           onchange="toggleChip(this, 'bank')">
                                    <span>
                                        <strong>{{ $acc->display_label ?: $acc->bank_name }}</strong>
                                        <span class="text-muted"> &mdash; {{ $acc->account_no }}</span>
                                    </span>
                                </label>
                                @endforeach
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endforeach

@if($staff->isEmpty() && $centers->isEmpty() && $partners->isEmpty())
<div class="alert alert-secondary border-0 text-center py-4">
    <i class="bi bi-people fs-3 text-muted d-block mb-2"></i>
    No staff members, centers, or channel partners found.
</div>
@endif

<div class="d-flex gap-2 mb-5 pt-2 sticky-bottom-bar">
    <button type="submit" class="btn btn-primary px-5">
        <i class="bi bi-check-lg me-1"></i> Save All Permissions
    </button>
    <a href="{{ route('master.bank-accounts.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>

</form>

@endsection

@push('styles')
<style>
.perm-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 4px;
    border: 1px solid;
    cursor: pointer;
    font-size: 11px;
    transition: background .12s, border-color .12s;
    user-select: none;
}
.perm-chip input[type=checkbox] {
    width: 11px;
    height: 11px;
    flex-shrink: 0;
    cursor: pointer;
}
.chip-mode-on  { background: #eaf3de; border-color: #1D9E75 !important; }
.chip-bank-on  { background: #e6f1fb; border-color: #185FA5 !important; }
.chip-off      { background: #f8fafc; border-color: #dee2e6 !important; color: #6c757d; }

.sticky-bottom-bar {
    position: sticky;
    bottom: 0;
    background: rgba(255,255,255,.92);
    backdrop-filter: blur(4px);
    padding: 10px 0;
    border-top: 1px solid #e9ecef;
    z-index: 10;
}
</style>
@endpush

@push('scripts')
<script>
function toggleChip(input, type) {
    const label = input.closest('label');
    label.className = 'perm-chip ' + (input.checked
        ? (type === 'bank' ? 'chip-bank-on' : 'chip-mode-on')
        : 'chip-off');
}

function setAll(containerId, checked) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.querySelectorAll('input[type=checkbox]').forEach(input => {
        input.checked = checked;
        toggleChip(input, containerId.startsWith('banks-') ? 'bank' : 'mode');
    });
}
</script>
@endpush
