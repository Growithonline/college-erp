<?php

namespace App\Services;

use App\Models\CoursePart;
use App\Models\CourseStream;
use App\Models\FeeInvoice;
use App\Models\StudentAcademicChangeLog;
use App\Models\Student;
use App\Models\StudentAcademicIdentity;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\SubjectChangeLog;
use App\Support\StudentSnapshotBuilder;
use Illuminate\Support\Facades\DB;

class StudentAcademicChangeService
{
    private const SUBJECT_LINKED_ITEM_TYPES = ['subject', 'practical', 'subject_assignment'];

    // ─── Auth helper — works across all guards ────────────────────────────
    private static function resolveActorId(): ?int
    {
        foreach (['staff', 'center', 'partner', 'web'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return auth()->guard($guard)->id();
            }
        }

        return auth()->id();
    }

    // ─── Build fee snapshot for a given subject set ───────────────────────
    public static function buildSnapshot(
        Student $student,
        ?int $courseStreamId = null,
        ?int $coursePartId = null,
        ?array $subjectIds = null,
        array $overrides = []
    ): array {
        $student->loadMissing(['stream.course', 'coursePart']);

        $sessionId = (int) ($overrides['academic_session_id'] ?? $student->academic_session_id);
        $semester  = max(1, (int) ($overrides['semester'] ?? $student->current_semester ?? 1));

        $stream = $courseStreamId
            ? CourseStream::with('course')->findOrFail($courseStreamId)
            : $student->stream()->with('course')->first();

        $part = $coursePartId
            ? CoursePart::find($coursePartId)
            : ($student->coursePart ?: null);

        $yearNumber = max(1, (int) ($part?->year_number ?? 1));
        $subjectIds = $subjectIds ?? self::currentSubjectIds($student, $sessionId, $yearNumber);

        $feeData = FeeCalculatorService::calculate(
            instituteId:     (int) $student->institute_id,
            sessionId:       $sessionId,
            courseId:        (int) ($stream?->course_id ?? 0),
            coursePart:      $yearNumber,
            semester:        $semester,
            studentType:     (string) ($overrides['student_type']     ?? $student->student_type     ?? 'regular'),
            admissionSource: (string) ($overrides['admission_source'] ?? $student->admission_source ?? 'direct'),
            category:        (string) ($overrides['category']         ?? $student->category         ?? 'general'),
            gender:          (string) ($overrides['gender']           ?? $student->gender           ?? 'other'),
            subjectIds:      $subjectIds,
            courseStreamId:  $stream?->id,
            coursePartId:    $part?->id
        );

        return [
            'academic_session_id' => $sessionId,
            'semester'            => $semester,
            'course_stream_id'    => $stream?->id,
            'course_id'           => (int) ($stream?->course_id ?? 0),
            'course_part_id'      => $part?->id,
            'course_part_year'    => $yearNumber,
            'subject_ids'         => array_values(array_unique(array_map('intval', $subjectIds))),
            'fee_data'            => $feeData,
            'recalculable_total'  => (float) ($feeData['total'] ?? 0),
        ];
    }

    // ─── Current enrolled subject IDs for a student ───────────────────────
    // BUG FIX: Fallback no longer returns wrong-year subjects
    public static function currentSubjectIds(Student $student, int $sessionId, ?int $yearNumber = null): array
    {
        $query = StudentSubject::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId);

        if ($yearNumber) {
            $query->where('year_number', $yearNumber);
        }

        return $query->pluck('subject_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        // OLD CODE had a dangerous fallback here that returned subjects from ANY year
        // when the target year had none — removed to prevent wrong fee calculation.
    }

