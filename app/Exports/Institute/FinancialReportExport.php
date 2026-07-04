<?php

namespace App\Exports\Institute;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FinancialReportExport implements WithMultipleSheets
{
    public function __construct(private int $id) {}

    public function sheets(): array
    {
        return [
            $this->sessionsSheet(),
            $this->feeCollectionsSheet(),
            $this->expensesSheet(),
            $this->salarySheet(),
            $this->employeesSheet(),
        ];
    }

    private function sessionsSheet(): object
    {
        $id = $this->id;

        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}

            public function title(): string { return 'Sessions Overview'; }

            public function headings(): array
            {
                return [
                    'Session Name', 'Start Date', 'End Date', 'Active',
                    'Total Students', 'Fee Invoiced (₹)', 'Fee Collected (₹)',
                    'Total Discount (₹)', 'Total Expenses (₹)', 'Net Income (₹)',
                ];
            }

            public function collection()
            {
                $sessions = DB::table('academic_sessions')
                    ->where('institute_id', $this->id)
                    ->orderByDesc('start_date')
                    ->get();

                return $sessions->map(function ($ses) {
                    $fee = DB::table('fee_invoices')
                        ->where('institute_id', $this->id)
                        ->where('academic_session_id', $ses->id)
                        ->selectRaw('
                            COALESCE(SUM(total_amount), 0) as invoiced,
                            COALESCE(SUM(paid_amount), 0)  as collected,
                            COALESCE(SUM(discount), 0)     as discount
                        ')
                        ->first();

                    $expenses = DB::table('expenses')
                        ->where('institute_id', $this->id)
                        ->where('academic_session_id', $ses->id)
                        ->sum('amount') ?? 0;

                    $students = DB::table('students')
                        ->where('institute_id', $this->id)
                        ->where('academic_session_id', $ses->id)
                        ->count();

                    return [
                        $ses->name,
                        $ses->start_date,
                        $ses->end_date ?? '—',
                        $ses->is_active ? 'Yes' : 'No',
                        $students,
                        number_format($fee->invoiced, 2),
                        number_format($fee->collected, 2),
                        number_format($fee->discount, 2),
                        number_format($expenses, 2),
                        number_format($fee->collected - $expenses, 2),
                    ];
                });
            }
        };
    }

    private function feeCollectionsSheet(): object
    {
        $id = $this->id;

        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}

            public function title(): string { return 'Fee Collections'; }

            public function headings(): array
            {
                return [
                    'Invoice No', 'Payment Date', 'Session', 'Semester',
                    'Student Name', 'Course', 'Stream',
                    'Total (₹)', 'Discount (₹)', 'Paid (₹)', 'Remaining (₹)',
                    'Payment Mode', 'Transaction Ref', 'Collected By',
                ];
            }

            public function collection()
            {
                return DB::table('fee_invoices as fi')
                    ->join('students as s', 's.id', '=', 'fi.student_id')
                    ->leftJoin('academic_sessions as ses', 'ses.id', '=', 'fi.academic_session_id')
                    ->leftJoin('course_streams as cs', 'cs.id', '=', 's.course_stream_id')
                    ->leftJoin('courses as c', 'c.id', '=', 'cs.course_id')
                    ->where('fi.institute_id', $this->id)
                    ->orderByDesc('fi.payment_date')
                    ->select([
                        'fi.invoice_no',
                        'fi.payment_date',
                        'ses.name as session',
                        DB::raw('COALESCE(fi.semester, "—") as semester'),
                        's.name as student_name',
                        'c.name as course',
                        'cs.name as stream',
                        'fi.total_amount',
                        'fi.discount',
                        'fi.paid_amount',
                        DB::raw('COALESCE(fi.remaining_due, 0) as remaining_due'),
                        'fi.payment_mode',
                        'fi.transaction_ref',
                        'fi.collected_by',
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    private function expensesSheet(): object
    {
        $id = $this->id;

        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}

            public function title(): string { return 'Expenses'; }

            public function headings(): array
            {
                return [
                    'Date', 'Session', 'Vendor', 'Bill No',
                    'Description', 'Amount (₹)', 'Payment Mode',
                ];
            }

            public function collection()
            {
                return DB::table('expenses as e')
                    ->leftJoin('academic_sessions as ses', 'ses.id', '=', 'e.academic_session_id')
                    ->where('e.institute_id', $this->id)
                    ->orderByDesc('e.expense_date')
                    ->select([
                        'e.expense_date',
                        'ses.name as session',
                        'e.vendor_name',
                        'e.bill_no',
                        'e.description',
                        'e.amount',
                        'e.payment_mode',
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    private function salarySheet(): object
    {
        $id = $this->id;

        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}

            public function title(): string { return 'Salary Records'; }

            public function headings(): array
            {
                return [
                    'Staff Name', 'Month', 'Year',
                    'Basic (₹)', 'Allowances (₹)', 'Deductions (₹)',
                    'Net Payable (₹)', 'Paid (₹)',
                    'Payment Date', 'Payment Mode', 'Status',
                ];
            }

            public function collection()
            {
                return DB::table('salary_records as sr')
                    ->join('staff_members as sm', 'sm.id', '=', 'sr.staff_member_id')
                    ->where('sr.institute_id', $this->id)
                    ->orderByDesc('sr.salary_year')
                    ->orderByDesc('sr.salary_month')
                    ->select([
                        'sm.name as staff_name',
                        'sr.salary_month',
                        'sr.salary_year',
                        'sr.basic_salary',
                        'sr.allowances',
                        'sr.deductions',
                        'sr.net_payable',
                        'sr.paid_amount',
                        'sr.payment_date',
                        'sr.payment_mode',
                        'sr.status',
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    private function employeesSheet(): object
    {
        $id = $this->id;

        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}

            public function title(): string { return 'Employees'; }

            public function headings(): array
            {
                return [
                    'Name', 'Email', 'Mobile', 'Role',
                    'Joining Date', 'Status',
                ];
            }

            public function collection()
            {
                return DB::table('staff_members as sm')
                    ->leftJoin('staff_roles as sr', 'sr.id', '=', 'sm.staff_role_id')
                    ->where('sm.institute_id', $this->id)
                    ->whereNull('sm.deleted_at')
                    ->orderBy('sm.name')
                    ->select([
                        'sm.name',
                        'sm.email',
                        'sm.mobile',
                        'sr.name as role',
                        'sm.joining_date',
                        DB::raw("IF(sm.status = 1, 'Active', 'Inactive') as status"),
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }
}
