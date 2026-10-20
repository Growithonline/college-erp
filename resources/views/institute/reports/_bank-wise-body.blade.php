{{-- Shared body of the bank-wise income report (on-screen page, print and PDF) --}}
@php $money = fn($v) => number_format((float) $v, 2); @endphp

@if($groups->isEmpty())
    <p class="bw-empty">No income found for the selected period.</p>
@else

{{-- 1. Which income source the money came from --}}
<div class="bw-title">Income source-wise</div>
<table class="bw-table bw-source">
    <thead>
        <tr><th>Income source</th><th class="r">Receipts</th><th class="r">Amount (₹)</th></tr>
    </thead>
    <tbody>
        @foreach($by_source as $source)
        <tr>
            <td>{{ $source['label'] }}</td>
            <td class="r">{{ $source['count'] }}</td>
            <td class="r">{{ $money($source['total']) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr><td>TOTAL</td><td class="r">{{ $grand_count }}</td><td class="r">{{ $money($grand_total) }}</td></tr>
    </tfoot>
</table>

{{-- 2. Bank-wise summary with payment-mode split --}}
<div class="bw-title">Summary — Bank-wise &amp; Payment-mode wise</div>
<table class="bw-table bw-summary">
    <thead>
        <tr>
            <th>Bank / Account</th>
            @foreach($modes as $modeKey => $modeLabel)
                <th class="r">{{ $modeLabel }}</th>
            @endforeach
            <th class="r">Total (₹)</th>
            <th class="r">Receipts</th>
        </tr>
    </thead>
    <tbody>
        @foreach($groups as $group)
        <tr>
            <td>
                <strong>{{ $group['title'] }}</strong>
                @if($group['bank'] || $group['account'])
                    <div class="bw-sub">{{ trim(($group['bank'] ?? '') . ' ' . ($group['account'] ? 'A/c ' . $group['account'] : '')) }}</div>
                @endif
            </td>
            @foreach($modes as $modeKey => $modeLabel)
                <td class="r">{{ ($group['by_mode'][$modeKey] ?? 0) > 0 ? $money($group['by_mode'][$modeKey]) : '—' }}</td>
            @endforeach
            <td class="r"><strong>{{ $money($group['total']) }}</strong></td>
            <td class="r">{{ $group['count'] }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>GRAND TOTAL</td>
            @foreach($modes as $modeKey => $modeLabel)
                <td class="r">{{ $money($mode_totals[$modeKey] ?? 0) }}</td>
            @endforeach
            <td class="r">{{ $money($grand_total) }}</td>
            <td class="r">{{ $grand_count }}</td>
        </tr>
    </tfoot>
</table>

{{-- 3. Every receipt, bank by bank --}}
@foreach($groups as $group)
<div class="bw-group">
    <div class="bw-group-head">
        <span><strong>{{ $group['title'] }}</strong>
            @if($group['bank'] || $group['account'])
                — {{ $group['bank'] }} @if($group['account']) · A/c {{ $group['account'] }} @endif @if($group['ifsc']) · IFSC {{ $group['ifsc'] }} @endif
            @endif
        </span>
        <span class="bw-group-total">₹{{ $money($group['total']) }}</span>
    </div>
    <table class="bw-table bw-detail">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Receipt No.</th>
                <th>Received from</th>
                <th>Income source / details</th>
                <th>Mode</th>
                <th>UTR / Cheque / Ref</th>
                <th>Received through</th>
                <th class="r">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($group['rows'] as $i => $e)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td class="nw">{{ $e['date']?->format('d-m-Y') }}</td>
                <td class="nw">{{ $e['receipt'] ?: '—' }}</td>
                <td>{{ $e['party'] ?: '—' }} @if($e['party_id'])<span class="bw-sub">{{ $e['party_id'] }}</span>@endif</td>
                <td><strong>{{ $e['label'] }}</strong>@if($e['purpose'])<div class="bw-sub">{{ $e['purpose'] }}</div>@endif</td>
                <td>{{ $allModes[$e['mode']] ?? strtoupper((string) $e['mode']) }}</td>
                <td>{{ $e['ref'] ?: '—' }}</td>
                <td>{{ $e['through'] ?: '—' }}</td>
                <td class="r">{{ $money($e['amount']) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8">
                    Total — {{ $group['title'] }}
                    <span class="bw-modes">
                        @foreach($group['by_mode'] as $modeKey => $amount)
                            {{ $allModes[$modeKey] ?? strtoupper($modeKey) }}: ₹{{ $money($amount) }}@if(!$loop->last) &nbsp;|&nbsp; @endif
                        @endforeach
                    </span>
                </td>
                <td class="r">₹{{ $money($group['total']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endforeach

@endif