    // ─── Sync student_subjects for a year ────────────────────────────────
    public static function syncSubjects(
        Student $student,
        int $sessionId,
        int $yearNumber,
        array $subjectRoleRows
    ): array {
        $finalSubjectIds = [];

        StudentSubject::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('year_number', $yearNumber)
            ->delete();

        foreach ($subjectRoleRows as $row) {
            $subjectId = (int) ($row['subject_id'] ?? 0);
            if ($subjectId <= 0) {
                continue;
            }

            StudentSubject::create([
                'student_id'          => $student->id,
                'subject_id'          => $subjectId,
                'academic_session_id' => $sessionId,
                'year_number'         => $yearNumber,
                'subject_role'        => $row['subject_role'] ?? 'minor',
                'is_auto_included'    => (bool) ($row['is_auto_included'] ?? false),
            ]);

            $finalSubjectIds[] = $subjectId;
        }

        return array_values(array_unique(array_map('intval', $finalSubjectIds)));
    }

    public static function currentRoleMap(Student $student, int $sessionId, int $yearNumber): array
    {
        return StudentSubject::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('year_number', $yearNumber)
            ->pluck('subject_role', 'subject_id')
            ->map(fn($role) => (string) $role)
            ->all();
    }

    // ─── NEW: Subject-level change with 2A/2B logic ───────────────────────
    /**
     * Handles subject add/remove with proper per-subject fee tracking.
     *
     * Sub-case 2A (credit_cancel): removed subject's fee was UNPAID  → direct credit
     * Sub-case 2B (credit_note):  removed subject's fee was PAID     → wallet advance credit
     * Case 1     (debit):         subject added                      → wallet debit
     *
     * Creates:
     *   - ONE wallet transaction (total delta)
     *   - N rows in subject_change_logs (one per added/removed subject)
     *   - ONE row in student_academic_change_logs
     */
    public static function applySubjectChange(
        Student $student,
        array $oldSnapshot,
        array $newSnapshot,
        array $oldRoleMap = [],  // [subject_id => role] before change
        array $newRoleMap = [],  // [subject_id => role] after change
        ?int $actorId = null,
        ?string $actorType = null,
        ?string $actorName = null
    ): array {
        $actorId   = $actorId ?? self::resolveActorId();
        $sessionId = (int) ($newSnapshot['academic_session_id'] ?? $student->academic_session_id);
        $semester  = (int) ($newSnapshot['semester'] ?? 1);
        $yearNumber = (int) ($newSnapshot['course_part_year'] ?? 1);

        $oldSubjectIds = array_map('intval', $oldSnapshot['subject_ids'] ?? []);
        $newSubjectIds = array_map('intval', $newSnapshot['subject_ids'] ?? []);

        $addedIds   = array_values(array_diff($newSubjectIds, $oldSubjectIds));
        $removedIds = array_values(array_diff($oldSubjectIds, $newSubjectIds));

        $oldFeeTotal = (float) ($oldSnapshot['recalculable_total'] ?? 0);
        $newFeeTotal = (float) ($newSnapshot['recalculable_total'] ?? 0);
        $delta       = round($newFeeTotal - $oldFeeTotal, 2);

        // Per-subject fee items from snapshots
        $oldItems = collect($oldSnapshot['fee_data']['items'] ?? []);
        $newItems = collect($newSnapshot['fee_data']['items'] ?? []);

        // Total actually paid via invoices (for paid/unpaid split)
        $totalInvoicePaid = (float) FeeInvoice::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('is_cancelled', false)
            ->sum('paid_amount');

        // How much of the OLD total is still unpaid
        // (used to allocate paid vs unpaid portions across removed subjects)
        $remainingUnpaid = max(0.0, $oldFeeTotal - $totalInvoicePaid);

        // Preload subject names
        $allChangedIds = array_merge($addedIds, $removedIds);
        $subjectMeta   = Subject::whereIn('id', $allChangedIds)
            ->get(['id', 'name', 'code'])
            ->keyBy('id');

        $changeRows  = [];
        $descriptions = [];

        // ── Removed subjects (process first for paid/unpaid allocation) ──
        foreach ($removedIds as $subjectId) {
            $breakdown = self::subjectImpactBreakdown($oldItems, $subjectId);
            $subjectFee = $breakdown['subject_fee'];
            $practicalFee = $breakdown['practical_fee'];
            $totalImpact = -$breakdown['total']; // negative = credit

            // Allocate paid/unpaid portions
            $absImpact      = abs($totalImpact);
            $unpaidPortion  = min($absImpact, $remainingUnpaid);
            $paidPortion    = max(0.0, $absImpact - $unpaidPortion);
            $remainingUnpaid = max(0.0, $remainingUnpaid - $unpaidPortion);

            $adjustmentType = $paidPortion > 0.005 ? 'credit_note' : 'credit_cancel';

            $subject = $subjectMeta->get($subjectId);
            $name    = $subject?->name ?? ('Subject #' . $subjectId);

            $changeRows[] = [
                'subject_id'       => $subjectId,
                'subject_name'     => $name,
                'subject_code'     => $subject?->code,
                'action'           => 'removed',
                'previous_role'    => $oldRoleMap[$subjectId] ?? null,
                'new_role'         => null,
                'subject_fee'      => $subjectFee,
                'practical_fee'    => $practicalFee,
                'total_fee_impact' => $totalImpact,
                'paid_portion'     => round($paidPortion, 2),
                'unpaid_portion'   => round($unpaidPortion, 2),
                'adjustment_type'  => $adjustmentType,
            ];

            $suffix = $adjustmentType === 'credit_note'
                ? " (₹{$paidPortion} credit note — already paid)"
                : " (₹{$absImpact} cancelled — unpaid)";
            $descriptions[] = "Subject Removed: {$name}{$suffix}";
        }

        // ── Added subjects ───────────────────────────────────────────────
        foreach ($addedIds as $subjectId) {
            $breakdown = self::subjectImpactBreakdown($newItems, $subjectId);
            $subjectFee = $breakdown['subject_fee'];
            $practicalFee = $breakdown['practical_fee'];
            $totalImpact = $breakdown['total']; // positive = charge

            $subject = $subjectMeta->get($subjectId);
            $name    = $subject?->name ?? ('Subject #' . $subjectId);

            $changeRows[] = [
                'subject_id'       => $subjectId,
                'subject_name'     => $name,
                'subject_code'     => $subject?->code,
                'action'           => 'added',
                'previous_role'    => null,
                'new_role'         => $newRoleMap[$subjectId] ?? null,
                'subject_fee'      => $subjectFee,
                'practical_fee'    => $practicalFee,
                'total_fee_impact' => $totalImpact,
                'paid_portion'     => 0.00,
                'unpaid_portion'   => round($totalImpact, 2),
                'adjustment_type'  => 'debit',
            ];

            $descriptions[] = "Subject Added: {$name} (+₹{$totalImpact})";
        }

        // ── If nothing changed, return early ─────────────────────────────
        if (empty($changeRows) && abs($delta) < 0.01) {
            return [
                'old_total'      => $oldFeeTotal,
                'new_total'      => $newFeeTotal,
                'delta'          => 0.0,
                'wallet_balance' => (float) StudentWallet::where('student_id', $student->id)
                    ->where('academic_session_id', $sessionId)
                    ->value('main_b'),
                'transaction_id' => null,
                'change_rows'    => [],
            ];
        }

        $transactionId  = null;
        $walletBalAfter = null;

        DB::transaction(function () use (
            $student, $sessionId, $delta, $descriptions,
            $actorId, $actorType, $actorName,
            $changeRows, $semester, $yearNumber,
            &$transactionId, &$walletBalAfter
        ) {
            // ── Create wallet transaction if fee changed ──────────────────
            if (abs($delta) >= 0.01) {
                StudentWallet::firstOrCreate(
                    ['student_id' => $student->id, 'academic_session_id' => $sessionId],
                    ['institute_id' => $student->institute_id, 'main_b' => 0.00]
                );

                $wallet = StudentWallet::where('student_id', $student->id)
                    ->where('academic_session_id', $sessionId)
                    ->lockForUpdate()
                    ->first();

                $opBal = (float) $wallet->main_b;
                $clBal = round($opBal - $delta, 2); // debit if delta>0, credit if delta<0

                $description = implode(' | ', $descriptions)
                    ?: 'Subject change fee adjustment';

                $txn = StudentTransaction::create([
                    'student_id'          => $student->id,
                    'institute_id'        => $student->institute_id,
                    'academic_session_id' => $sessionId,
                    'des'                 => $description,
                    'credit'              => $delta < 0 ? abs($delta) : 0.00,
                    'debit'               => $delta > 0 ? $delta      : 0.00,
                    'type'                => $delta > 0
                        ? StudentTransaction::DEBIT
                        : StudentTransaction::CREDIT,
                    'date'                => now()->toDateString(),
                    'op_bal'              => $opBal,
                    'cl_bal'              => $clBal,
                    'by_user_id'          => $actorId,
                ]);

                $wallet->main_b = $clBal;
                $wallet->save();

                $transactionId  = $txn->id;
                $walletBalAfter = $clBal;
            } else {
                $walletBalAfter = (float) StudentWallet::where('student_id', $student->id)
                    ->where('academic_session_id', $sessionId)
                    ->value('main_b');
            }

            // ── Log each subject change ───────────────────────────────────
            foreach ($changeRows as $row) {
                SubjectChangeLog::create([
                    'student_id'          => $student->id,
                    'institute_id'        => $student->institute_id,
                    'academic_session_id' => $sessionId,
                    'year_number'         => $yearNumber,
                    'semester'            => $semester,
                    'subject_id'          => $row['subject_id'],
                    'subject_name'        => $row['subject_name'],
                    'subject_code'        => $row['subject_code'],
                    'action'              => $row['action'],
                    'previous_role'       => $row['previous_role'],
                    'new_role'            => $row['new_role'],
                    'subject_fee'         => $row['subject_fee'],
                    'practical_fee'       => $row['practical_fee'],
                    'total_fee_impact'    => $row['total_fee_impact'],
                    'paid_portion'        => $row['paid_portion'],
                    'unpaid_portion'      => $row['unpaid_portion'],
                    'adjustment_type'     => $row['adjustment_type'],
                    'transaction_id'      => $transactionId,
                    'by_user_id'          => $actorId,
                    'actor_type'          => $actorType,
                    'actor_name'          => $actorName,
                ]);
            }
        });

        return [
            'old_total'      => $oldFeeTotal,
            'new_total'      => $newFeeTotal,
            'delta'          => $delta,
            'wallet_balance' => $walletBalAfter,
            'transaction_id' => $transactionId,
            'change_rows'    => $changeRows,
        ];
    }

