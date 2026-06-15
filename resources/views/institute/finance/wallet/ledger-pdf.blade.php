<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 7.5px; color: #333; margin: 0; }
    h2  { text-align: center; font-size: 13px; margin: 0 0 2px; color: #1a1a2e; }
    h3  { text-align: center; font-size: 10px; margin: 0 0 3px; color: #555; font-weight: normal; }
    .subtitle { text-align: center; font-size: 8px; color: #666; margin-bottom: 8px; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #1a1a2e; color: #fff; }
    th  { padding: 4px 3px; text-align: left; font-size: 7px; font-weight: bold; }
    th.num { text-align: right; }
    td  { padding: 3px; border-bottom: 1px solid #eee; vertical-align: middle; }
    td.num { text-align: right; }
    tr:nth-child(even) td { background: #fafafa; }
    .income  { color: #198754; font-weight: bold; }
    .expense { color: #dc3545; font-weight: bold; }
    .bal-pos { color: #198754; font-weight: bold; }
    .bal-neg { color: #dc3545; font-weight: bold; }
    .badge   { padding: 1px 4px; border-radius: 3px; font-size: 6.5px; }
    .badge-inc { background: #d1fae5; color: #065f46; }
    .badge-exp { background: #fee2e2; color: #991b1b; }
    .day-hdr td { background: #e8f0fe !important; font-weight: bold; color: #1a1a2e;
                  font-size: 7px; padding: 2px 3px; border-top: 1px solid #aac; }
    tfoot tr { background: #1a1a2e; }
    tfoot td { color: #fff; font-weight: bold; border-top: 2px solid #1a1a2e; }
    .muted { color: #777; }
</style>
</head>
<body>
<h2>{{ $instituteName }}</h2>
<h3>Wallet Ledger</h3>
<p class="subtitle">
    {{ $filterLabel }}
    &nbsp;|&nbsp; Generated: {{ now()->format('d-m-Y H:i') }}
    &nbsp;|&nbsp; Total Records: {{ $transactions->count() }}
</p>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Session</th>
            <th>Date</th>
            <th>Remark</th>
            <th>Category</th>
            <th>Receipt No.</th>
            <th>Ref. No.</th>
            <th>Type</th>
            <th>Bank Account</th>
            <th class="num">Income</th>
            <th class="num">Expense</th>
            <th class="num">Op. Bal</th>
            <th class="num">Balance</th>
            <th>User</th>
        </tr>
    </thead>
    <tbody>
        @php $totalIncome = 0; $totalExpense = 0; $prevDate = null; @endphp
        @foreach($transactions as $i => $tx)
        @php
            $sd       = $sourceData[$tx->id] ?? [];
            $isIncome = str_starts_with($sd['category'] ?? '', 'INCOME');
            $totalIncome  += $tx->credit;
            $totalExpense += $tx->debit;
            $curDate  = $tx->date->toDateString();
        @endphp
        @if($curDate !== $prevDate)
        <tr class="day-hdr">
            <td colspan="14">{{ $tx->date->format('d F Y, l') }}</td>
        </tr>
        @php $prevDate = $curDate; @endphp
        @endif
        <tr>
            <td class="muted">{{ $i + 1 }}</td>
            <td class="muted">{{ $tx->session?->name ?? '-' }}</td>
            <td style="white-space:nowrap">{{ $tx->date->format('d-m-Y') }}</td>
            <td style="max-width:85px;overflow:hidden">{{ Str::limit($tx->des, 32) }}</td>
            <td><span class="badge {{ $isIncome ? 'badge-inc' : 'badge-exp' }}">{{ $sd['category'] ?? '-' }}</span></td>
            <td>{{ $sd['receipt_no'] ?? '-' }}</td>
            <td class="muted">{{ $sd['payment_ref'] ?? '-' }}</td>
            <td>{{ $sd['pay_type'] ?? '-' }}</td>
            <td class="muted" style="max-width:65px;overflow:hidden">{{ $sd['bank_account'] ?? '-' }}</td>
            <td class="num income">{{ $tx->credit > 0 ? number_format($tx->credit, 2) : '-' }}</td>
            <td class="num expense">{{ $tx->debit > 0 ? number_format($tx->debit, 2) : '-' }}</td>
            <td class="num muted">{{ number_format($tx->op_bal, 2) }}</td>
            <td class="num {{ $tx->cl_bal >= 0 ? 'bal-pos' : 'bal-neg' }}">{{ number_format($tx->cl_bal, 2) }}</td>
            <td class="muted">{{ $sd['user_name'] ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="9" class="num" style="color:#fff">Grand Total</td>
            <td class="num" style="color:#86efac">{{ number_format($totalIncome, 2) }}</td>
            <td class="num" style="color:#fca5a5">{{ number_format($totalExpense, 2) }}</td>
            <td colspan="3"></td>
        </tr>
    </tfoot>
</table>
</body>
</html>
