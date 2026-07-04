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
            $this->educationDetailsSheet(),
            $this->feeHistorySheet(),
            $this->librarySheet(),
            $this->transportSheet(),
        ];
    }

    // ── 1. Students (All Fields) ─────────────────────────────────────────────

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
                    // IDs & Office
                    'Sr No', 'Session', 'Student UID', 'Institute Form No',
                    'Enrollment No', 'Roll No', 'SR No',
                    'Exam Form No', 'UIN No', 'Reference No',

                    // Course Details
                    'Course', 'Stream', 'Semester/Part',
                    'Admission Date', 'Submitted Date',
                    'Admission Type', 'Admission Source', 'Referred By',
                    'Quick Admission', 'Status', 'Status Reason',

                    // Personal Details
                    'Name', 'Email', 'Mobile',
                    'DOB', 'Gender', 'Nationality', 'Religion',
                    'Category', 'Special Category', 'Student Type',
                    'Aadhar No', 'APAAR No', 'Marital Status',
                    'Gap Year', 'Is Previous Student',
                    'Previous Roll No', 'Previous Percentage',

                    // Parents & Guardian
                    'Father Name', 'Father Mobile', 'Father Occupation',
                    'Mother Name', 'Mother Mobile', 'Mother Occupation',
                    'Guardian Name', 'Guardian Mobile', 'Guardian Relation',

                    // Permanent Address
                    'Perm State', 'Perm District', 'Perm Post',
                    'Perm Thana', 'Perm Village', 'Perm City',
                    'Perm Pincode', 'Perm Address',

                    // Communication Address
                    'Comm Same as Perm',
                    'Comm State', 'Comm District', 'Comm Post',
                    'Comm Thana', 'Comm City', 'Comm Pincode', 'Comm Address',

                    // Scholarship
                    'Has Scholarship', 'Scholarship Name', 'Scholarship Type',
                    'Scholarship Authority', 'Scholarship Applied Date',
                    'Scholarship Amount (₹)', 'Scholarship Ref No',

                    // Remarks
                    'Remarks',
                ];
            }

            public function collection()
            {
                $rows = DB::table('students as s')
                    ->leftJoin('course_streams as cs', 'cs.id', '=', 's.course_stream_id')
                    ->leftJoin('courses as c', 'c.id', '=', 'cs.course_id')
                    ->leftJoin('academic_sessions as ses', 'ses.id', '=', 's.academic_session_id')
                    ->leftJoin('course_parts as cp', 'cp.id', '=', 's.course_part_id')
                    // Referred by: center or channel partner name
                    ->leftJoin('centers as ctr', function ($j) {
                        $j->on('ctr.id', '=', 's.admission_source_id')
                          ->where('s.admission_source', '=', 'center');
                    })
                    ->leftJoin('channel_partners as chp', function ($j) {
                        $j->on('chp.id', '=', 's.admission_source_id')
                          ->where('s.admission_source', '=', 'channel_partner');
                    })
                    ->where('s.institute_id', $this->id)
                    ->orderByDesc('ses.start_date')
                    ->orderBy('s.name')
                    ->select([
                        // will add Sr No via map
                        'ses.name as session',
                        's.student_uid', 's.institute_form_no',
                        's.enrollment_no', 's.roll_no', 's.sr_no',
                        's.exam_form_no', 's.uin_no', 's.reference_no',

                        'c.name as course', 'cs.name as stream',
                        'cp.part_name as semester_part',
                        's.admission_date', 's.submitted_date',
                        's.admission_type', 's.admission_source',
                        DB::raw('COALESCE(ctr.name, chp.name) as referred_by'),
                        DB::raw("IF(s.is_quick_admission = 1, 'Yes', 'No') as is_quick_admission"),
                        's.status', 's.status_reason',

                        's.name as student_name', 's.email', 's.mobile',
                        's.dob', 's.gender', 's.nationality', 's.religion',
                        's.category', 's.special_category', 's.student_type',
                        's.aadhar_no', 's.apaar_no', 's.marital_status',
                        DB::raw("IF(s.gap_year = 1, 'Yes', 'No') as gap_year"),
                        DB::raw("IF(s.is_previous_student = 1, 'Yes', 'No') as is_previous_student"),
                        's.previous_roll_no', 's.previous_percentage',

                        's.father_name', 's.father_mobile', 's.father_occupation',
                        's.mother_name', 's.mother_mobile', 's.mother_occupation',
                        's.guardian_name', 's.guardian_mobile', 's.guardian_relation',

                        's.perm_state', 's.perm_district', 's.perm_post',
                        's.perm_thana', 's.perm_village', 's.perm_city',
                        's.perm_pincode', 's.perm_address',

                        DB::raw("IF(s.comm_same_as_perm = 1, 'Yes', 'No') as comm_same_as_perm"),
                        's.comm_state', 's.comm_district', 's.comm_post',
                        's.comm_thana', 's.comm_city', 's.comm_pincode', 's.comm_address',

                        DB::raw("IF(s.has_scholarship = 1, 'Yes', 'No') as has_scholarship"),
                        's.scholarship_name', 's.scholarship_type',
                        's.scholarship_authority', 's.scholarship_applied_date',
                        's.scholarship_amount', 's.scholarship_ref_no',

                        's.remarks',
                    ])
                    ->get();

                return $rows->values()->map(function ($r, $index) {
                    $row = [(string)($index + 1)] + array_values((array) $r);
                    // insert Sr No at position 0
                    $arr = (array) $r;
                    return array_merge([$index + 1], array_values($arr));
                });
            }
        };
    }

    // ── 2. Education Details ─────────────────────────────────────────────────

    private function educationDetailsSheet(): object
    {
        $id = $this->id;
        return new class($id) implements
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithTitle,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize
        {
            public function __construct(private int $id) {}
            public function title(): string { return 'Education Details'; }
            public function headings(): array
            {
                return [
                    'Session', 'Student Name', 'Student UID',
                    'Exam Name', 'Institute/School Name', 'Board / University',
                    'Roll Number', 'Passing Year', 'District',
                    'Division', 'Obtained Marks', 'Max Marks', 'Percentage',
                ];
            }
            public function collection()
            {
                return DB::table('student_education_details as sed')
                    ->join('students as s', 's.id', '=', 'sed.student_id')
                    ->leftJoin('academic_sessions as ses', 'ses.id', '=', 's.academic_session_id')
                    ->where('s.institute_id', $this->id)
                    ->orderByDesc('ses.start_date')
                    ->orderBy('s.name')
                    ->orderBy('sed.passing_year')
                    ->select([
                        'ses.name as session',
                        's.name as student_name',
                        's.student_uid',
                        'sed.exam_name',
                        'sed.institute_name',
                        'sed.board_university',
                        'sed.roll_number',
                        'sed.passing_year',
                        'sed.district',
                        'sed.division',
                        'sed.obtained_marks',
                        'sed.max_marks',
                        'sed.percentage',
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 3. Fee History ───────────────────────────────────────────────────────

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
                        'fi.invoice_no', 'fi.payment_date',
                        's.name as student_name', 's.student_uid',
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

    // ── 4. Library ───────────────────────────────────────────────────────────

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
                        's.name as student_name', 's.student_uid',
                        'lb.title as book_title', 'lb.isbn',
                        'lt.issued_on', 'lt.due_on', 'lt.returned_on',
                        'lt.fine_amount', 'lt.fine_paid', 'lt.current_status', 'lt.issued_by',
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }

    // ── 5. Transport ─────────────────────────────────────────────────────────

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
                        's.name as student_name', 's.student_uid',
                        'tr.name as route', 'trs.stop_name as stop',
                        'tv.vehicle_no',
                        'ta.fee_amount', 'ta.charged_amount', 'ta.paid_amount',
                        'ta.start_date', 'ta.status',
                    ])
                    ->get()
                    ->map(fn($r) => array_values((array) $r));
            }
        };
    }
}