    // ─── Legacy: simple total diff (kept for backward compat) ─────────────
    public static function applyFeeDelta(
        Student $student,
        array $oldSnapshot,
        array $newSnapshot,
        ?string $reason = null,
        ?int $actorId = null
    ): array {
        $actorId   = $actorId ?? self::resolveActorId(); // BUG FIX: was auth()->id() ?? 0
        $sessionId = (int) ($newSnapshot['academic_session_id'] ?? $student->academic_session_id);
        $oldTotal  = (float) ($oldSnapshot['recalculable_total'] ?? 0);
        $newTotal  = (float) ($newSnapshot['recalculable_total'] ?? 0);
        $delta     = round($newTotal - $oldTotal, 2);

        if (abs($delta) < 0.01) {
            return [
                'old_total'      => $oldTotal,
                'new_total'      => $newTotal,
                'delta'          => 0.0,
                'wallet_balance' => (float) StudentWallet::where('student_id', $student->id)
                    ->where('academic_session_id', $sessionId)
                    ->value('main_b'),
            ];
        }

        $description = trim((string) ($reason ?: 'Academic fee adjustment after course / subject update'));

        DB::transaction(function () use ($student, $sessionId, $delta, $description, $actorId) {
            StudentWallet::firstOrCreate(
                ['student_id' => $student->id, 'academic_session_id' => $sessionId],
                ['institute_id' => $student->institute_id, 'main_b' => 0.00]
            );

            $wallet = StudentWallet::where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)
                ->lockForUpdate()
                ->first();

            $opBal = (float) $wallet->main_b;
            $clBal = round($opBal - $delta, 2);

            StudentTransaction::create([
                'student_id'          => $student->id,
                'institute_id'        => $student->institute_id,
                'academic_session_id' => $sessionId,
                'des'                 => $description,
                'credit'              => $delta < 0 ? abs($delta) : 0.00,
                'debit'               => $delta > 0 ? $delta      : 0.00,
                'type'                => $delta > 0
                    ? StudentTransaction::DEBIT
                    : StudentTransaction::CREDIT,
                'date'                => now()->toDateString(),
                'op_bal'              => $opBal,
                'cl_bal'              => $clBal,
                'by_user_id'          => $actorId,
            ]);

            $wallet->main_b = $clBal;
            $wallet->save();
        });

