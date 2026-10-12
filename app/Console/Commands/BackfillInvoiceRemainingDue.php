<?php

namespace App\Console\Commands;

use App\Models\FeeInvoice;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use Illuminate\Console\Command;

class BackfillInvoiceRemainingDue extends Command
{
    protected $signature = 'fee:backfill-remaining-due
        {--apply : Actually write the values (default is a dry run that writes nothing)}
        {--institute= : Only this institute id}
        {--limit=0 : Stop after N invoices (0 = no limit)}';

    protected $description = 'Fill FeeInvoice.remaining_due for old invoices where it is NULL, from the student ledger closing balance right after that invoice\'s payment';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = (int) $this->option('limit');

        $query = FeeInvoice::whereNull('remaining_due')
            ->where('is_cancelled', false)
            ->where(function ($q) {
                $q->whereNull('approval_status')->orWhere('approval_status', FeeInvoice::STATUS_APPROVED);
            });

        if ($this->option('institute')) {
            $query->where('institute_id', (int) $this->option('institute'));
        }

        $rows = [];
        $stats = ['seen' => 0, 'from_ledger' => 0, 'no_ledger' => 0, 'differs_from_running' => 0, 'written' => 0];
        $runningCache = [];

        $query->chunkById(200, function ($invoices) use (&$rows, &$stats, &$runningCache, $apply, $limit) {
            foreach ($invoices as $invoice) {
                if ($limit > 0 && $stats['seen'] >= $limit) {
                    return false;
                }
                $stats['seen']++;

                $ledgerDue = $this->ledgerDue($invoice);
                if ($ledgerDue === null) {
                    $stats['no_ledger']++;
                    $rows[] = [$invoice->id, $invoice->invoice_no, $invoice->student_id, '-', '-', 'SKIP (no ledger row)'];
                    continue;
                }
                $stats['from_ledger']++;

                $runningDue = $this->runningDue($invoice, $runningCache);
                $differs = $runningDue === null || abs($runningDue - $ledgerDue) > 0.01;
                if ($differs) {
                    $stats['differs_from_running']++;
                }

                $note = $differs ? 'differs from running calc' : 'same';
                if ($apply) {
                    // whereNull guard: never overwrite a snapshot that appeared in the meantime.
                    $stats['written'] += FeeInvoice::where('id', $invoice->id)
                        ->whereNull('remaining_due')
                        ->update(['remaining_due' => $ledgerDue]);
                    $note .= ' | written';
                }

                $rows[] = [
                    $invoice->id,
                    $invoice->invoice_no,
                    $invoice->student_id,
                    number_format($ledgerDue, 2, '.', ''),
                    $runningDue === null ? '-' : number_format($runningDue, 2, '.', ''),
                    $note,
                ];
            }
        });

        $this->table(['id', 'invoice_no', 'student', 'ledger due', 'running due', 'note'], $rows);
        $this->info(sprintf(
            '%s: %d NULL invoices seen, %d have a ledger value, %d skipped, %d differ from the running calc%s.',
            $apply ? 'APPLIED' : 'DRY RUN (nothing written)',
            $stats['seen'],
            $stats['from_ledger'],
            $stats['no_ledger'],
            $stats['differs_from_running'],
            $apply ? ", {$stats['written']} rows written" : ''
        ));

        return self::SUCCESS;
    }

    // Closing wallet balance right after this invoice's payment/discount rows were posted
    // (fine and custom charges are posted before them, so they are already counted).
    // The wallet is negative while a student owes money, so due = -balance, floored at 0.
    private function ledgerDue(FeeInvoice $invoice): ?float
    {
        $last = StudentTransaction::where('fee_invoice_id', $invoice->id)
            ->where('student_id', $invoice->student_id)
            ->where('academic_session_id', $invoice->academic_session_id)
            ->where(function ($q) {
                $q->where('des', 'like', 'Fee paid - Invoice:%')
                    ->orWhere('des', 'like', 'Discount granted - Invoice:%');
            })
            ->orderByDesc('id')
            ->first();

        return $last ? max(0.0, round(-(float) $last->cl_bal, 2)) : null;
    }

    // The fallback the history/list pages use today, shown only for comparison:
    // (all paid in the session + today's live due) minus payments made up to this invoice.
    private function runningDue(FeeInvoice $invoice, array &$cache): ?float
    {
        $key = $invoice->student_id . ':' . $invoice->academic_session_id;

        if (!isset($cache[$key])) {
            $wallet = StudentWallet::where('student_id', $invoice->student_id)
                ->where('academic_session_id', $invoice->academic_session_id)
                ->first();
            $currentDue = $wallet && (float) $wallet->main_b < 0 ? abs((float) $wallet->main_b) : 0.0;

            $paid = FeeInvoice::where('student_id', $invoice->student_id)
                ->where('academic_session_id', $invoice->academic_session_id)
                ->where('is_cancelled', false)
                ->orderBy('payment_date')
                ->orderBy('id')
                ->get(['id', 'paid_amount']);

            $running = $paid->sum('paid_amount') + $currentDue;
            $map = [];
            foreach ($paid as $p) {
                $running -= (float) $p->paid_amount;
                $map[$p->id] = max(0.0, round($running, 2));
            }
            $cache[$key] = $map;
        }

        return $cache[$key][$invoice->id] ?? null;
    }
}
