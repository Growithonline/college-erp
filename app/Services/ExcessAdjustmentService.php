<?php

namespace App\Services;

use App\Models\FeeExcessTransfer;
use App\Models\Student;
use App\Models\StudentWallet;
use Illuminate\Support\Facades\DB;

// Cash a student paid on one fee item beyond what it now costs ("excess") is re-allocated onto the
// fee items that are still pending, in the order they appear on the Collect Fee page — the same
// order One-time pay fills them in (Previous Due first, then the fees top to bottom). Nothing
// moves in the wallet or the books, only which fee the cash counts against (see
// WalletService::applyExcessTransfers()). Used by the Collect Fee page button and, later, by the
// promotion review.
class ExcessAdjustmentService
{
    /**
     * What an adjustment would do right now. Pure read.
     *
     * @return array{excess: array<string,float>, pending: array<string,float>, moves: array<int, array{from: string, to: string, amount: float}>, excess_total: float, adjustable_total: float, unused_advance: float}
     */
    public static function plan(Student $student, int $sessionId): array
    {
        $state  = WalletService::buildPromotionAwareFeeState($student, $sessionId);
        $excess = $state['excess'] ?? [];

        $eligible = collect($state['grouped_items'] ?? [])
            ->filter(fn($item) => WalletService::isExcessTransferable($item))
            ->pluck('label')
            ->unique()
            ->all();

        $pending = [];
        foreach (WalletService::buildPendingRows($student, $sessionId, $state) as $row) {
            $name = (string) $row['name'];
            if (in_array($name, $eligible, true) && (float) $row['pending'] > 0.005) {
                $pending[$name] = round((float) $row['pending'], 2);
            }
        }

        $moves = [];
        $left  = $pending;
        foreach ($excess as $from => $available) {
            foreach ($left as $to => $room) {
                if ($available <= 0.005) {
                    break;
                }
                if ($to === $from || $room <= 0.005) {
                    continue;
                }

                $amount = round(min($available, $room), 2);
                $moves[] = ['from' => $from, 'to' => $to, 'amount' => $amount];
                $available  = round($available - $amount, 2);
                $left[$to]  = round($room - $amount, 2);
            }
        }

        // Advance carried in from the previous session that this session's fees did not use up.
        // Still the student's money: it is carried on / refunded with the leftover excess.
        $unusedAdvance = max(0.0, round(AdvanceService::pool($student, $sessionId) - (float) ($state['advance_applied'] ?? 0), 2));

        return [
            'excess'           => $excess,
            'pending'          => $pending,
            'moves'            => $moves,
            'excess_total'     => round(array_sum($excess), 2),
            'adjustable_total' => round(array_sum(array_column($moves, 'amount')), 2),
            'unused_advance'   => $unusedAdvance,
        ];
    }

    /**
     * Books the planned moves. Recomputed inside a lock on the student's wallet row, so a
     * double-click or a collection happening at the same moment cannot over-adjust.
     *
     * @param  array{type?: string, id?: ?int}  $actor
     * @return array<int, array{from: string, to: string, amount: float}>
     */
    public static function apply(Student $student, int $sessionId, array $actor = []): array
    {
        return DB::transaction(function () use ($student, $sessionId, $actor) {
            StudentWallet::where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)
                ->lockForUpdate()
                ->first();

            $plan = self::plan($student, $sessionId);

            foreach ($plan['moves'] as $move) {
                FeeExcessTransfer::create([
                    'institute_id'        => $student->institute_id,
                    'student_id'          => $student->id,
                    'academic_session_id' => $sessionId,
                    'from_label'          => $move['from'],
                    'to_label'            => $move['to'],
                    'amount'              => $move['amount'],
                    'status'              => FeeExcessTransfer::ACTIVE,
                    'created_by_type'     => $actor['type'] ?? null,
                    'created_by_id'       => $actor['id'] ?? null,
                    'note'                => 'Excess adjusted against pending fee',
                ]);
            }

            if (!empty($plan['moves'])) {
                AuditLogService::log(
                    (int) $student->institute_id,
                    'fee_excess',
                    'adjusted',
                    'Excess adjusted for ' . $student->name . ': ' . number_format($plan['adjustable_total'], 2),
                    $student,
                    ['session_id' => $sessionId, 'moves' => $plan['moves']]
                );
            }

            return $plan['moves'];
        });
    }
}
