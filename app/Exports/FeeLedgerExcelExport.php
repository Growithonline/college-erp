<?php

namespace App\Exports;

use App\Models\Institute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FeeLedgerExcelExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private int $instituteId,
        private array $filters,
        private ?Institute $institute,
    ) {}

    public function sheets(): array
    {
        $rows    = $this->fetchRows();
        $grouped = $rows->groupBy('course_name');
        $sheets  = [];

        // Summary sheet
        $sheets[] = new FeeLedgerSummarySheet($grouped, $this->institute);

        // One sheet per course
        foreach ($grouped as $courseName => $courseRows) {
            $sheets[] = new FeeLedgerCourseSheet($courseName, $courseRows, $this->institute);
        }

        return $sheets;
    }

    private function fetchRows(): Collection
    {
        return $this->buildQuery()->get();
    }

    private function buildQuery(): \Illuminate\Database\Query\Builder
    {
        $q = DB::table('students as s')
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
                FROM fee_invoices fi WHERE fi.is_cancelled = 0
                GROUP BY fi.student_id, fi.academic_session_id
            ) AS inv'), function ($j) {
                $j->on('inv.student_id', '=', 's.id')
                  ->on('inv.academic_session_id', '=', 's.academic_session_id');
            })
            ->leftJoin(DB::raw('(
                SELECT fi2.student_id, fi2.academic_session_id, SUM(fii.fine) AS total_fine
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
                  AND lm.institute_id = {$this->instituteId}
                GROUP BY lm.student_id
            ) AS lib_fine"), 'lib_fine.student_id', '=', 's.id')
            ->where('s.institute_id', $this->instituteId)
            ->select([
                's.id', 's.name', 's.student_uid', 's.roll_no', 's.mobile',
                's.father_name', 's.mother_name', 's.current_semester',
                'cs.name as stream_name', 'c.id as course_id', 'c.name as course_name',
                'cp.year_number', 'sess.name as session_name',
                DB::raw('COALESCE(sw.main_b, 0) as wallet_balance'),
                DB::raw('COALESCE(inv.total_paid, 0) as total_paid'),
                DB::raw('COALESCE(inv.total_discount, 0) as total_discount'),
                DB::raw('COALESCE(inv.total_invoiced, 0) as total_invoiced'),
                DB::raw('COALESCE(fines.total_fine, 0) as total_fine'),
                DB::raw('COALESCE(lib_fine.library_fine_due, 0) as library_fine_due'),
            ])
            ->orderBy('c.name')
            ->orderBy('cs.name')
            ->orderBy('s.name');

        if (!empty($this->filters['course_ids'])) {
            $q->whereIn('c.id', $this->filters['course_ids']);
        }
        if (!empty($this->filters['session_id'])) {
            $q->where('s.academic_session_id', $this->filters['session_id']);
        }
        if (!empty($this->filters['search'])) {
            $s = '%' . $this->filters['search'] . '%';
            $q->where(function ($qb) use ($s) {
                $qb->where('s.name', 'like', $s)
                   ->orWhere('s.student_uid', 'like', $s)
                   ->orWhere('s.mobile', 'like', $s);
            });
        }
        if (!empty($this->filters['due_only'])) {
            $q->where('sw.main_b', '<', 0);
        }

        return $q;
    }
}
