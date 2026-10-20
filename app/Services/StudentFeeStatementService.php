<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\Center;
use App\Models\ChannelPartner;
use App\Models\Course;
use App\Models\FeeInvoice;
use App\Models\Institute;
use App\Models\Student;
use App\Models\StudentAcademicIdentity;
use App\Models\StudentWallet;
use App\Models\Subject;
use Illuminate\Support\Collection;

/**
 * Full fee statement of one student: every academic year (session) up to a chosen session,
 * each with its fee items, every transaction, where the fee was collected and the balance.
 */
class StudentFeeStatementService
{
    public static function build(Student $student, ?int $asOfSessionId = null): array
    {
        $instituteId = (int) $student->institute_id;
        $student->loadMissing(['stream.course', 'coursePart', 'session']);

        $currentSessionId = (int) $student->academic_session_id;
        $asOfSessionId    = $asOfSessionId ?: $currentSessionId;

        // Every session in which the student has fee activity or an academic identity
        $sessionIds = StudentWallet::where('student_id', $student->id)
            ->where('institute_id', $instituteId)->pluck('academic_session_id')
            ->merge(FeeInvoice::where('student_id', $student->id)->where('is_cancelled', false)->pluck('academic_session_id'))
            ->merge(StudentAcademicIdentity::where('student_id', $student->id)->realOnly()->pluck('academic_session_id'))
            ->push($currentSessionId)
            ->filter()->unique()->sort()->values();

        // Session ids are NOT chronological (a later year can be created first) — order by start date.
        $sessionKey = fn(AcademicSession $s) => $s->start_date?->timestamp ?? (int) $s->id;
        $availableSessions = AcademicSession::whereIn('id', $sessionIds)->get()->sortBy($sessionKey)->keyBy('id');
        $asOfSession = AcademicSession::where('institute_id', $instituteId)->find($asOfSessionId);
        $asOfKey     = $asOfSession ? $sessionKey($asOfSession) : PHP_INT_MAX;
        $sessionIds  = $availableSessions->filter(fn(AcademicSession $s) => $sessionKey($s) <= $asOfKey)->keys();

        $centerNames  = [];
        $partnerNames = [];
        $nameOf = function (string $type, ?int $id) use (&$centerNames, &$partnerNames): ?string {
            if (!$id) {
                return null;
            }
            if ($type === 'center') {
                return $centerNames[$id] ??= Center::whereKey($id)->value('name');
            }

            return $partnerNames[$id] ??= ChannelPartner::whereKey($id)->value('name');
        };
        $instituteName = Institute::whereKey($instituteId)->value('name') ?? 'Institute';

        $blocks = collect();
        foreach ($sessionIds as $sessionId) {
            $sessionId = (int) $sessionId;
            $session   = $availableSessions[$sessionId] ?? null;
            if (!$session) {
                continue;
            }

            $invoices = FeeInvoice::with('items')
                ->where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)
                ->where('is_cancelled', false)
                ->orderBy('payment_date')->orderBy('id')
                ->get();

            $rows = WalletService::buildPendingRows($student, $sessionId);
            if ($rows->isEmpty() && $invoices->isEmpty()) {
                continue; // nothing was charged or paid in this session
            }

            $ctx      = WalletService::resolveAcademicContext($student, $sessionId);
            $identity = $ctx['identity'];
            $part     = $ctx['course_part'];

            $course = $identity?->course_id ? Course::find($identity->course_id) : null;
            $courseName = $course?->name ?? $student->stream?->course?->name ?? '';

            // Where the student was admitted from in that year (university direct / centre / partner)
            $source   = $identity?->admission_source_snapshot ?: $student->admission_source ?: 'direct';
            $sourceId = $identity?->admission_source_id_snapshot ?: $student->admission_source_id;
            $admissionPlace = match ($source) {
                'center'          => $nameOf('center', $sourceId) ?: 'Center',
                'channel_partner' => $nameOf('partner', $sourceId) ?: 'Channel Partner',
                default           => 'Direct',
            };

            $transactions = $invoices->map(function (FeeInvoice $invoice) use ($nameOf, $instituteName) {
                $feeCentre = $invoice->collected_by_center_id
                    ? ($nameOf('center', (int) $invoice->collected_by_center_id) ?: 'Center')
                    : ($invoice->collected_by_partner_id
                        ? ($nameOf('partner', (int) $invoice->collected_by_partner_id) ?: 'Channel Partner')
                        : $instituteName);

                return [
                    'date'       => $invoice->payment_date,
                    'receipt_no' => $invoice->invoice_no,
                    'paid'       => (float) $invoice->paid_amount,
                    'discount'   => (float) $invoice->discount,
                    'fine'       => (float) $invoice->items->sum('fine'),
                    'mode'       => strtoupper((string) $invoice->payment_mode),
                    'fee_centre' => $feeCentre,
                ];
            });

            $blocks->push([
                'session_id'      => $sessionId,
                'session_name'    => $session->name,
                'title'           => trim(strtoupper($courseName . ' ' . ($part?->part_name ?? ''))),
                'year_label'      => $part?->year_label,
                'semester'        => $ctx['semester'] ?? null,
                'admission_place' => $admissionPlace,
                'rows'            => $rows,
                'transactions'    => $transactions,
            ]);
        }

