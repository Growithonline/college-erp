<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Refund Voucher {{ $refund->voucher_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111; }
        .box { max-width: 720px; margin: 0 auto; border: 1px solid #333; padding: 24px; }
        h2, h3 { margin: 0; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        td { padding: 8px 6px; border-bottom: 1px solid #ccc; }
        td:first-child { width: 36%; color: #555; }
        .sign { display: flex; justify-content: space-between; margin-top: 56px; font-size: 13px; }
        @media print { .noprint { display: none; } }
    </style>
</head>
<body>
<div class="box">
    <h2>{{ $institute?->name }}</h2>
    <h3 style="margin-top:6px">REFUND VOUCHER</h3>
    <table>
        <tr><td>Voucher no.</td><td><strong>{{ $refund->voucher_no }}</strong></td></tr>
        <tr><td>Paid on</td><td>{{ $refund->paid_on?->format('d M Y') }}</td></tr>
        <tr><td>Student</td><td>{{ $refund->student?->name }} ({{ $refund->student?->student_uid }})</td></tr>
        <tr><td>Session of payment</td><td>{{ $refund->session?->name }}</td></tr>
        <tr><td>Reason</td><td>{{ $refund->reason }}</td></tr>
        <tr><td>Amount refunded</td><td><strong>₹{{ number_format((float) $refund->amount, 2) }}</strong></td></tr>
        <tr><td>Paid by</td><td>{{ $modes[$refund->payment_mode] ?? $refund->payment_mode }}
            @if($refund->bankAccount) — {{ $refund->bankAccount->display_label ?: $refund->bankAccount->bank_name }}@endif
            @if($refund->reference_no) · Ref {{ $refund->reference_no }}@endif</td></tr>
    </table>
    <div class="sign"><span>Received by (student / guardian)</span><span>Authorised signatory</span></div>
    <p class="noprint" style="text-align:center;margin-top:28px"><button onclick="window.print()">Print</button></p>
</div>
</body>
</html>
