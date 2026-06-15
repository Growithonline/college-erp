<?php

namespace App\Http\Controllers\Institute\Reports;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateFeeLedgerPdf;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Institute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FeeLedgerReportController extends Controller
{
    private function instituteId(): int
    {
        foreach (['web', 'staff'] as $guard) {
            $user = auth()->guard($guard)->user();
            if ($user?->institute_id) {
                return (int) $user->institute_id;
            }
        }
        abort(403, 'Not authenticated.');
    }

    // ── Core Query Builder ───────────────────────────────────────────────
    private function buildQuery(int $instituteId, array $filters): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('students as s')
            ->join('course_streams as cs', 'cs.id', '=', 's.course_stream_id')
            ->join('courses as c', 'c.id', '=', 'cs.course_id')
            ->leftJoin('course_parts as cp', 'cp.id', '=', 's.course_part_id')
            ->leftJoin('academic_sessions as sess', 'sess.id', '=', 's.academic_session_id')
            ->leftJoin('student_wallets as sw', function ($j) {
                $j->on('sw.student_id', '=', 's.id')
                  ->on('sw.academic_session_id', '=', 's.academic_session_id');
            })
            ->leftJoin(DB::raw('(
                SELECT fi.student_id, fi.academic_session_id,
                       SUM(fi.paid_amount)  AS total_paid,
                       SUM(fi.discount)     AS total_discount,
                       SUM(fi.total_amount) AS total_invoiced
                FROM fee_invoices fi
                WHERE fi.is_cancelled = 0
                GROUP BY fi.student_id, fi.academic_session_id
            ) AS inv'), function ($j) {
                $j->on('inv.student_id', '=', 's.id')
                  ->on('inv.academic_session_id', '=', 's.academic_session_id');
            })
            ->leftJoin(DB::raw('(
                SELECT fi2.student_id, fi2.academic_session_id,
                       SUM(fii.fine) AS total_fine
                FROM fee_invoices fi2
                JOIN fee_invoice_items fii ON fii.fee_invoice_id = fi2.id
                WHERE fi2.is_cancelled = 0
                GROUP BY fi2.student_id, fi2.academic_session_id
            ) AS fines'), function ($j) {
                $j->on('fines.student_id', '=', 's.id')
                  ->on('fines.academic_session_id', '=', 's.academic_session_id');
            })
            ->leftJoin(DB::raw("(
                SELECT lm.student_id,
                       COALESCE(SUM(GREATEST(lt.fine_amount - lt.fine_paid, 0)), 0) AS library_fine_due
                FROM library_transactions lt
                JOIN library_members lm ON lm.id = lt.library_member_id
                WHERE lt.fine_amount > lt.fine_paid
                  AND lm.institute_id = {$instituteId}
                GROUP BY lm.student_id
            ) AS lib_fine"), 'lib_fine.student_id', '=', 's.id')
            ->where('s.institute_id', $instituteId)
            ->select([
                's.id',
                's.name',
                's.student_uid',
                's.roll_no',
                's.mobile',
                's.father_name',
                's.mother_name',
                's.current_semester',
                'cs.name as stream_name',
                'c.id as course_id',
                'c.name as course_name',
                'cp.year_number',
                'sess.name as session_name',
                DB::raw('COALESCE(sw.main_b, 0) as wallet_balance'),
                DB::raw('COALESCE(inv.total_paid, 0) as total_paid'),
                DB::raw('COALESCE(inv.total_discount, 0) as total_discount'),
                DB::raw('COALESCE(inv.total_invoiced, 0) as total_invoiced'),
                DB::raw('COALESCE(fines.total_fine, 0) as total_fine'),
                DB::raw('COALESCE(lib_fine.library_fine_due, 0) as library_fine_due'),
            ]);

        if (!empty($filters['course_ids'])) {
            $query->whereIn('c.id', $filters['course_ids']);
        }
        if (!empty($filters['session_id'])) {
            $query->where('s.academic_session_id', $filters['session_id']);
        }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($s) {
                $q->where('s.name', 'like', $s)
                  ->orWhere('s.student_uid', 'like', $s)
                  ->orWhere('s.mobile', 'like', $s);
            });
        }
        if (!empty($filters['due_only'])) {
            $query->where('sw.main_b', '<', 0);
        }

        return $query->orderBy('c.name')->orderBy('cs.name')->orderBy('s.name');
    }

    private function getSummary(\Illuminate\Database\Query\Builder $query): object
    {
        $sub = DB::table(DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query)
            ->selectRaw('
                COUNT(*) as total_students,
                SUM(total_paid) as total_paid,
                SUM(total_discount) as total_discount,
                SUM(total_fine) as total_fine,
                SUM(library_fine_due) as total_library_fine,
                SUM(CASE WHEN wallet_balance < 0 THEN ABS(wallet_balance) ELSE 0 END) as total_due,
                COUNT(CASE WHEN wallet_balance < 0 OR library_fine_due > 0 THEN 1 ELSE NULL END) as due_count
            ')
            ->first();

        return $sub ?? (object) [
            'total_students' => 0, 'total_paid' => 0, 'total_discount' => 0,
            'total_fine' => 0, 'total_library_fine' => 0, 'total_due' => 0, 'due_count' => 0,
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    //  DASHBOARD — Paginated
    // ════════════════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $instituteId = $this->instituteId();

        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courses  = Course::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();

        $filters = [
            'course_ids' => array_filter((array) $request->course_ids),
            'session_id' => $request->session_id,
            'search'     => $request->search,
            'due_only'   => $request->boolean('due_only'),
        ];

        $baseQuery = $this->buildQuery($instituteId, $filters);
        $summary   = $this->getSummary(clone $baseQuery);
        $students  = $baseQuery->paginate(50)->withQueryString();

        return view('institute.reports.fee-ledger.index', compact(
            'sessions', 'courses', 'filters', 'students', 'summary'
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    //  PRINT ALL — Browser print (no pagination)
    // ════════════════════════════════════════════════════════════════════
    public function printAll(Request $request)
    {
        $instituteId = $this->instituteId();

        $filters = [
            'course_ids' => array_filter((array) $request->course_ids),
            'session_id' => $request->session_id,
            'search'     => $request->search,
            'due_only'   => $request->boolean('due_only'),
        ];

        $institute = Institute::find($instituteId);
        $rows      = $this->buildQuery($instituteId, $filters)->get();
        $grouped   = $rows->groupBy('course_name');

        $summary = (object) [
            'total_students'     => $rows->count(),
            'total_paid'         => $rows->sum('total_paid'),
            'total_discount'     => $rows->sum('total_discount'),
            'total_fine'         => $rows->sum('total_fine'),
            'total_library_fine' => $rows->sum('library_fine_due'),
            'total_due'          => $rows->sum(fn($r) => $r->wallet_balance < 0 ? abs($r->wallet_balance) : 0),
            'due_count'          => $rows->filter(fn($r) => $r->wallet_balance < 0 || $r->library_fine_due > 0)->count(),
        ];

        return view('institute.reports.fee-ledger.print', compact(
            'grouped', 'summary', 'institute', 'filters'
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    //  CSV EXPORT — Streaming, zero memory
    // ════════════════════════════════════════════════════════════════════
    public function exportCsv(Request $request)
    {
        $instituteId = $this->instituteId();
        $institute   = Institute::find($instituteId);

        $filters = [
            'course_ids' => array_filter((array) $request->course_ids),
            'session_id' => $request->session_id,
            'search'     => $request->search,
            'due_only'   => $request->boolean('due_only'),
        ];

        $filename = 'fee_ledger_' . now()->format('Ymd_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'X-Accel-Buffering'   => 'no',
        ];

        return response()->stream(function () use ($instituteId, $institute, $filters) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($out, [$institute?->name ?? 'Institute']);
            fputcsv($out, ['Fee Ledger Report — Course Wise']);
            fputcsv($out, ['Generated: ' . now()->format('d M Y, h:i A')]);
            fputcsv($out, []);
            fputcsv($out, [
                'Sr No', 'Student Name', 'Student ID', 'Mobile',
                'Course', 'Stream', 'Year / Semester', 'Session',
                'Total Invoiced (Rs)', 'Total Paid (Rs)', 'Discount (Rs)',
                'Fine (Rs)', 'Library Fine (Rs)', 'Balance Due (Rs)', 'Status',
            ]);

            $sr          = 0;
            $currentCourse = null;

            $this->buildQuery($instituteId, $filters)
                ->chunk(500, function ($chunk) use ($out, &$sr, &$currentCourse) {
                    foreach ($chunk as $row) {
                        if ($row->course_name !== $currentCourse) {
                            $currentCourse = $row->course_name;
                            fputcsv($out, []);
                            fputcsv($out, ['--- ' . strtoupper($currentCourse) . ' ---']);
                        }
                        $sr++;
                        $libFine = (float) ($row->library_fine_due ?? 0);
                        $due     = $row->wallet_balance < 0 ? abs($row->wallet_balance) : 0;
                        $status  = ($due > 0 || $libFine > 0) ? 'Due' : ($row->total_paid > 0 ? 'Paid' : 'No Payment');
                        $yearSem = $row->year_number
                            ? 'Year ' . $row->year_number
                            : ($row->current_semester ? 'Sem ' . $row->current_semester : '-');
                        fputcsv($out, [
                            $sr,
                            $row->name,
                            $row->student_uid,
                            $row->mobile ?? '',
                            $row->course_name,
                            $row->stream_name,
                            $yearSem,
                            $row->session_name ?? '',
                            number_format($row->total_invoiced, 2, '.', ''),
                            number_format($row->total_paid, 2, '.', ''),
                            number_format($row->total_discount, 2, '.', ''),
                            number_format($row->total_fine, 2, '.', ''),
                            number_format($libFine, 2, '.', ''),
                            number_format($due, 2, '.', ''),
                            $status,
                        ]);
                    }
                    ob_flush();
                    flush();
                });

            fclose($out);
        }, 200, $headers);
    }

    // ════════════════════════════════════════════════════════════════════
    //  EXCEL EXPORT — Maatwebsite, multi-sheet per course
    // ════════════════════════════════════════════════════════════════════
    public function exportExcel(Request $request)
    {
        $instituteId = $this->instituteId();

        $filters = [
            'course_ids' => array_filter((array) $request->course_ids),
            'session_id' => $request->session_id,
            'search'     => $request->search,
            'due_only'   => $request->boolean('due_only'),
        ];

        $institute = Institute::find($instituteId);
        $filename  = 'fee_ledger_' . now()->format('Ymd_His') . '.xlsx';

        $export = new \App\Exports\FeeLedgerExcelExport($instituteId, $filters, $institute);

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    // ════════════════════════════════════════════════════════════════════
    //  PDF — Synchronous (≤1000) or Queue Job (>1000)
    // ════════════════════════════════════════════════════════════════════
    public function queuePdf(Request $request)
    {
        $instituteId = $this->instituteId();
        $institute   = Institute::find($instituteId);

        $filters = [
            'course_ids' => array_filter((array) $request->course_ids),
            'session_id' => $request->session_id,
            'search'     => $request->search,
            'due_only'   => $request->boolean('due_only'),
        ];

        $jobId        = Str::uuid()->toString();
        $instituteName = $institute?->name ?? 'Institute';

        $count = $this->buildQuery($instituteId, $filters)->count();

        if ($count <= 1000) {
            // Generate immediately — fast enough for small datasets
            $this->generatePdfNow($jobId, $instituteId, $filters, $instituteName);
            return response()->json(['job_id' => $jobId, 'status' => 'ready']);
        }

        // Large dataset — background queue
        GenerateFeeLedgerPdf::dispatch($jobId, $instituteId, $filters, $instituteName);
        return response()->json([
            'job_id'  => $jobId,
            'status'  => 'pending',
            'message' => 'PDF generation started (' . number_format($count) . ' students). Thodi der mein ready ho jaayega.',
        ]);
    }

    private function generatePdfNow(string $jobId, int $instituteId, array $filters, string $instituteName): void
    {
        ini_set('memory_limit', '256M');
        Storage::makeDirectory('exports/fee-ledger');

        $rows    = $this->buildQuery($instituteId, $filters)->get();
        $grouped = $rows->groupBy('course_name');
        $summary = (object) [
            'total_students'     => $rows->count(),
            'total_paid'         => $rows->sum('total_paid'),
            'total_discount'     => $rows->sum('total_discount'),
            'total_fine'         => $rows->sum('total_fine'),
            'total_library_fine' => $rows->sum('library_fine_due'),
            'total_due'          => $rows->sum(fn($r) => $r->wallet_balance < 0 ? abs($r->wallet_balance) : 0),
            'due_count'          => $rows->filter(fn($r) => $r->wallet_balance < 0 || $r->library_fine_due > 0)->count(),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('institute.reports.fee-ledger.pdf-template', [
            'grouped'       => $grouped,
            'summary'       => $summary,
            'instituteName' => $instituteName,
            'generatedAt'   => now()->format('d M Y, h:i A'),
            'filters'       => $filters,
        ]);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'dpi' => 96]);

        Storage::put("exports/fee-ledger/{$jobId}.pdf", $pdf->output());
    }

    public function pdfStatus(Request $request)
    {
        $jobId = $request->job_id;

        if (Storage::exists("exports/fee-ledger/{$jobId}.pdf")) {
            return response()->json(['status' => 'ready']);
        }
        if (Storage::exists("exports/fee-ledger/{$jobId}.failed")) {
            return response()->json([
                'status'  => 'failed',
                'message' => Storage::get("exports/fee-ledger/{$jobId}.failed"),
            ]);
        }
        return response()->json(['status' => 'pending']);
    }

    public function downloadPdf(Request $request)
    {
        $jobId = $request->job_id;
        $path  = "exports/fee-ledger/{$jobId}.pdf";

        abort_unless(Storage::exists($path), 404, 'PDF not ready yet.');

        return Storage::download($path, 'fee_ledger_' . now()->format('Ymd') . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    // ── Used by the PDF Job ──────────────────────────────────────────────
    public function getDataForPdf(int $instituteId, array $filters): array
    {
        $rows      = $this->buildQuery($instituteId, $filters)->get();
        $grouped   = $rows->groupBy('course_name');
        $summary   = (object) [
            'total_students'     => $rows->count(),
            'total_paid'         => $rows->sum('total_paid'),
            'total_discount'     => $rows->sum('total_discount'),
            'total_fine'         => $rows->sum('total_fine'),
            'total_library_fine' => $rows->sum('library_fine_due'),
            'total_due'          => $rows->sum(fn($r) => $r->wallet_balance < 0 ? abs($r->wallet_balance) : 0),
            'due_count'          => $rows->filter(fn($r) => $r->wallet_balance < 0 || $r->library_fine_due > 0)->count(),
        ];
        return ['grouped' => $grouped, 'summary' => $summary];
    }
}
