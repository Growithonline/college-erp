<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; padding: 20px; }
    .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 12px; }
    .institute-name { font-size: 18px; font-weight: bold; }
    .payslip-title { font-size: 13px; font-weight: bold; text-align: center;
                     background: #f0f0f0; border: 1px solid #ccc; padding: 5px; margin-bottom: 12px; }
    .two-col { display: flex; gap: 20px; margin-bottom: 12px; }
    .two-col > div { flex: 1; }
    .label { color: #555; font-size: 10px; }
    .value { font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    th { background: #e8e8e8; padding: 5px 8px; text-align: left; font-size: 10px; border: 1px solid #ccc; }
    td { padding: 4px 8px; border: 1px solid #ccc; vertical-align: top; }
    .text-right { text-align: right; }
    .total-row td { font-weight: bold; background: #f5f5f5; }
    .net-row td { font-weight: bold; font-size: 13px; background: #222; color: #fff; }
    .footer { margin-top: 24px; display: flex; justify-content: space-between; }
    .sign-box { border-top: 1px solid #333; width: 160px; text-align: center; padding-top: 4px; font-size: 10px; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; }
    .badge-paid { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .badge-draft { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
    .divider { border: none; border-top: 1px dashed #bbb; margin: 8px 0; }
    small { font-size: 10px; color: #666; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
            <div class="institute-name">{{ $institute->name ?? 'Institute Name' }}</div>
            <div style="font-size:10px; color:#555;">{{ $institute->address ?? '' }}</div>
        </div>
        <div style="text-align:right;">
            <span class="badge {{ $record->status === 'paid' ? 'badge-paid' : 'badge-draft' }}">
                {{ strtoupper($record->status) }}
            </span>
            @if($record->payment_date)
                <div style="font-size:10px; margin-top:4px;">Paid: {{ $record->payment_date->format('d M Y') }}</div>
            @endif
        </div>
    </div>
</div>

<div class="payslip-title">
    SALARY SLIP — {{ \Carbon\Carbon::createFromDate($record->salary_year, $record->salary_month, 1)->format('F Y') }}
</div>

{{-- Employee Info --}}
<div class="two-col" style="margin-bottom:14px;">
    <div>
        <table style="border:none;">
            <tr><td style="border:none; width:110px;" class="label">Employee Name</td>
                <td style="border:none;" class="value">{{ $record->staffMember?->name ?? '—' }}</td></tr>
            <tr><td style="border:none;" class="label">Designation</td>
                <td style="border:none;">{{ $record->staffMember?->role?->name ?? '—' }}</td></tr>
            <tr><td style="border:none;" class="label">Category</td>
                <td style="border:none;">{{ $record->staffMember?->staff_category ?? '—' }}</td></tr>
            <tr><td style="border:none;" class="label">Joining Date</td>
                <td style="border:none;">{{ $record->staffMember?->joining_date?->format('d M Y') ?? '—' }}</td></tr>
        </table>
    </div>
    <div>
        <table style="border:none;">
            <tr><td style="border:none; width:110px;" class="label">Payroll Type</td>
                <td style="border:none;" class="value">{{ ucfirst($record->staffMember?->payroll_type ?? 'monthly') }}</td></tr>
            <tr><td style="border:none;" class="label">Payment Mode</td>
                <td style="border:none;">{{ ucfirst($record->payment_mode ?? '—') }}</td></tr>
            @if($record->bankAccount)
            <tr><td style="border:none;" class="label">Bank Account</td>
                <td style="border:none;">{{ $record->bankAccount?->account_name ?? '—' }}</td></tr>
            @endif
        </table>
    </div>
</div>

<hr class="divider">

{{-- Earnings & Deductions --}}
<div class="two-col">
    {{-- Earnings --}}
    <div>
        <table>
            <thead>
                <tr><th colspan="2">Earnings</th></tr>
                <tr><th>Component</th><th class="text-right">Amount (₹)</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td class="text-right">{{ number_format($record->basic_salary, 2) }}</td>
                </tr>
                @if($record->hra > 0)
                <tr>
                    <td>HRA ({{ $record->staffMember?->hra_percent ?? 0 }}%)</td>
                    <td class="text-right">{{ number_format($record->hra, 2) }}</td>
                </tr>
                @endif
                @if($record->da > 0)
                <tr>
                    <td>DA ({{ $record->staffMember?->da_percent ?? 0 }}%)</td>
                    <td class="text-right">{{ number_format($record->da, 2) }}</td>
                </tr>
                @endif
                @if($record->ta > 0)
                <tr>
                    <td>Travel Allowance</td>
                    <td class="text-right">{{ number_format($record->ta, 2) }}</td>
                </tr>
                @endif
                @if($record->medical > 0)
                <tr>
                    <td>Medical Allowance</td>
                    <td class="text-right">{{ number_format($record->medical, 2) }}</td>
                </tr>
                @endif
                @if($record->overtime_amount > 0)
                <tr>
                    <td>Overtime</td>
                    <td class="text-right">{{ number_format($record->overtime_amount, 2) }}</td>
                </tr>
                @endif
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>Gross Earnings</td>
                    <td class="text-right">{{ number_format((float)$record->basic_salary + (float)$record->allowances, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Deductions --}}
    <div>
        <table>
            <thead>
                <tr><th colspan="2">Deductions</th></tr>
                <tr><th>Component</th><th class="text-right">Amount (₹)</th></tr>
            </thead>
            <tbody>
                @if($record->absence_deduction > 0)
                <tr>
                    <td>Absence Deduction</td>
                    <td class="text-right">{{ number_format($record->absence_deduction, 2) }}</td>
                </tr>
                @endif
                @if($record->pf_employee > 0)
                <tr>
                    <td>PF (Employee 12%)</td>
                    <td class="text-right">{{ number_format($record->pf_employee, 2) }}</td>
                </tr>
                @endif
                @if($record->esi_employee > 0)
                <tr>
                    <td>ESI (Employee 0.75%)</td>
                    <td class="text-right">{{ number_format($record->esi_employee, 2) }}</td>
                </tr>
                @endif
                @if($record->tds > 0)
                <tr>
                    <td>TDS</td>
                    <td class="text-right">{{ number_format($record->tds, 2) }}</td>
                </tr>
                @endif
                @if($record->professional_tax > 0)
                <tr>
                    <td>Professional Tax</td>
                    <td class="text-right">{{ number_format($record->professional_tax, 2) }}</td>
                </tr>
                @endif
                @if($record->loan_deduction > 0)
                <tr>
                    <td>Loan / Advance EMI</td>
                    <td class="text-right">{{ number_format($record->loan_deduction, 2) }}</td>
                </tr>
                @endif
                @if($record->deductions == 0)
                <tr><td colspan="2" style="color:#888; font-style:italic;">No deductions</td></tr>
                @endif
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>Total Deductions</td>
                    <td class="text-right">{{ number_format($record->deductions, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Employer contributions note --}}
        @if($record->pf_employer > 0 || $record->esi_employer > 0)
        <table style="margin-top:8px; background:#fafafa;">
            <thead><tr><th colspan="2" style="font-size:9px;">Employer Contributions (not deducted from salary)</th></tr></thead>
            <tbody>
                @if($record->pf_employer > 0)
                <tr><td style="font-size:10px;">PF Employer (12%)</td>
                    <td class="text-right" style="font-size:10px;">₹{{ number_format($record->pf_employer, 2) }}</td></tr>
                @endif
                @if($record->esi_employer > 0)
                <tr><td style="font-size:10px;">ESI Employer (3.25%)</td>
                    <td class="text-right" style="font-size:10px;">₹{{ number_format($record->esi_employer, 2) }}</td></tr>
                @endif
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- Net Payable --}}
<table>
    <tr class="net-row">
        <td style="width:70%;">NET PAYABLE</td>
        <td class="text-right">₹ {{ number_format($record->net_payable, 2) }}</td>
    </tr>
</table>

@if($record->remarks)
<p style="font-size:10px; color:#555; margin-bottom:12px;"><strong>Remarks:</strong> {{ $record->remarks }}</p>
@endif

<p style="font-size:9px; color:#888; margin-bottom:16px;">
    * This is a system-generated payslip and does not require a physical signature.
    PF calculated on min(Basic, ₹15,000). ESI applicable if gross ≤ ₹21,000.
</p>

<div class="footer">
    <div class="sign-box">Employee Signature</div>
    <div class="sign-box">Accounts / HR</div>
    <div class="sign-box">Principal / Director</div>
</div>

</body>
</html>
