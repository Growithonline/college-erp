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
            $this->staffSheet(),
            $this->libraryStaffSheet(),
            $this->transportDriversSheet(),
            $this->transportVehiclesSheet(),
            $this->transportRoutesSheet(),
            $this->routeStopsSheet(),
            $this->centersSheet(),
            $this->channelPartnersSheet(),
            $this->libraryBooksSheet(),
        ];
    }

    // ── 1. Sessions Overview ─────────────────────────────────────────────────

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
                return DB::table('academic_sessions')
                    ->where('institute_id', $this->id)
                    ->orderByDesc('start_date')
                    ->get()
                    ->map(function ($ses) {
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

    // ── 2. Fee Collections ───────────────────────────────────────────────────

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
                        'fi.invoice_no', 'fi.payment_date',
                        'ses.name as session',
                        DB::raw('COALESCE(fi.semester, "—") as semester'),
                        's.name as student_name',
                        'c.name as course', 'cs.name as stream',
                        'fi.total_amount', 'fi.discount', 'fi.paid_amount',
                        DB::raw('COALESCE(fi.remaining_due, 0) as remaining_due'),
                        'fi.payment_mode', 'fi.transaction_ref', 'fi.collected_by',
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 3. Expenses ──────────────────────────────────────────────────────────

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
                        'e.expense_date', 'ses.name as session',
                        'e.vendor_name', 'e.bill_no',
                        'e.description', 'e.amount', 'e.payment_mode',
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 4. Salary Records ────────────────────────────────────────────────────

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
                    'Employee Name', 'Department', 'Designation', 'Month', 'Year',
                    'Basic (₹)', 'Allowances (₹)', 'Gross (₹)',
                    'Deductions (₹)', 'Net Salary (₹)',
                    'Payment Date', 'Payment Mode', 'Status',
                ];
            }
            public function collection()
            {
                return DB::table('employee_salary_disbursements as esd')
                    ->join('employees as e', 'e.id', '=', 'esd.employee_id')
                    ->leftJoin('employee_departments as dept', 'dept.id', '=', 'e.employee_department_id')
                    ->leftJoin('employee_designations as desig', 'desig.id', '=', 'e.employee_designation_id')
                    ->where('esd.institute_id', $this->id)
                    ->orderByDesc('esd.year')
                    ->orderByDesc('esd.month')
                    ->select([
                        'e.name as employee_name',
                        'dept.name as department',
                        'desig.name as designation',
                        'esd.month', 'esd.year',
                        'esd.basic_paid', 'esd.total_allowances', 'esd.gross_salary',
                        'esd.deductions', 'esd.net_salary',
                        'esd.payment_date', 'esd.payment_mode', 'esd.status',
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 5. All Employees (Unified Employee Registry) ─────────────────────────

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
            public function title(): string { return 'All Employees'; }
            public function headings(): array
            {
                return [
                    'Employee Code', 'Name', 'Father Name', 'DOB', 'Gender',
                    'Phone', 'Email', 'Department', 'Designation',
                    'Employment Type', 'Salary Type', 'Basic Salary (₹)',
                    'Joining Date', 'City', 'State', 'Status',
                ];
            }
            public function collection()
            {
                return DB::table('employees as e')
                    ->leftJoin('employee_departments as dept', 'dept.id', '=', 'e.employee_department_id')
                    ->leftJoin('employee_designations as desig', 'desig.id', '=', 'e.employee_designation_id')
                    ->where('e.institute_id', $this->id)
                    ->orderBy('dept.name')
                    ->orderBy('e.name')
                    ->select([
                        'e.employee_code', 'e.name', 'e.father_name', 'e.dob', 'e.gender',
                        'e.phone', 'e.email',
                        'dept.name as department',
                        'desig.name as designation',
                        'e.employment_type', 'e.salary_type', 'e.basic_salary',
                        'e.joining_date', 'e.city', 'e.state', 'e.status',
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 6. Staff Members (Admin Portal Login Staff) ───────────────────────────

    private function staffSheet(): object
    {
        $id = $this->id;
        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}
            public function title(): string { return 'Staff Members'; }
            public function headings(): array
            {
                return [
                    'Name', 'Email', 'Mobile', 'Role',
                    'Address', 'Joining Date', 'Status',
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
                        'sm.name', 'sm.email', 'sm.mobile',
                        'sr.name as role',
                        'sm.address', 'sm.joining_date',
                        DB::raw("IF(sm.status = 1, 'Active', 'Inactive') as status"),
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 6. Library Staff ─────────────────────────────────────────────────────

    private function libraryStaffSheet(): object
    {
        $id = $this->id;
        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}
            public function title(): string { return 'Library Staff'; }
            public function headings(): array
            {
                return [
                    'Employee ID', 'Name', 'Email', 'Phone', 'Gender',
                    'Designation', 'Shift', 'Section', 'Qualification',
                    'Joining Date', 'DOB', 'Address', 'Status',
                ];
            }
            public function collection()
            {
                return DB::table('library_staff')
                    ->where('institute_id', $this->id)
                    ->orderBy('name')
                    ->select([
                        'employee_id', 'name', 'email', 'phone', 'gender',
                        'designation', 'shift', 'assigned_section', 'qualification',
                        'joining_date', 'date_of_birth', 'address',
                        DB::raw("IF(status = 1, 'Active', 'Inactive') as status"),
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 7. Transport Drivers ─────────────────────────────────────────────────

    private function transportDriversSheet(): object
    {
        $id = $this->id;
        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}
            public function title(): string { return 'Transport Drivers'; }
            public function headings(): array
            {
                return [
                    'Driver Name', 'Mobile', 'License No', 'License Expiry',
                    'Helper Name', 'Helper Mobile',
                    'Notes', 'Status',
                ];
            }
            public function collection()
            {
                return DB::table('transport_drivers')
                    ->where('institute_id', $this->id)
                    ->orderBy('name')
                    ->select([
                        'name', 'mobile', 'license_no', 'license_expiry',
                        'helper_name', 'helper_mobile',
                        'notes',
                        DB::raw("IF(status = 1, 'Active', 'Inactive') as status"),
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 8. Transport Vehicles ────────────────────────────────────────────────

    private function transportVehiclesSheet(): object
    {
        $id = $this->id;
        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}
            public function title(): string { return 'Transport Vehicles'; }
            public function headings(): array
            {
                return [
                    'Vehicle No', 'Registration No', 'Model', 'Capacity',
                    'Fuel Type', 'Insurance Expiry', 'Permit Expiry',
                    'Fitness Expiry', 'Pollution Expiry', 'Service Due Date',
                    'Notes', 'Status',
                ];
            }
            public function collection()
            {
                return DB::table('transport_vehicles')
                    ->where('institute_id', $this->id)
                    ->orderBy('vehicle_no')
                    ->select([
                        'vehicle_no', 'registration_no', 'model', 'capacity',
                        'fuel_type', 'insurance_expiry', 'permit_expiry',
                        'fitness_expiry', 'pollution_expiry', 'service_due_date',
                        'notes',
                        DB::raw("IF(status = 1, 'Active', 'Inactive') as status"),
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 9. Transport Routes ──────────────────────────────────────────────────

    private function transportRoutesSheet(): object
    {
        $id = $this->id;
        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}
            public function title(): string { return 'Transport Routes'; }
            public function headings(): array
            {
                return [
                    'Route Code', 'Route Name', 'Start Point', 'End Point',
                    'Distance (KM)', 'Route Fee (₹)', 'Morning Time', 'Evening Time',
                    'Total Stops', 'Notes', 'Status',
                ];
            }
            public function collection()
            {
                return DB::table('transport_routes as tr')
                    ->leftJoin(
                        DB::raw('(SELECT transport_route_id, COUNT(*) as stop_count FROM transport_route_stops GROUP BY transport_route_id) as sc'),
                        'sc.transport_route_id', '=', 'tr.id'
                    )
                    ->where('tr.institute_id', $this->id)
                    ->orderBy('tr.route_code')
                    ->select([
                        'tr.route_code', 'tr.name',
                        'tr.start_point', 'tr.end_point',
                        'tr.distance_km',
                        'tr.fee_amount',
                        'tr.morning_time', 'tr.evening_time',
                        DB::raw('COALESCE(sc.stop_count, 0) as total_stops'),
                        'tr.notes',
                        DB::raw("IF(tr.status = 1, 'Active', 'Inactive') as status"),
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 10. Route Stops ──────────────────────────────────────────────────────

    private function routeStopsSheet(): object
    {
        $id = $this->id;
        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}
            public function title(): string { return 'Route Stops'; }
            public function headings(): array
            {
                return [
                    'Route Code', 'Route Name', 'Seq No', 'Stop Name',
                    'Landmark', 'Pickup Time', 'Drop Time', 'Stop Active',
                ];
            }
            public function collection()
            {
                return DB::table('transport_route_stops as trs')
                    ->join('transport_routes as tr', 'tr.id', '=', 'trs.transport_route_id')
                    ->where('tr.institute_id', $this->id)
                    ->orderBy('tr.route_code')
                    ->orderBy('trs.sequence')
                    ->select([
                        'tr.route_code', 'tr.name as route_name',
                        'trs.sequence', 'trs.stop_name',
                        'trs.landmark', 'trs.pickup_time', 'trs.drop_time',
                        DB::raw("IF(trs.status = 1, 'Active', 'Inactive') as stop_active"),
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 11. Centers ──────────────────────────────────────────────────────────

    private function centersSheet(): object
    {
        $id = $this->id;
        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}
            public function title(): string { return 'Centers'; }
            public function headings(): array
            {
                return [
                    'Center Name', 'Code', 'Mobile', 'Email',
                    'City', 'State', 'Address',
                    'Can Collect Fee', 'Can Add Admission', 'Can View Students',
                    'Total Students', 'Status',
                ];
            }
            public function collection()
            {
                return DB::table('centers as c')
                    ->leftJoin(
                        DB::raw('(SELECT admission_source_id, COUNT(*) as cnt FROM students WHERE admission_source = "center" GROUP BY admission_source_id) as sc'),
                        'sc.admission_source_id', '=', 'c.id'
                    )
                    ->where('c.institute_id', $this->id)
                    ->orderBy('c.name')
                    ->select([
                        'c.name', 'c.code', 'c.mobile', 'c.email',
                        'c.city', 'c.state', 'c.address',
                        DB::raw("IF(c.can_collect_fee = 1, 'Yes', 'No') as can_collect_fee"),
                        DB::raw("IF(c.can_add_admission = 1, 'Yes', 'No') as can_add_admission"),
                        DB::raw("IF(c.can_view_students = 1, 'Yes', 'No') as can_view_students"),
                        DB::raw('COALESCE(sc.cnt, 0) as total_students'),
                        DB::raw("IF(c.status = 1, 'Active', 'Inactive') as status"),
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 12. Channel Partners ─────────────────────────────────────────────────

    private function channelPartnersSheet(): object
    {
        $id = $this->id;
        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}
            public function title(): string { return 'Channel Partners'; }
            public function headings(): array
            {
                return [
                    'Partner Name', 'Mobile', 'Email',
                    'City', 'State', 'Address',
                    'Commission %',
                    'Can Collect Fee', 'Can Add Admission', 'Can View Students',
                    'Total Students Referred', 'Status',
                ];
            }
            public function collection()
            {
                return DB::table('channel_partners as cp')
                    ->leftJoin(
                        DB::raw('(SELECT admission_source_id, COUNT(*) as cnt FROM students WHERE admission_source = "channel_partner" GROUP BY admission_source_id) as sp'),
                        'sp.admission_source_id', '=', 'cp.id'
                    )
                    ->where('cp.institute_id', $this->id)
                    ->orderBy('cp.name')
                    ->select([
                        'cp.name', 'cp.mobile', 'cp.email',
                        'cp.city', 'cp.state', 'cp.address',
                        'cp.commission_percent',
                        DB::raw("IF(cp.can_collect_fee = 1, 'Yes', 'No') as can_collect_fee"),
                        DB::raw("IF(cp.can_add_admission = 1, 'Yes', 'No') as can_add_admission"),
                        DB::raw("IF(cp.can_view_students = 1, 'Yes', 'No') as can_view_students"),
                        DB::raw('COALESCE(sp.cnt, 0) as total_students'),
                        DB::raw("IF(cp.status = 1, 'Active', 'Inactive') as status"),
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 13. Library Books ────────────────────────────────────────────────────

    private function libraryBooksSheet(): object
    {
        $id = $this->id;
        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}
            public function title(): string { return 'Library Books'; }
            public function headings(): array
            {
                return [
                    'Title', 'Subtitle', 'ISBN', 'Edition', 'Language',
                    'Subject', 'Authors', 'Category', 'Publisher',
                    'Total Copies', 'Available Copies', 'Active',
                ];
            }
            public function collection()
            {
                return DB::table('library_books as lb')
                    ->leftJoin('library_categories as lc', 'lc.id', '=', 'lb.category_id')
                    ->leftJoin('library_publishers as lp', 'lp.id', '=', 'lb.publisher_id')
                    ->leftJoin(
                        DB::raw('(SELECT lbc.book_id, COUNT(*) as total, SUM(IF(lbc.status = "available", 1, 0)) as available FROM library_book_copies lbc GROUP BY lbc.book_id) as copies'),
                        'copies.book_id', '=', 'lb.id'
                    )
                    ->where('lb.institute_id', $this->id)
                    ->orderBy('lb.title')
                    ->select([
                        'lb.title', 'lb.subtitle', 'lb.isbn', 'lb.edition', 'lb.language',
                        'lb.subject_name', 'lb.author_text',
                        'lc.name as category',
                        'lp.name as publisher',
                        DB::raw('COALESCE(copies.total, 0) as total_copies'),
                        DB::raw('COALESCE(copies.available, 0) as available_copies'),
                        DB::raw("IF(lb.is_active = 1, 'Yes', 'No') as active"),
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }
}
