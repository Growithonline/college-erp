<?php

namespace App\Exports\Institute;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentReportExport implements WithMultipleSheets
{
    public function __construct(private int $id) {}

    public function sheets(): array
    {
        return [
            $this->studentsSheet(),
            $this->feeHistorySheet(),
            $this->librarySheet(),
            $this->transportSheet(),
        ];
    }

    private function studentsSheet(): object
    {
        $id = $this->id;

        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}

            public function title(): string { return 'Students'; }

            public function headings(): array
            {
                return [
                    'Sr No', 'Student UID', 'Enrollment No', 'Name', 'Father Name',
                    'Mother Name', 'Mobile', 'Email', 'DOB', 'Gender', 'Category',
                    'Course', 'Stream', 'Semester/Part', 'Admission Session',
                    'Admission Date', 'Admission Type', 'Student Type', 'Status',
                ];
            }

            public function collection()
            {
                return DB::table('students as s')
                    ->leftJoin('course_streams as cs', 'cs.id', '=', 's.course_stream_id')
                    ->leftJoin('courses as c', 'c.id', '=', 'cs.course_id')
                    ->leftJoin('academic_sessions as ses', 'ses.id', '=', 's.academic_session_id')
                    ->leftJoin('course_parts as cp', 'cp.id', '=', 's.course_part_id')
                    ->where('s.institute_id', $this->id)
                    ->orderBy('s.name')
                    ->select([
                        DB::raw('ROW_NUMBER() OVER (ORDER BY s.name) as sr_no'),
                        's.student_uid', 's.enrollment_no', 's.name',
                        's.father_name', 's.mother_name',
                        's.mobile', 's.email', 's.dob', 's.gender', 's.category',
                        'c.name as course', 'cs.name as stream',
                        'cp.name as semester_part',
                        'ses.name as session',
                        's.admission_date', 's.admission_type',
                        's.student_type', 's.status',
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    private function feeHistorySheet(): object
    {
        $id = $this->id;

        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}

            public function title(): string { return 'Fee History'; }

            public function headings(): array
            {
                return [
                    'Invoice No', 'Payment Date', 'Semester', 'Student Name', 'Student UID',
                    'Course', 'Stream', 'Session',
                    'Total Amount (₹)', 'Discount (₹)', 'Paid Amount (₹)', 'Remaining Due (₹)',
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
                    ->orderBy('s.name')
                    ->orderByDesc('fi.payment_date')
                    ->select([
                        'fi.invoice_no',
                        'fi.payment_date',
                        DB::raw('COALESCE(fi.semester, "—") as semester'),
                        's.name as student_name',
                        's.student_uid',
                        'c.name as course',
                        'cs.name as stream',
                        'ses.name as session',
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

    private function librarySheet(): object
    {
        $id = $this->id;

        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}

            public function title(): string { return 'Library'; }

            public function headings(): array
            {
                return [
                    'Student Name', 'Student UID', 'Book Title', 'ISBN',
                    'Issue Date', 'Due Date', 'Return Date',
                    'Fine Amount (₹)', 'Fine Paid (₹)', 'Status', 'Issued By',
                ];
            }

            public function collection()
            {
                return DB::table('library_transactions as lt')
                    ->join('library_members as lm', 'lm.id', '=', 'lt.library_member_id')
                    ->join('students as s', 's.id', '=', 'lm.student_id')
                    ->join('library_book_copies as lbc', 'lbc.id', '=', 'lt.library_book_copy_id')
                    ->join('library_books as lb', 'lb.id', '=', 'lbc.book_id')
                    ->where('lt.institute_id', $this->id)
                    ->where('lm.member_type', 'student')
                    ->orderBy('s.name')
                    ->orderByDesc('lt.issued_on')
                    ->select([
                        's.name as student_name',
                        's.student_uid',
                        'lb.title as book_title',
                        'lb.isbn',
                        'lt.issued_on',
                        'lt.due_on',
                        'lt.returned_on',
                        'lt.fine_amount',
                        'lt.fine_paid',
                        'lt.current_status',
                        'lt.issued_by',
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    private function transportSheet(): object
    {
        $id = $this->id;

        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}

            public function title(): string { return 'Transport'; }

            public function headings(): array
            {
                return [
                    'Student Name', 'Student UID', 'Session', 'Route',
                    'Stop', 'Vehicle No', 'Fee Amount (₹)', 'Charged (₹)',
                    'Paid (₹)', 'Start Date', 'Status',
                ];
            }

            public function collection()
            {
                return DB::table('transport_allocations as ta')
                    ->join('students as s', 's.id', '=', 'ta.student_id')
                    ->join('transport_routes as tr', 'tr.id', '=', 'ta.transport_route_id')
                    ->leftJoin('transport_route_stops as trs', 'trs.id', '=', 'ta.transport_route_stop_id')
                    ->leftJoin('transport_vehicles as tv', 'tv.id', '=', 'ta.transport_vehicle_id')
                    ->leftJoin('academic_sessions as ses', 'ses.id', '=', 'ta.academic_session_id')
                    ->where('ta.institute_id', $this->id)
                    ->orderBy('s.name')
                    ->select([
                        's.name as student_name',
                        's.student_uid',
                        'ses.name as session',
                        'tr.name as route',
                        'trs.stop_name as stop',
                        'tv.vehicle_no',
                        'ta.fee_amount',
                        'ta.charged_amount',
                        'ta.paid_amount',
                        'ta.start_date',
                        'ta.status',
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }
}