        // Fee lines, totals and carry-forward handling (oldest -> newest)
        $blocks = $blocks->values()->map(function (array $block, int $index) {
            $items = $block['rows']->map(function (array $row) use ($index) {
                // "Previous Due (...)" in a later year is the earlier year's unpaid balance brought forward
                $row['carried_in'] = $index > 0 && str_starts_with((string) $row['name'], 'Previous Due (');

                return $row;
            });

            $collegeFee = (float) $items->where('carried_in', false)->sum('charged');
            $carriedIn  = (float) $items->where('carried_in', true)->sum('charged');
            $fine       = (float) $items->sum('fine');
            $paid       = (float) $block['transactions']->sum('paid');
            $discount   = (float) $block['transactions']->sum('discount');
            $txnFine    = (float) $block['transactions']->sum('fine');

            $block['items']         = $items;
            $block['college_fee']   = $collegeFee;
            $block['carried_in']    = $carriedIn;
            $block['fine']          = max($fine, $txnFine);
            $block['session_fee']   = $collegeFee + $carriedIn + $block['fine'];
            $block['paid']          = $paid;
            $block['discount']      = $discount;
            $block['balance']       = $block['session_fee'] - $paid - $discount;
            unset($block['rows']);

            return $block;
        });

        // Newest year first (as on the printed receipt); totals never count a carried-in amount twice
        $totals = [
            'college_fee' => (float) $blocks->sum('college_fee'),
            'paid'        => (float) $blocks->sum('paid'),
            'discount'    => (float) $blocks->sum('discount'),
            'fine'        => (float) $blocks->sum('fine'),
        ];
        $totals['balance'] = $totals['college_fee'] + $totals['fine'] - $totals['paid'] - $totals['discount'];

        $blocks = $blocks->map(function (array $block, int $index) use ($blocks) {
            $next = $blocks->get($index + 1);
            $block['carried_forward'] = $next && (float) $next['carried_in'] > 0 && $block['balance'] > 0.009;

            return $block;
        })->reverse()->values();

        $last = $blocks->first();
        $ctxLast = $last ? WalletService::resolveAcademicContext($student, (int) $last['session_id']) : null;
        $subjectNames = $ctxLast && !empty($ctxLast['subject_ids'])
            ? Subject::whereIn('id', $ctxLast['subject_ids'])->orderBy('name')->pluck('name')->all()
            : [];

        $identityNow = $ctxLast['identity'] ?? null;

        return [
            'institute'      => Institute::find($instituteId),
            'student'        => $student,
            'identity'       => $identityNow,
            'subject_names'  => $subjectNames,
            'blocks'         => $blocks,
            'totals'         => $totals,
            'as_of_session'  => $asOfSession ?? $student->session,
            'sessions'       => $availableSessions->values(),
            'address'        => self::address($student),
        ];
    }

    private static function address(Student $student): string
    {
        return collect([
            $student->perm_address, $student->perm_village, $student->perm_post, $student->perm_thana,
            $student->perm_district, $student->perm_state, $student->perm_pincode,
        ])->filter(fn($v) => filled($v))->implode(', ');
    }
}
