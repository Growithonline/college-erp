<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseStream;
use App\Models\CourseType;
use App\Models\FeeInvoice;
use App\Models\FeeType;
use App\Models\Institute;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CenterReportController extends Controller
{
    private function center()
    {
        return Auth::guard('center')->user();
    }

    public function index()
    {
        $center      = $this->center();
        abort_unless($center->canDownloadReports(), 403, 'Report download not permitted for this center.');

        $instituteId = $center->institute_id;

        $sessions    = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')->orderByDesc('id')->get();
        $activeSession = $sessions->firstWhere('is_active', true);

        $courseTypes = CourseType::where('institute_id', $instituteId)
            ->orderBy('name')->get(['id', 'name']);

        $courses = Course::where('institute_id', $instituteId)
            ->where('status', true)->orderBy('name')
            ->get(['id', 'name', 'course_type_id']);

        $streams = CourseStream::whereIn('course_id', $courses->pluck('id'))
            ->where('status', true)->orderBy('name')
            ->get(['id', 'name', 'course_id']);

        $feeTypes = FeeType::where('institute_id', $instituteId)
            ->orderBy('name')->get(['id', 'name']);

        $studentStatuses = [
            'active'      => 'Active',
            'inactive'    => 'Inactive',
            'detained'    => 'Detained',
            'passed_out'  => 'Passed Out',
            'transferred' => 'Transferred',
            'cancelled'   => 'Cancelled',
        ];

        return view('center.reports.index', compact(
            'sessions', 'activeSession', 'courseTypes', 'courses', 'streams',
            'feeTypes', 'studentStatuses'
        ));
    }

    // ── Student List ───────────────────────────────────────────────────────

    public function downloadStudents(Request $request)
    {
        $center = $this->center();
        abort_unless($center->canDownloadReports(), 403, 'Report download not permitted for this center.');

        $format = $request->input('format', 'csv');

        $query = Student::where('institute_id', $center->institute_id)
            ->with(['stream.course.type', 'session', 'coursePart'])
            ->orderBy('name');

        if ($center->isStudentScopeOwn()) {
            $query->where('admission_source', 'center')
                  ->where('admission_source_id', $center->id);
        }

        $this->applyStudentFilters($query, $request);
        $students = $query->get();

        $filterSummary = $this->resolveStudentFilterLabels($request, $center->institute_id);

        if ($format === 'pdf') {
            $institute = Institute::find($center->institute_id);
            $pdf = Pdf::loadView('center.reports.pdf.students', compact(
                'students', 'center', 'institute', 'filterSummary'
            ))->setPaper('A4', 'landscape');
            return $pdf->download('students-' . now()->format('Y-m-d') . '.pdf');
        }

        $filename = 'students-' . now()->format('Y-m-d') . ($format === 'excel' ? '.xls' : '.csv');
        $mime     = $format === 'excel' ? 'application/vnd.ms-excel' : 'text/csv; charset=UTF-8';

        return response()->streamDownload(function () use ($students, $filterSummary, $format) {
            $h = fopen('php://output', 'w');
            if ($format !== 'excel') fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));

            // Meta rows
            fputcsv($h, ['Student List Report']);
            foreach ($filterSummary as $label => $value) {
                fputcsv($h, [$label . ':', $value]);
            }
            fputcsv($h, ['Generated:', now()->format('d M Y h:i A')]);
            fputcsv($h, []);

            fputcsv($h, ['#', 'Student ID', 'Name', 'Father Name', 'Mother Name',
                'Mobile', 'Email', 'Gender', 'Course Type', 'Course', 'Stream',
                'Session', 'Admission Date', 'Status']);

            foreach ($students as $i => $s) {
                fputcsv($h, [
                    $i + 1,
                    $s->student_uid ?? '',
                    $s->name,
                    $s->father_name ?? '',
                    $s->mother_name ?? '',
                    $s->mobile ?? '',
                    $s->email ?? '',
                    ucfirst($s->gender ?? ''),
                    $s->stream->course->type->name ?? '',
                    $s->stream->course->name ?? '',
                    $s->stream->name ?? '',
                    $s->session->name ?? '',
                    $s->admission_date?->format('d-m-Y') ?? '',
                    ucfirst(str_replace('_', ' ', $s->status ?? 'active')),
                ]);
            }
            fclose($h);
        }, $filename, ['Content-Type' => $mime]);
    }

    // ── Admission Report ───────────────────────────────────────────────────

    public function downloadAdmissions(Request $request)
    {
        $center = $this->center();
        abort_unless($center->canDownloadReports(), 403, 'Report download not permitted for this center.');

        $format = $request->input('format', 'csv');

        $query = Student::where('institute_id', $center->institute_id)
            ->where('admission_source', 'center')
            ->where('admission_source_id', $center->id)
            ->with(['stream.course.type', 'session', 'coursePart'])
            ->orderBy('admission_date');

        $this->applyStudentFilters($query, $request);
        $students = $query->get();

        $filterSummary = $this->resolveStudentFilterLabels($request, $center->institute_id);

        if ($format === 'pdf') {
            $institute = Institute::find($center->institute_id);
            $pdf = Pdf::loadView('center.reports.pdf.admissions', compact(
                'students', 'center', 'institute', 'filterSummary'
            ))->setPaper('A4', 'landscape');
            return $pdf->download('admissions-' . now()->format('Y-m-d') . '.pdf');
        }

        $filename = 'admissions-' . now()->format('Y-m-d') . ($format === 'excel' ? '.xls' : '.csv');
        $mime     = $format === 'excel' ? 'application/vnd.ms-excel' : 'text/csv; charset=UTF-8';

        return response()->streamDownload(function () use ($students, $filterSummary, $format) {
            $h = fopen('php://output', 'w');
            if ($format !== 'excel') fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($h, ['Admission Report']);
            foreach ($filterSummary as $label => $value) {
                fputcsv($h, [$label . ':', $value]);
            }
            fputcsv($h, ['Generated:', now()->format('d M Y h:i A')]);
            fputcsv($h, []);

            fputcsv($h, ['#', 'Student ID', 'Name', 'Father Name', 'Mobile', 'Email',
                'Gender', 'Course Type', 'Course', 'Stream', 'Session', 'Admission Date', 'Status']);

            foreach ($students as $i => $s) {
                fputcsv($h, [
                    $i + 1,
                    $s->student_uid ?? '',
                    $s->name,
                    $s->father_name ?? '',
                    $s->mobile ?? '',
                    $s->email ?? '',
                    ucfirst($s->gender ?? ''),
                    $s->stream->course->type->name ?? '',
                    $s->stream->course->name ?? '',
                    $s->stream->name ?? '',
                    $s->session->name ?? '',
                    $s->admission_date?->format('d-m-Y') ?? '',
                    ucfirst(str_replace('_', ' ', $s->status ?? 'active')),
                ]);
            }
            fclose($h);
        }, $filename, ['Content-Type' => $mime]);
    }

    // ── Fee Collection ─────────────────────────────────────────────────────

    public function downloadFeeCollection(Request $request)
    {
        $center = $this->center();
        abort_unless($center->canDownloadReports(), 403, 'Report download not permitted for this center.');

        $format = $request->input('format', 'csv');

        $query = FeeInvoice::where('institute_id', $center->institute_id)
            ->where('collected_by_center_id', $center->id)
            ->where('is_cancelled', false)
            ->with(['student.stream.course', 'session'])
            ->orderBy('payment_date');

        if ($request->filled('session_id')) {
            $query->where('academic_session_id', $request->session_id);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }
        if ($request->filled('payment_mode')) {
            $query->where('payment_mode', $request->payment_mode);
        }
        if ($request->filled('course_id')) {
            $query->whereHas('student.stream', fn($q) => $q->where('course_id', $request->course_id));
        }
        if ($request->filled('stream_id')) {
            $query->whereHas('student', fn($q) => $q->where('course_stream_id', $request->stream_id));
        }

        $invoices = $query->get();
        $filterSummary = $this->resolveFeeFilterLabels($request, $center->institute_id);

        if ($format === 'pdf') {
            $institute = Institute::find($center->institute_id);
            $pdf = Pdf::loadView('center.reports.pdf.fee-collection', compact(
                'invoices', 'center', 'institute', 'filterSummary'
            ))->setPaper('A4', 'landscape');
            return $pdf->download('fee-collection-' . now()->format('Y-m-d') . '.pdf');
        }

        $filename = 'fee-collection-' . now()->format('Y-m-d') . ($format === 'excel' ? '.xls' : '.csv');
        $mime     = $format === 'excel' ? 'application/vnd.ms-excel' : 'text/csv; charset=UTF-8';

        return response()->streamDownload(function () use ($invoices, $filterSummary, $format) {
            $h = fopen('php://output', 'w');
            if ($format !== 'excel') fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($h, ['Fee Collection Report']);
            foreach ($filterSummary as $label => $value) {
                fputcsv($h, [$label . ':', $value]);
            }
            fputcsv($h, ['Generated:', now()->format('d M Y h:i A')]);
            fputcsv($h, []);

            fputcsv($h, ['#', 'Invoice No', 'Payment Date', 'Student Name', 'Student ID',
                'Course', 'Stream', 'Session', 'Payment Mode', 'Amount Paid', 'Remarks']);

            foreach ($invoices as $i => $inv) {
                fputcsv($h, [
                    $i + 1,
                    $inv->invoice_no ?? '',
                    $inv->payment_date?->format('d-m-Y') ?? '',
                    $inv->student->name ?? '',
                    $inv->student->student_uid ?? '',
                    $inv->student->stream->course->name ?? '',
                    $inv->student->stream->name ?? '',
                    $inv->session->name ?? '',
                    ucfirst($inv->payment_mode ?? ''),
                    $inv->paid_amount ?? 0,
                    $inv->remarks ?? '',
                ]);
            }
            fclose($h);
        }, $filename, ['Content-Type' => $mime]);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function applyStudentFilters($query, Request $request): void
    {
        if ($request->filled('session_id')) {
            $query->where('academic_session_id', $request->session_id);
        }
        if ($request->filled('course_type_id')) {
            $query->whereHas('stream.course', fn($q) => $q->where('course_type_id', $request->course_type_id));
        }
        if ($request->filled('course_id')) {
            $query->whereHas('stream', fn($q) => $q->where('course_id', $request->course_id));
        }
        if ($request->filled('stream_id')) {
            $query->where('course_stream_id', $request->stream_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('admission_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('admission_date', '<=', $request->date_to);
        }
    }

    private function resolveStudentFilterLabels(Request $request, int $instituteId): array
    {
        $summary = [];
        if ($request->filled('session_id')) {
            $summary['Session'] = AcademicSession::find($request->session_id)?->name ?? $request->session_id;
        }
        if ($request->filled('course_type_id')) {
            $summary['Course Type'] = CourseType::find($request->course_type_id)?->name ?? $request->course_type_id;
        }
        if ($request->filled('course_id')) {
            $summary['Course'] = Course::find($request->course_id)?->name ?? $request->course_id;
        }
        if ($request->filled('stream_id')) {
            $summary['Stream'] = CourseStream::find($request->stream_id)?->name ?? $request->stream_id;
        }
        if ($request->filled('status')) {
            $summary['Status'] = ucfirst(str_replace('_', ' ', $request->status));
        }
        if ($request->filled('gender')) {
            $summary['Gender'] = ucfirst($request->gender);
        }
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $from = $request->date_from ? date('d M Y', strtotime($request->date_from)) : '—';
            $to   = $request->date_to   ? date('d M Y', strtotime($request->date_to))   : '—';
            $summary['Admission Date'] = "{$from} to {$to}";
        }
        return $summary ?: ['Scope' => 'All Records'];
    }

    private function resolveFeeFilterLabels(Request $request, int $instituteId): array
    {
        $summary = [];
        if ($request->filled('session_id')) {
            $summary['Session'] = AcademicSession::find($request->session_id)?->name ?? $request->session_id;
        }
        if ($request->filled('course_id')) {
            $summary['Course'] = Course::find($request->course_id)?->name ?? $request->course_id;
        }
        if ($request->filled('stream_id')) {
            $summary['Stream'] = CourseStream::find($request->stream_id)?->name ?? $request->stream_id;
        }
        if ($request->filled('payment_mode')) {
            $summary['Payment Mode'] = ucfirst($request->payment_mode);
        }
        if ($request->filled('from_date') || $request->filled('to_date')) {
            $from = $request->from_date ? date('d M Y', strtotime($request->from_date)) : '—';
            $to   = $request->to_date   ? date('d M Y', strtotime($request->to_date))   : '—';
            $summary['Payment Date'] = "{$from} to {$to}";
        }
        return $summary ?: ['Scope' => 'All Records'];
    }
}
