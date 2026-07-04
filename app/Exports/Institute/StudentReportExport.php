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

    // ── 1. Students ──────────────────────────────────────────────────────────

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
                    'Sr No', 'Session', 'Student UID', 'Enrollment No',
                    'Name', 'Father Name', 'Mother Name',
                    'Mobile', 'Email', 'DOB', 'Gender', 'Category',
                    'Course', 'Stream', 'Semester/Part',
                    'Admission Date', 'Admission Type', 'Student Type', 'Status',
                ];
            }
            public function collection()
            {
                $rows = DB::table('students as s')
                    ->leftJoin('course_streams as cs', 'cs.id', '=', 's.course_stream_id')
                    ->leftJoin('courses as c', 'c.id', '=', 'cs.course_id')
                    ->leftJoin('academic_sessions as ses', 'ses.id', '=', 's.academic_session_id')
                    ->leftJoin('course_parts as cp', 'cp.id', '=', 's.course_part_id')
                    ->where('s.institute_id', $this->id)
                    ->orderByDesc('ses.start_date')
                    ->orderBy('s.name')
                    ->select([
                        's.name',           // for row numbering grouping only
                        'ses.name as session_name',
                        'ses.start_date',   // for ordering only
                        's.student_uid', 's.enrollment_no',
                        's.name as student_name', 's.father_name', 's.mother_name',
                        's.mobile', 's.email', 's.dob', 's.gender', 's.category',
                        'c.name as course', 'cs.name as stream',
                        'cp.part_name as semester_part',
                        's.admission_date', 's.admission_type',
                        's.student_type', 's.status',
                    ])
                    ->get();

                return $rows->values()->map(function ($r, $index) {
                    return [
                        $index + 1,
                        $r->session_name,
                        $r->student_uid,
                        $r->enrollment_no,
                        $r->student_name,
                        $r->father_name,
                        $r->mother_name,
                        $r->mobile,
                        $r->email,
                        $r->dob,
                        $r->gender,
                        $r->category,
                        $r->course,
                        $r->stream,
                        $r->semester_part,
                        $r->admission_date,
                        $r->admission_type,
                        $r->student_type,
                        $r->status,
                    ];
                });
            }
        };
    }

    // ── 2. Fee History ───────────────────────────────────────────────────────

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
                    'Session', 'Semester', 'Invoice No', 'Payment Date',
                    'Student Name', 'Student UID', 'Course', 'Stream',
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
                    ->orderByDesc('ses.start_date')
                    ->orderBy('s.name')
                    ->orderByDesc('fi.payment_date')
                    ->select([
                        'ses.name as session',
                        DB::raw('COALESCE(fi.semester, "—") as semester'),
                        'fi.invoice_no',
                        'fi.payment_date',
                        's.name as student_name',
                        's.student_uid',
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

    // ── 3. Library ───────────────────────────────────────────────────────────

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
                    'Session', 'Student Name', 'Student UID',
                    'Book Title', 'ISBN',
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
                    ->leftJoin('academic_sessions as ses', 'ses.id', '=', 'lt.academic_session_id')
                    ->where('lt.institute_id', $this->id)
                    ->where('lm.member_type', 'student')
                    ->orderByDesc('ses.start_date')
                    ->orderBy('s.name')
                    ->orderByDesc('lt.issued_on')
                    ->select([
                        'ses.name as session',
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

    // ── 4. Transport ─────────────────────────────────────────────────────────

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
                    'Session', 'Student Name', 'Student UID',
                    'Route', 'Stop', 'Vehicle No',
                    'Fee Amount (₹)', 'Charged (₹)', 'Paid (₹)',
                    'Start Date', 'Status',
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
                    ->orderByDesc('ses.start_date')
                    ->orderBy('s.name')
                    ->select([
                        'ses.name as session',
                        's.name as student_name',
                        's.student_uid',
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
