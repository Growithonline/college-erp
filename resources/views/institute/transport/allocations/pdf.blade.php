<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; }
    .header { background: #1a1a2e; color: #fff; padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; }
    .header h2 { font-size: 15px; margin-bottom: 2px; }
    .header small { font-size: 10px; opacity: .8; }
    .badge-title { background: #fff; color: #1a1a2e; font-size: 10px; font-weight: bold; padding: 3px 8px; border-radius: 4px; }
    .section { padding: 12px 16px; border-bottom: 1px solid #e8e8e8; }
    .section-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; color: #888; margin-bottom: 6px; }
    .row { display: flex; gap: 8px; }
    .col { flex: 1; }
    .label { font-size: 9px; color: #888; }
    .value { font-size: 11px; font-weight: bold; margin-top: 1px; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th { background: #f5f5f5; padding: 5px 8px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .3px; color: #555; border-bottom: 1px solid #ddd; }
    td { padding: 5px 8px; border-bottom: 1px solid #f0f0f0; }
    .text-right { text-align: right; }
    .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-info { background: #d1ecf1; color: #0c5460; }
    .summary-box { background: #f8f9fc; border: 1px solid #e0e3ea; border-radius: 6px; padding: 10px 14px; margin: 10px 16px; }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
    .summary-row:last-child { margin-bottom: 0; border-top: 1px solid #ddd; padding-top: 5px; font-weight: bold; }
    .footer { text-align: center; font-size: 9px; color: #aaa; padding: 10px 16px; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div>
        <h2>{{ $institute?->name ?? 'Institute' }}</h2>
        <small>{{ $institute?->address ?? '' }}</small>
    </div>
    <div class="badge-title">Transport Invoice</div>
</div>

{{-- Student Info --}}
<div class="section">
    <div class="section-title">Student Details</div>
    <div class="row">
        <div class="col">
            <div class="label">Name</div>
            <div class="value">{{ $allocation->student?->name ?? '—' }}</div>
        </div>
        <div class="col">
            <div class="label">Roll No</div>
            <div class="value">{{ $allocation->student?->roll_no ?? '—' }}</div>
        </div>
        <div class="col">
            <div class="label">Course</div>
            <div class="value">{{ $allocation->student?->stream?->course?->name ?? '—' }}</div>
        </div>
        <div class="col">
            <div class="label">Session</div>
            <div class="value">{{ $allocation->session?->name ?? '—' }}</div>
        </div>
    </div>
</div>

{{-- Route Info --}}
<div class="section">
    <div class="section-title">Transport Details</div>
    <div class="row">
        <div class="col">
            <div class="label">Route</div>
            <div class="value">{{ $allocation->route?->name ?? '—' }}</div>
        </div>
        <div class="col">
            <div class="label">Stop</div>
            <div class="value">{{ $allocation->stop?->stop_name ?? 'Direct Route' }}</div>
        </div>
        <div class="col">
            <div class="label">Vehicle</div>
            <div class="value">{{ $allocation->vehicle?->vehicle_no ?? '—' }}</div>
        </div>
        <div class="col">
            <div class="label">Driver</div>
            <div class="value">{{ $allocation->driver?->name ?? '—' }}</div>
        </div>
    </div>
    <div class="row" style="margin-top:8px;">
        <div class="col">
            <div class="label">Billing</div>
            <div class="value">{{ ucfirst(str_replace('_', ' ', $allocation->route?->billing_frequency ?? '')) }}</div>
        </div>
        <div class="col">
            <div class="label">Start Date</div>
            <div class="value">{{ $allocation->start_date?->format('d M Y') ?? '—' }}</div>
        </div>
        <div class="col">
            <div class="label">Status</div>
            <div class="value">{{ ucfirst($allocation->status) }}</div>
        </div>
        <div class="col">
            <div class="label">Generated On</div>
            <div class="value">{{ now()->format('d M Y') }}</div>
        </div>
    </div>
</div>

{{-- Fee Summary --}}
<div class="summary-box">
    <div class="summary-row">
        <span>Transport Fee</span>
        <span>₹{{ number_format((float) $allocation->fee_amount, 2) }}</span>
    </div>
    <div class="summary-row">
        <span>Total Charged</span>
        <span>₹{{ number_format((float) $allocation->charged_amount, 2) }}</span>
    </div>
    <div class="summary-row">
        <span>Total Paid</span>
        <span style="color:#155724;">₹{{ number_format((float) $allocation->paid_amount, 2) }}</span>
    </div>
    <div class="summary-row">
        <span>Balance Due</span>
        <span style="color:{{ $allocation->balance > 0 ? '#856404' : '#155724' }};">
            ₹{{ number_format(max(0, (float) $allocation->balance), 2) }}
        </span>
    </div>
</div>

{{-- Payments Table --}}
@if($payments->count() > 0)
<div class="section" style="border-bottom:none;">
    <div class="section-title">Payment History</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Mode</th>
                <th>Reference</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $i => $p)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $p->payment_date?->format('d M Y') ?? '—' }}</td>
                <td>{{ ucfirst($p->payment_mode) }}</td>
                <td>{{ $p->reference_no ?? '—' }}</td>
                <td class="text-right" style="font-weight:bold; color:#155724;">₹{{ number_format((float) $p->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="footer">
    This is a computer-generated document. No signature required. | {{ $institute?->name ?? '' }} — Transport Management System
</div>
</body>
</html>