        return [
            'old_total'      => $oldTotal,
            'new_total'      => $newTotal,
            'delta'          => $delta,
            'wallet_balance' => (float) StudentWallet::where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)
                ->value('main_b'),
        ];
    }

    // ─── Create overall academic change log ───────────────────────────────
    public static function createChangeLog(
        Student $student,
        array $oldSnapshot,
        array $newSnapshot,
        array $adjustment,
        ?string $actorType = null,
        ?string $actorName = null,
        ?string $reason = null,
        ?string $notes = null
    ): ?StudentAcademicChangeLog {
        if (!self::hasMeaningfulChange($oldSnapshot, $newSnapshot, $adjustment)) {
            return null;
        }

        return StudentAcademicChangeLog::create([
            'student_id'          => $student->id,
            'institute_id'        => $student->institute_id,
            'academic_session_id' => $newSnapshot['academic_session_id'] ?? $student->academic_session_id,
            'old_snapshot'        => self::decorateSnapshot($oldSnapshot),
            'new_snapshot'        => self::decorateSnapshot($newSnapshot),
            'old_academic_fee'    => (float) ($adjustment['old_total'] ?? 0),
            'new_academic_fee'    => (float) ($adjustment['new_total'] ?? 0),
            'fee_delta'           => (float) ($adjustment['delta']     ?? 0),
            'wallet_balance_after'=> (float) ($adjustment['wallet_balance'] ?? 0),
            'actor_type'          => $actorType,
            'actor_name'          => $actorName,
            'reason'              => $reason,
            'notes'               => $notes,
        ]);
    }

    // ─── Sync academic identity snapshot ─────────────────────────────────
    public static function syncCurrentIdentity(Student $student, array $subjectIds): void
    {
        $sessionId = (int) $student->academic_session_id;
        $semester  = (int) ($student->current_semester ?? 1);

        $identity = StudentAcademicIdentity::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('semester_at_time', $semester)
            ->realOnly()
            ->latest('id')
            ->first();

        $payload = [
            'institute_id'                 => $student->institute_id,
            'course_id'                    => $student->stream?->course_id,
            'course_stream_id'             => $student->course_stream_id,
            'course_part_id'               => $student->course_part_id,
            'semester_at_time'             => $semester,
            'subjects_json'                => array_values(array_unique(array_map('intval', $subjectIds))),
            'sr_no_snapshot'               => $student->sr_no,
            'enrollment_no_snapshot'       => $student->enrollment_no,
            'roll_no_snapshot'             => $student->roll_no,
            'admission_source_snapshot'    => $student->admission_source,
            'student_uid_snapshot'         => $student->student_uid,
            'institute_form_no_snapshot'   => $student->institute_form_no,
            'exam_form_no_snapshot'        => $student->exam_form_no,
            'uin_no_snapshot'              => $student->uin_no,
            'reference_no_snapshot'        => $student->reference_no,
            'admission_source_id_snapshot' => $student->admission_source_id,
            'submitted_date_snapshot'      => $student->submitted_date,
            'admission_date_snapshot'      => $student->admission_date,
            'student_status_snapshot'      => $student->status,
            'admission_type'               => $student->admission_type ?? 'new',
            'profile_snapshot'             => StudentSnapshotBuilder::build($student),
        ];

        if ($identity) {
            $identity->update($payload);
            return;
        }

        StudentAcademicIdentity::create($payload + [
            'student_id'          => $student->id,
            'academic_session_id' => $sessionId,
            'source'              => StudentAcademicIdentity::SOURCE_ADMISSION,
        ]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    private static function decorateSnapshot(array $snapshot): array
    {
        $subjectIds  = array_values(array_unique(array_map('intval', $snapshot['subject_ids'] ?? [])));
        $subjectNames = Subject::whereIn('id', $subjectIds)
            ->pluck('name', 'id')
            ->mapWithKeys(fn($name, $id) => [(int) $id => $name])
            ->all();

        $stream = !empty($snapshot['course_stream_id'])
            ? CourseStream::with('course')->find($snapshot['course_stream_id'])
            : null;
        $part   = !empty($snapshot['course_part_id'])
            ? CoursePart::find($snapshot['course_part_id'])
            : null;

        return [
            'academic_session_id' => (int) ($snapshot['academic_session_id'] ?? 0),
            'semester'            => (int) ($snapshot['semester'] ?? 0),
            'course_id'           => (int) ($snapshot['course_id'] ?? 0),
            'course_name'         => $stream?->course?->name,
            'course_stream_id'    => (int) ($snapshot['course_stream_id'] ?? 0),
            'stream_name'         => $stream?->name,
            'course_part_id'      => (int) ($snapshot['course_part_id'] ?? 0),
            'course_part_year'    => (int) ($snapshot['course_part_year'] ?? 0),
            'course_part_name'    => $part?->year_label,
            'subject_ids'         => $subjectIds,
            'subject_names'       => array_values(array_map(
                fn($id) => $subjectNames[$id] ?? ('Subject #' . $id),
                $subjectIds
            )),
            'recalculable_total'  => (float) ($snapshot['recalculable_total'] ?? 0),
        ];
    }

    private static function hasMeaningfulChange(array $oldSnapshot, array $newSnapshot, array $adjustment): bool
    {
        $oldSubjects = array_values(array_unique(array_map('intval', $oldSnapshot['subject_ids'] ?? [])));
        $newSubjects = array_values(array_unique(array_map('intval', $newSnapshot['subject_ids'] ?? [])));
        sort($oldSubjects);
        sort($newSubjects);

        return (int) ($oldSnapshot['course_stream_id'] ?? 0) !== (int) ($newSnapshot['course_stream_id'] ?? 0)
            || (int) ($oldSnapshot['course_part_id']   ?? 0) !== (int) ($newSnapshot['course_part_id']   ?? 0)
            || $oldSubjects !== $newSubjects
            || abs((float) ($adjustment['delta'] ?? 0)) >= 0.01;
    }

    private static function subjectImpactBreakdown($items, int $subjectId): array
    {
        $subjectFeeItems = collect($items)
            ->where('subject_id', $subjectId)
            ->whereIn('type', self::SUBJECT_LINKED_ITEM_TYPES);

        $practicalFee = round((float) $subjectFeeItems->where('type', 'practical')->sum('amount'), 2);
        $subjectFee = round((float) $subjectFeeItems->reject(
            fn($item) => ($item['type'] ?? null) === 'practical'
        )->sum('amount'), 2);

        return [
            'subject_fee' => $subjectFee,
            'practical_fee' => $practicalFee,
            'total' => round($subjectFee + $practicalFee, 2),
        ];
    }

    private static function formatAmount(float $amount): string
    {
        return 'Rs. ' . number_format(round($amount, 2), 2, '.', '');
    }
}
