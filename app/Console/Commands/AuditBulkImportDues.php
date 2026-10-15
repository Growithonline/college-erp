<?php

namespace App\Console\Commands;

use App\Models\FeeInvoice;
use App\Models\Student;
use App\Models\StudentTransaction;
use App\Services\WalletService;
use Illuminate\Console\Command;

// Read-only diagnostic — never writes anything. For every bulk-imported student who has
// already been reviewed/approved (BulkImportApprovalController), compares three numbers
// that should all agree:
//   1. Sum of FeeInvoice.remaining_due across their "Imported via Bulk Excel" historical
//      invoices — the per-period due exactly as the reviewer entered it at approval time.
//   2. Sum of the "Previous Due (Semester N) ... Excel Import" debit transactions actually
//      carried into their current session (WalletService::chargeBulkImportPreviousDue()).
//   3. What WalletService::buildPendingRows() says is STILL outstanding for those same
//      "Previous Due (...)" items today (after any already-paid netting).
// (1) and (2) should always match (same value, written in the same approval request).
// If (3) is lower than (2) with nothing actually collected against it yet, that is the
// "due appears reduced by an unrelated payment" symptom being investigated.
class AuditBulkImportDues extends Command
{
    protected $signature = 'bulk-import:audit-dues {--institute=} {--student=}';

    protected $description = 'Read-only report comparing each bulk-imported student\'s recorded historical due against what the Wallet currently shows as outstanding';

    public function handle(): int
    {
        $query = Student::where('is_bulk_import', true)->where('status', '!=', 'pending');

        if ($this->option('institute')) {
            $query->where('institute_id', (int) $this->option('institute'));
        }
        if ($this->option('student')) {
            $query->where('id', (int) $this->option('student'));
        }

        $students = $query->orderBy('id')->get();
        $rows = [];
        $mismatches = 0;

        foreach ($students as $student) {
            $historicalInvoices = FeeInvoice::where('student_id', $student->id)
                ->where('remarks', 'like', 'Imported via Bulk Excel%')
                ->orderBy('semester')
                ->get(['id', 'semester', 'academic_session_id', 'paid_amount', 'remaining_due']);

            $recordedDueSum = round((float) $historicalInvoices->sum('remaining_due'), 2);
            $periodsText = $historicalInvoices->map(fn($i) => "S{$i->semester}:paid=" . number_format((float) $i->paid_amount, 0) . "/due=" . number_format((float) $i->remaining_due, 0))->implode(', ');

            $currentSessionId = (int) $student->academic_session_id;

            $previousDueDebits = StudentTransaction::where('student_id', $student->id)
                ->where('type', StudentTransaction::DEBIT)
                ->where('des', 'like', 'Previous Due (%')
                ->where('des', 'like', '%Fee details updated via Excel Import%')
                ->selectRaw('academic_session_id, des, SUM(debit) as amount')
                ->groupBy('academic_session_id', 'des')
                ->get();

            $carriedForwardSum = round((float) $previousDueDebits->sum('amount'), 2);
            $offSessionDebits = $previousDueDebits->where('academic_session_id', '!=', $currentSessionId);

            $liveRows = WalletService::buildPendingRows($student, $currentSessionId);
            $liveBulkImportDuePending = round(
                (float) $liveRows
                    ->filter(fn($r) => str_starts_with((string) ($r['name'] ?? ''), 'Previous Due ('))
                    ->sum('pending'),
                2
            );

            $recordedVsCarried = round($recordedDueSum - $carriedForwardSum, 2);
            $carriedVsLive = round($carriedForwardSum - $liveBulkImportDuePending, 2);

            $flags = [];
            if (abs($recordedVsCarried) > 0.01) {
                $flags[] = 'recorded<>carried diff=' . $recordedVsCarried;
            }
            if (abs($carriedVsLive) > 0.01) {
                $flags[] = 'carried<>live diff=' . $carriedVsLive;
            }
            if ($offSessionDebits->isNotEmpty()) {
                $flags[] = 'off-session debit!';
            }

            $isMismatch = !empty($flags);
            if ($isMismatch) {
                $mismatches++;
            }

            $rows[] = [
                $student->id,
                $student->student_uid,
                mb_substr($student->name, 0, 20),
                $historicalInvoices->count(),
                mb_substr($periodsText, 0, 40),
                number_format($recordedDueSum, 2),
                number_format($carriedForwardSum, 2),
                number_format($liveBulkImportDuePending, 2),
                $isMismatch ? implode(' | ', $flags) : 'ok',
            ];
        }

        $this->table(
            ['id', 'uid', 'name', '#periods', 'periods (paid/due)', 'sum due entered', 'sum carried-fwd', 'live pending today', 'status'],
            $rows
        );

        $this->info(sprintf(
            '%d bulk-import reviewed student(s) checked, %d mismatch(es) found.',
            $students->count(),
            $mismatches
        ));

        return self::SUCCESS;
    }
}
