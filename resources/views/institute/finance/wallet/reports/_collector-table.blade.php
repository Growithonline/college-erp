@php
    // Build pivot: collector_id → [mode => {cnt, total}] + row total
    $allModes = $rows->flatten()->map(fn($r) => strtolower($r->payment_mode ?? 'other'))->unique()->sort()->values();
    $pivot = [];
    foreach ($rows as $collectorId => $modeRows) {
        $pivot[$collectorId] = [];
        foreach ($modeRows as $r) {
            $m = strtolower($r->payment_mode ?? 'other');
            $pivot[$collectorId][$m] = ['cnt' => $r->cnt, 'total' => (float) $r->total];
        }
    }
@endphp
<div class="table-responsive">
    <table class="table table-hover mb-0 align-middle small">
        <thead class="table-light">
            <tr>
                <th>Name</th>
                @foreach($allModes as $m)
                    <th class="text-end">
                        <span class="badge bg-{{ $modeColors[$m] ?? 'secondary' }}">{{ $modeLabels[$m] ?? ucfirst($m) }}</span>
                    </th>
                @endforeach
                <th class="text-end text-success">Total</th>
                @if(!empty($showCommission)) <th class="text-end text-warning">Commission</th> @endif
            </tr>
        </thead>
        <tbody>
            @foreach($pivot as $collectorId => $modes)
            @php
                $name     = $nameKey($collectorId);
                $rowTotal = collect($modes)->sum('total');
                $rowCnt   = collect($modes)->sum('cnt');
            @endphp
            <tr>
                <td>
                    <div class="fw-semibold">{{ $name }}</div>
                    <div class="text-muted" style="font-size:10px">{{ $rowCnt }} transactions</div>
                </td>
                @foreach($allModes as $m)
                <td class="text-end">
                    @if(isset($modes[$m]))
                        <div class="fw-semibold">₹{{ number_format($modes[$m]['total'], 2) }}</div>
                        <div class="text-muted" style="font-size:10px">{{ $modes[$m]['cnt'] }}</div>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                @endforeach
                <td class="text-end fw-bold text-success">₹{{ number_format($rowTotal, 2) }}</td>
                @if(!empty($showCommission))
                @php
                    $partner  = $partnerMap[$collectorId] ?? null;
                    $commPct  = (float) ($partner?->commission_percent ?? 0);
                    $commAmt  = round($rowTotal * $commPct / 100, 2);
                @endphp
                <td class="text-end">
                    @if($commPct > 0)
                        <div class="fw-semibold text-warning">₹{{ number_format($commAmt, 2) }}</div>
                        <div class="text-muted" style="font-size:10px">{{ $commPct }}%</div>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
        <tfoot class="table-dark fw-semibold">
            <tr>
                <td>Grand Total</td>
                @foreach($allModes as $m)
                @php $modeSum = collect($pivot)->sum(fn($modes) => $modes[$m]['total'] ?? 0); @endphp
                <td class="text-end">₹{{ number_format($modeSum, 2) }}</td>
                @endforeach
                <td class="text-end text-success">₹{{ number_format(collect($pivot)->sum(fn($modes) => collect($modes)->sum('total')), 2) }}</td>
                @if(!empty($showCommission))<td></td>@endif
            </tr>
        </tfoot>
    </table>
</div>
