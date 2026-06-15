<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Practical Token Collection Report</title>
<style>
    @page { size: A4 landscape; margin: 10mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #111; background: #fff; }
    .page-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 12px; }
    .page-header h2 { font-size: 16px; font-weight: 700; }
    .page-header p  { font-size: 11px; color: #555; margin-top: 2px; }
    .filter-info { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;
                   padding: 6px 10px; margin-bottom: 12px; font-size: 11px; display: flex; gap: 20px; }
    .filter-info span { color: #444; }
    .filter-info strong { color: #111; }
    .batch-block { margin-bottom: 18px; page-break-inside: avoid; }
    .batch-title { background: #1e293b; color: #fff; padding: 5px 10px;
                   font-size: 12px; font-weight: 700; border-radius: 4px 4px 0 0;
                   display: flex; justify-content: space-between; align-items: center; }
    .batch-stats { display: flex; gap: 0; border: 1px solid #dee2e6; border-top: none;
                   border-bottom: none; }
    .stat-box { flex: 1; text-align: center; padding: 5px 4px; border-right: 1px solid #dee2e6; }
    .stat-box:last-child { border-right: none; }
    .stat-box .lbl { font-size: 9px; color: #666; text-transform: uppercase; }
    .stat-box .val { font-size: 12px; font-weight: 700; }
    table { width: 100%; border-collapse: collapse; border: 1px solid #dee2e6; }
    thead th { background: #e2e8f0; font-size: 10px; font-weight: 700; text-transform: uppercase;
               padding: 5px 6px; border: 1px solid #dee2e6; text-align: left; }
    tbody td { padding: 4px 6px; border: 1px solid #e5e7eb; font-size: 10px; vertical-align: middle; }
    tbody tr:nth-child(even) { background: #f9fafb; }
    tfoot td { padding: 5px 6px; border: 1px solid #dee2e6; font-weight: 700; font-size: 10px;
               background: #f1f5f9; }
    .text-end { text-align: right; }
    .badge-posted { background: #d1fae5; color: #065f46; padding: 1px 5px; border-radius: 3px; font-size: 9px; }
    .badge-other  { background: #f1f5f9; color: #475569; padding: 1px 5px; border-radius: 3px; font-size: 9px; }
    .grand-total  { margin-top: 10px; background: #1e293b; color: #fff; padding: 7px 12px;
                    border-radius: 4px; font-size: 12px; font-weight: 700;
                    display: flex; justify-content: space-between; }
    @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
</style>
</head>
<body>

<div class="page-header">
    <h2>{{ $instituteName }}</h2>
    <p>Practical Token Collection Report</p>
    <p>
        Session: <strong>{{ $sessionName }}</strong>
        @if($courseName) &nbsp;|&nbsp; Course: <strong>{{ $courseName }}</strong> @endif
        @if($batchTitle) &nbsp;|&nbsp; Batch: <strong>{{ $batchTitle }}</strong> @endif
        &nbsp;|&nbsp; Generated: <strong>{{ now()->format('d M Y, h:i A') }}</strong>
    </p>
</div>

@foreach($batches as $batch)
@php
    $batchTotal    = (float) $batch->entries->sum('amount');
    $batchFine     = (float) $batch->entries->sum('fine');
    $batchDiscount = (float) $batch->entries->sum('discount');
    $tokenAmt      = (float) $batch->token_amount;
    $entryCount    = $batch->entries->count();
    $remaining     = max(0, ($tokenAmt * $entryCount) - $batchTotal);
@endphp
<div class="batch-block">
    <div class="batch-title">
        <span>
            {{ $batch->title ?? ('Token #' . $batch->id) }}
            — {{ $batch->course?->name ?? '-' }}
            @if($batch->subject) / {{ $batch->subject->name }} @endif
            &nbsp;| Sem {{ $batch->semester }}
        </span>
        <span style="font-weight:400;font-size:11px;">{{ $batch->collection_date?->format('d M Y') }}</span>
    </div>
    <div class="batch-stats">
        <div class="stat-box"><div class="lbl">Token Amt</div><div class="val">₹{{ number_format($tokenAmt, 2) }}</div></div>
        <div class="stat-box"><div class="lbl">Students</div><div class="val">{{ $entryCount }}</div></div>
        <div class="stat-box"><div class="lbl" style="color:#065f46">Collected</div><div class="val" style="color:#065f46">₹{{ number_format($batchTotal, 2) }}</div></div>
        <div class="stat-box"><div class="lbl" style="color:#92400e">Fine</div><div class="val" style="color:#b45309">₹{{ number_format($batchFine, 2) }}</div></div>
        <div class="stat-box"><div class="lbl">Discount</div><div class="val" style="color:#0369a1">₹{{ number_format($batchDiscount, 2) }}</div></div>
        <div class="stat-box"><div class="lbl" style="color:#991b1b">Remaining</div><div class="val" style="color:#dc2626">₹{{ number_format($remaining, 2) }}</div></div>
    </div>

    @if($batch->entries->isNotEmpty())
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student Name</th>
                <th>Student ID</th>
                <th>Roll No</th>
                <th>Father Name</th>
                <th>Mother Name</th>
                <th class="text-end">Amount (₹)</th>
                <th class="text-end">Fine (₹)</th>
                <th class="text-end">Discount (₹)</th>
                <th>Status</th>
                <th>Date</th>
                <th>Posted By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($batch->entries as $j => $entry)
            <tr>
                <td>{{ $j + 1 }}</td>
                <td>{{ $entry->student?->name ?? '-' }}</td>
                <td>{{ $entry->student?->student_uid ?? '-' }}</td>
                <td>{{ $entry->student?->roll_no ?? '-' }}</td>
                <td>{{ $entry->student?->father_name ?? '-' }}</td>
                <td>{{ $entry->student?->mother_name ?? '-' }}</td>
                <td class="text-end">{{ number_format((float)$entry->amount, 2) }}</td>
                <td class="text-end">{{ number_format((float)$entry->fine, 2) }}</td>
                <td class="text-end">{{ number_format((float)$entry->discount, 2) }}</td>
                <td>
                    <span class="{{ $entry->status === 'posted' ? 'badge-posted' : 'badge-other' }}">
                        {{ ucfirst($entry->status ?? 'pending') }}
                    </span>
                </td>
                <td>{{ $entry->posted_at?->format('d M Y') ?? '-' }}</td>
                <td>{{ $entry->entered_by_name }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-end">Batch Total:</td>
                <td class="text-end" style="color:#065f46">{{ number_format($batchTotal, 2) }}</td>
                <td class="text-end" style="color:#b45309">{{ number_format($batchFine, 2) }}</td>
                <td class="text-end" style="color:#0369a1">{{ number_format($batchDiscount, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    @else
    <p style="padding:8px;border:1px solid #dee2e6;border-top:none;color:#888;">No entries in this batch.</p>
    @endif
</div>
@endforeach

<div class="grand-total">
    <span>GRAND TOTAL — {{ $batches->count() }} Batch(es) &nbsp;|&nbsp; {{ $grandStudents }} Student(s)</span>
    <span>₹{{ number_format($grandTotal, 2) }}</span>
</div>

<script>window.onload = () => window.print();</script>
</body>
</html>
