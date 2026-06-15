<?php

namespace App\Jobs;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GenerateFeeLedgerPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 1;

    public function __construct(
        private string $jobId,
        private int $instituteId,
        private array $filters,
        private string $instituteName,
    ) {}

    public function handle(): void
    {
        ini_set('memory_limit', '512M');

        Storage::makeDirectory('exports/fee-ledger');

        try {
            $rows    = $this->fetchRows();
            $grouped = $rows->groupBy('course_name');

            $summary = (object) [
                'total_students' => $rows->count(),
                'total_paid'     => $rows->sum('total_paid'),
                'total_discount' => $rows->sum('total_discount'),
                'total_fine'     => $rows->sum('total_fine'),
                'total_due'      => $rows->sum(fn($r) => $r->wallet_balance < 0 ? abs($r->wallet_balance) : 0),
                'due_count'      => $rows->filter(fn($r) => $r->wallet_balance < 0)->count(),
            ];

            $pdf = Pdf::loadView('institute.reports.fee-ledger.pdf-template', [
                'grouped'       => $grouped,
                'summary'       => $summary,
                'instituteName' => $this->instituteName,
                'generatedAt'   => now()->format('d M Y, h:i A'),
                'filters'       => $this->filters,
            ]);

            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'defaultFont'       => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'   => false,
                'dpi'               => 96,
            ]);

            Storage::put("exports/fee-ledger/{$this->jobId}.pdf", $pdf->output());

        } catch (\Throwable $e) {
            Storage::put(
                "exports/fee-ledger/{$this->jobId}.failed",
                $e->getMessage()
            );
        }
    }

    private function fetchRows(): \Illuminate\Support\Collection
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
            ->where('s.institute_id', $this->instituteId)
            ->select([
                's.id', 's.name', 's.student_uid', 's.roll_no', 's.mobile',
                's.father_name', 's.mother_name', 's.current_semester',
                'cs.name as stream_name', 'c.name as course_name',
                'cp.year_number', 'sess.name as session_name',
                DB::raw('COALESCE(sw.main_b, 0) as wallet_balance'),
                DB::raw('COALESCE(inv.total_paid, 0) as total_paid'),
                DB::raw('COALESCE(inv.total_discount, 0) as total_discount'),
                DB::raw('COALESCE(inv.total_invoiced, 0) as total_invoiced'),
                DB::raw('COALESCE(fines.total_fine, 0) as total_fine'),
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
        if (!empty($this->filters['due_only'])) {
            $q->where('sw.main_b', '<', 0);
        }

        return $q->get();
    }
}
