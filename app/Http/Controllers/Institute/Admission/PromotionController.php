<?php

namespace App\Http\Controllers\Institute\Admission;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CoursePart;
use App\Models\CourseType;
use App\Models\CourseStreamSubject;
use App\Models\PromotionLog;
use App\Models\Student;
use App\Models\StudentAcademicIdentity;
use App\Models\StudentEducationDetail;
use App\Models\Subject;
use App\Models\StudentSubject;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use App\Http\Controllers\Institute\Master\AdmissionFormController;
use App\Services\FeeCalculatorService;
use App\Services\WalletService;
use App\Support\SimpleSpreadsheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PromotionController extends Controller
{
    private const TERMINAL_STATUSES = ['passed_out', 'backlog', 'failed', 'dropped'];

    private function hasPromotionLogColumn(): bool
    {
        static $hasPromotionLogColumn = null;

        if ($hasPromotionLogColumn === null) {
            $hasPromotionLogColumn = Schema::hasColumn('student_transactions', 'promotion_log_id');
        }

        return $hasPromotionLogColumn;
    }

    private function previousDueDescription(?AcademicSession $fromSession): string
    {
        return 'Previous Due (' . ($fromSession?->name ?? 'Last Session') . ')';
    }

    private function previousDueLikePattern(): string
    {
        return 'Previous Due (%';
    }

    private function promotionFeeLikePattern(int $semester): string
    {
        return 'Promotion fee:%(Sem ' . $semester . ')';
    }

    private function fallbackPromotionTransactionQuery(PromotionLog $log)
    {
        $query = StudentTransaction::where('student_id', $log->student_id)
            ->where('created_at', '>=', $log->created_at);

        if ($log->promotion_type === 'semester') {
            return $query->where('academic_session_id', $log->to_session_id ?? $log->from_session_id)
                ->where('des', 'like', $this->promotionFeeLikePattern((int) $log->to_semester));
        }

        $fromSession = $log->fromSession ?? AcademicSession::find($log->from_session_id);

        return $query->where('academic_session_id', $log->to_session_id ?? $log->from_session_id)
            ->where(function ($transactionQuery) use ($log, $fromSession) {
                $transactionQuery->where('des', 'like', $this->previousDueLikePattern())
                    ->orWhere('des', 'like', $this->promotionFeeLikePattern((int) $log->to_semester));
            });
    }

    private function instituteId(): int
    {
        return session('institute_id')
            ?? $this->authenticatedUser()?->institute_id
            ?? abort(403);
    }

    private function currentStaff(): ?\App\Models\StaffMember
    {
        return Auth::guard('staff')->user();
    }

    private function authenticatedUser()
    {
        return $this->currentStaff() ?: Auth::user();
    }

    private function ensurePromotionAccess(): void
    {
        $staff = $this->currentStaff();

        if (!$staff) {
            return;
        }

        abort_if(!$staff->hasPermission('student_promote'), 403, 'You are not allowed to promote students.');
    }

    private function ensureStaffCanAccessCourse(?int $courseId): void
    {
        $staff = $this->currentStaff();

        if ($staff && $courseId) {
            abort_if(!$staff->canAccessCourse($courseId), 403, 'Selected course is outside your access scope.');
        }
    }

    private function ensureStaffCanAccessSession(?int $sessionId): void
    {
        $staff = $this->currentStaff();

        if ($staff && $sessionId) {
            abort_if(!$staff->canAccessAcademicSession($sessionId), 403, 'Selected session is outside your access scope.');
        }
    }

    private function ensureStaffCanAccessStudent(Student $student): void
    {
        $staff = $this->currentStaff();

        if ($staff) {
            abort_if(!$staff->canAccessStudentForOperations($student), 403, 'This student is outside your access scope.');
        }
    }

    private function promotedBy(): string
    {
        // Check staff guard first — mirrors promotedByRole() so name and role always agree.
        // If both guards are somehow active simultaneously, they would mismatch otherwise.
        return Auth::guard('staff')->user()?->name ?? Auth::user()?->name ?? 'System';
    }

    private function promotedByRole(): string
    {
        return Auth::guard('staff')->check() ? 'staff' : 'institute';
    }

    private function semestersPerYear(Student $student): int
    {
        return $student->stream?->course?->effectiveSemestersPerYear() ?? 2;
    }

    private function partSemesterBounds(?CoursePart $part, Student $student): array
    {
        $spy        = $this->semestersPerYear($student);
        $yearNumber = max(1, (int) ($part?->year_number ?? 1));
        $start      = (($yearNumber - 1) * $spy) + 1;

        return [
            'start' => $start,
            'end'   => $yearNumber * $spy,
        ];
    }

    private function currentPartBounds(Student $student): array
    {
        return $this->partSemesterBounds($student->coursePart, $student);
    }

    private function coursePartsForStudent(Student $student)
    {
        if (!$student->stream?->course_id) {
            return collect();
        }

        return CoursePart::where('course_id', $student->stream->course_id)
            ->orderBy('year_number')
            ->orderBy('part_number')
            ->get();
    }

    private function nextPartForStudent(Student $student): ?CoursePart
    {
        return $this->targetPartForStudent($student, $this->nextSemester($student));
    }

    private function targetPartForStudent(Student $student, int $targetSemester): ?CoursePart
    {
        $parts = $this->coursePartsForStudent($student);

        if ($parts->isEmpty()) {
            return null;
        }

        $targetSemester   = max(1, $targetSemester);
        $spy              = $this->semestersPerYear($student);
        $targetYearNumber = (int) ceil($targetSemester / max(1, $spy));
        $structureType    = strtolower((string) ($student->stream?->course?->structure_type ?? ''));

        if ($structureType === 'semester' || $structureType === 'trimester') {
            $semesterPart = $parts->first(
                fn($part) => (int) $part->part_number === $targetSemester
            );

            if ($semesterPart) {
                return $semesterPart;
            }
        }

        return $parts->first(
            fn($part) => (int) $part->year_number === $targetYearNumber
        );
    }

    private function canSemesterPromote(Student $student): bool
    {
        // Short-term (modular) courses have no semester promotion
        if ($student->stream?->course?->isShortTerm()) {
            return false;
        }

        $bounds          = $this->currentPartBounds($student);
        $currentSemester = (int) $student->current_semester;

        return $currentSemester >= $bounds['start'] && $currentSemester < $bounds['end'];
    }

    private function canSessionPromote(Student $student): bool
    {
        // Short-term (modular) courses have no session promotion either
        if ($student->stream?->course?->isShortTerm()) {
            return false;
        }

        $bounds = $this->currentPartBounds($student);

        return (int) $student->current_semester === $bounds['end'];
    }

    private function canMarkComplete(Student $student): bool
    {
        return $student->stream?->course?->isShortTerm()
            && $student->status === 'active';
    }

    private function nextSemester(Student $student): int
    {
        return (int) $student->current_semester + 1;
    }

    private function checkNextPart(Student $student): array
    {
        $nextPart = $this->nextPartForStudent($student);
        $parts = $this->coursePartsForStudent($student);
        $bounds = $this->currentPartBounds($student);

        return [
            'can_semester_promote' => $this->canSemesterPromote($student),
            'can_session_promote'  => $this->canSessionPromote($student),
            'next_part'            => $nextPart,
            'is_last'              => $nextPart === null,
            'total_parts'          => $parts->count(),
            'current_part_end_sem' => $bounds['end'],
            'next_semester'        => $this->nextSemester($student),
        ];
    }

    private function subjectIdsForSession(Student $student, int $sessionId, ?int $yearNumber = null): array
    {
        $query = StudentSubject::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId);

        if ($yearNumber !== null) {
            $query->where('year_number', $yearNumber);
        }

        return $query->orderBy('subject_id')->pluck('subject_id')->unique()->values()->all();
    }

    private function snapshotIdentityPayload(Student $student, int $sessionId, ?int $yearNumber = null): array
    {
        return [
            'institute_id'                 => $student->institute_id,
            'course_id'                    => $student->stream?->course_id,
            'course_stream_id'             => $student->course_stream_id,
            'course_part_id'               => $student->course_part_id,
            'subjects_json'                => $this->subjectIdsForSession($student, $sessionId, $yearNumber),
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
            'gap_years'                    => $student->gap_year ? 1 : 0,
            'form_no'                      => $student->sr_no,
            'roll_no'                      => $student->roll_no,
        ];
    }

    private function snapshotIdentity(Student $student, int $sessionId, string $source): void
    {
        StudentAcademicIdentity::firstOrCreate(
            [
                'student_id'          => $student->id,
                'academic_session_id' => $sessionId,
                'source'              => $source,
                'semester_at_time'    => $student->current_semester,
            ],
            $this->snapshotIdentityPayload($student, $sessionId, $student->coursePart?->year_number)
        );
    }

    private function upsertCurrentIdentity(Student $student, int $sessionId, string $fallbackSource, array $extra = []): StudentAcademicIdentity
    {
        $existingSource = StudentAcademicIdentity::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('semester_at_time', $student->current_semester)
            ->realOnly()
            ->value('source');

        $source = $existingSource ?: $fallbackSource;

        return StudentAcademicIdentity::updateOrCreate(
            [
                'student_id'          => $student->id,
                'academic_session_id' => $sessionId,
                'source'              => $source,
                'semester_at_time'    => $student->current_semester,
            ],
            array_merge(
                $this->snapshotIdentityPayload($student, $sessionId, $student->coursePart?->year_number),
                $extra
            )
        );
    }

    private function carryForwardContext(Student $student, ?AcademicSession $fromSession): array
    {
        $yearLabel = $student->coursePart?->year_label
            ?? \App\Support\AcademicState::yearLabel(
                $student->stream?->course?->structure_type,
                (int) $student->current_semester,
                $student->coursePart?->year_number,
                $student->stream?->course?->effectiveSemestersPerYear() ?? 2
            );

        return [
            'from_session_id' => $fromSession?->id,
            'from_session_name' => $fromSession?->name,
            'from_course_part_id' => $student->course_part_id,
            'from_course_part_name' => $student->coursePart?->part_name,
            'from_year_label' => $yearLabel,
            'from_semester' => (int) $student->current_semester,
        ];
    }

    private function detailedPreviousDueDescription(Student $student, ?AcademicSession $fromSession): string
    {
        $context = $this->carryForwardContext($student, $fromSession);
        $parts = array_filter([
            $context['from_session_name'] ?? null,
            $context['from_year_label'] ?? null,
            !empty($context['from_semester']) ? 'Sem ' . $context['from_semester'] : null,
        ]);

        return 'Previous Due (' . implode(', ', $parts ?: ['Last Session']) . ')';
    }

    private function isChronologicallyAfter(AcademicSession $fromSession, AcademicSession $toSession): bool
    {
        if ($toSession->start_date && $fromSession->start_date) {
            return $toSession->start_date->gt($fromSession->start_date);
        }

        return (int) $toSession->id > (int) $fromSession->id;
    }

    private function resolveTargetSession(int $toSessionId, Student $student): AcademicSession
    {
        $fromSession = $student->session ?: AcademicSession::findOrFail($student->academic_session_id);
        $toSession = AcademicSession::where('id', $toSessionId)
            ->where('institute_id', $student->institute_id)
            ->firstOrFail();

        abort_if(
            !$this->isChronologicallyAfter($fromSession, $toSession),
            422,
            'Target session must be chronologically after the current session.'
        );

        $staff = $this->currentStaff();
        if ($staff) {
            abort_if(!$staff->canAccessAcademicSession((int) $toSession->id), 403, 'Target session is outside your access scope.');
        }

        return $toSession;
    }

    private function normalizeTerminalStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        return in_array($status, self::TERMINAL_STATUSES, true) ? $status : 'passed_out';
    }

    private function promotionLogStatusForTerminal(string $terminalStatus): string
    {
        return $terminalStatus === 'passed_out' ? 'completed' : $terminalStatus;
    }

    private function resolveBacklogSubjects(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return Subject::whereIn('id', $ids)
            ->where('institute_id', $this->instituteId())
            ->get(['id', 'name', 'code'])
            ->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code ?? ''])
            ->toArray();
    }

    // Bug 8: create a zero-amount audit ledger entry when a student exits with outstanding dues.
    // The wallet balance is unchanged (dues remain payable); this entry proves the balance
    // was reviewed at the time of the terminal outcome.
    private function recordTerminalDue(
        Student $student,
        int $sessionId,
        float $due,
        PromotionLog $log,
        string $terminalStatus
    ): void {
        if ($due <= 0) {
            return;
        }

        $wallet = StudentWallet::where('student_id', $student->id)
            ->where('institute_id', $student->institute_id)
            ->where('academic_session_id', $sessionId)
            ->first();

        $bal = $wallet ? (float) $wallet->main_b : 0.0;

        StudentTransaction::create([
            'student_id'          => $student->id,
            'institute_id'        => $student->institute_id,
            'academic_session_id' => $sessionId,
            'des'                 => sprintf(
                'Exit balance noted — Rs %.2f outstanding. Status: %s. Dues remain payable.',
                $due,
                ucwords(str_replace('_', ' ', $terminalStatus))
            ),
            'credit'              => 0.00,
            'debit'               => 0.00,
            'type'                => StudentTransaction::DEBIT,
            'date'                => now()->toDateString(),
            'op_bal'              => $bal,
            'cl_bal'              => $bal,
            'by_user_id'          => $this->currentStaff()?->id ?? auth()->id(),
        ] + ($this->hasPromotionLogColumn() ? ['promotion_log_id' => $log->id] : []));
    }

    private function applyStudentAccessScope($query)
    {
        $staff = $this->currentStaff();

        if (!$staff) {
            return $query;
        }

        if ($staff->hasRestrictedSessionAccess()) {
            $query->whereIn('academic_session_id', $staff->allowedSessionIds() ?: [-1]);
        }

        if ($staff->hasRestrictedCourseAccess()) {
            $query->whereHas('stream', fn($streamQuery) => $streamQuery->whereIn('course_id', $staff->allowedCourseIds() ?: [-1]));
        }

        return $query;
    }

    private function applyIdentityAccessScope($query)
    {
        $staff = $this->currentStaff();

        if (!$staff) {
            return $query;
        }

        if ($staff->hasRestrictedSessionAccess()) {
            $query->whereIn('academic_session_id', $staff->allowedSessionIds() ?: [-1]);
        }

        if ($staff->hasRestrictedCourseAccess()) {
            $query->whereIn('course_id', $staff->allowedCourseIds() ?: [-1]);
        }

        return $query;
    }

    private function applyPromotionLogAccessScope($query)
    {
        $staff = $this->currentStaff();

        if (!$staff) {
            return $query;
        }

        return $query->whereHas('student', function ($studentQuery) use ($staff) {
            if ($staff->hasRestrictedSessionAccess()) {
                $studentQuery->whereIn('academic_session_id', $staff->allowedSessionIds() ?: [-1]);
            }

            if ($staff->hasRestrictedCourseAccess()) {
                $studentQuery->whereHas('stream', fn($streamQuery) => $streamQuery->whereIn('course_id', $staff->allowedCourseIds() ?: [-1]));
            }
        });
    }

    private function getWalletDue(Student $student, int $sessionId): float
    {
        // Use buildPendingRows (fee rules + actual invoices) for accurate due calculation.
        // Raw wallet main_b can be 0 when admission fee debits weren't recorded.
        $pendingRows = WalletService::buildPendingRows($student, $sessionId);
        return (float) max(0, $pendingRows->sum('pending'));
    }

    private function createWalletTransaction(
        Student $student,
        int $sessionId,
        int $type,
        float $amount,
        string $description,
        ?int $promotionLogId = null
    ): void {
        if ($amount <= 0) {
            return;
        }

        $wallet = StudentWallet::firstOrCreate(
            ['student_id' => $student->id, 'academic_session_id' => $sessionId],
            ['institute_id' => $student->institute_id, 'main_b' => 0.00]
        );

        $openingBalance = (float) $wallet->main_b;
        $closingBalance = $type === StudentTransaction::DEBIT
            ? ($openingBalance - $amount)
            : ($openingBalance + $amount);

        StudentTransaction::create([
            'student_id'          => $student->id,
            'institute_id'        => $student->institute_id,
            'academic_session_id' => $sessionId,
            'des'                 => $description,
            'credit'              => $type === StudentTransaction::CREDIT ? $amount : 0.00,
            'debit'               => $type === StudentTransaction::DEBIT ? $amount : 0.00,
            'type'                => $type,
            'date'                => now()->toDateString(),
            'op_bal'              => $openingBalance,
            'cl_bal'              => $closingBalance,
            'by_user_id'          => auth()->id(),
        ] + ($this->hasPromotionLogColumn() ? [
            'promotion_log_id' => $promotionLogId,
        ] : []));

        $wallet->update(['main_b' => $closingBalance]);
    }

    private function carryForwardDue(
        Student $student,
        ?AcademicSession $fromSession,
        AcademicSession $toSession,
        float $due,
        PromotionLog $log
    ): void {
        if ($due <= 0) {
            return;
        }

        $this->createWalletTransaction(
            $student,
            $toSession->id,
            StudentTransaction::DEBIT,
            $due,
            $this->detailedPreviousDueDescription($student, $fromSession),
            $log->id
        );
    }

    private function syncSubjectsForTargetPart(Student $student, int $sessionId, CoursePart $targetPart): array
    {
        $mappings = CourseStreamSubject::where('course_stream_id', $student->course_stream_id)
            ->where('year_number', $targetPart->year_number)
            ->where('is_active', true)
            ->get();

        if ($mappings->isEmpty()) {
            throw new \RuntimeException("No subjects are mapped for {$targetPart->part_name}.");
        }

        $subjectIds = $mappings->pluck('subject_id')->unique()->values();

        StudentSubject::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('year_number', $targetPart->year_number)
            ->whereNotIn('subject_id', $subjectIds->all())
            ->delete();

        foreach ($mappings as $mapping) {
            StudentSubject::updateOrCreate(
                [
                    'student_id'          => $student->id,
                    'subject_id'          => $mapping->subject_id,
                    'academic_session_id' => $sessionId,
                    'year_number'         => $targetPart->year_number,
                ],
                [
                    'subject_role'     => $mapping->subject_role ?? 'compulsory',
                    'is_auto_included' => !$mapping->is_chooseable,
                ]
            );
        }

        return $subjectIds->all();
    }

    private function applyPromotionFee(Student $student, int $sessionId, int $semester, PromotionLog $log): void
    {
        $alreadyCharged = $this->hasPromotionLogColumn()
            ? StudentTransaction::where('promotion_log_id', $log->id)
                ->where('des', 'like', 'Promotion fee:%')
                ->exists()
            : StudentTransaction::where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)
                ->where('created_at', '>=', $log->created_at)
                ->where('des', 'like', $this->promotionFeeLikePattern($semester))
                ->exists();

        if ($alreadyCharged) {
            return;
        }

        $student->refresh();
        $student->load(['stream', 'coursePart']);

        $subjectIds = $this->subjectIdsForSession(
            $student,
            $sessionId,
            $student->coursePart?->year_number
        );

        // Semester promotion pe yearly fees (semester=0) already Sem 1 mein charge ho chuki hain
        // Session promotion pe naya saal shuru hota hai, isliye yearly fees dobara lagte hain
        $includeYearlyFees = $log->promotion_type === 'session';

        $feeData = FeeCalculatorService::calculate(
            instituteId:      $student->institute_id,
            sessionId:        $sessionId,
            courseId:         $student->stream?->course_id ?? 0,
            coursePart:       $student->coursePart?->year_number ?? 1,
            semester:         $semester,
            studentType:      $student->student_type ?? 'regular',
            admissionSource:  $student->admission_source ?? 'direct',
            category:         $student->category ?? 'general',
            gender:           $student->gender ?? 'other',
            subjectIds:       $subjectIds,
            courseStreamId:   $student->course_stream_id,
            coursePartId:     $student->course_part_id,
            includeYearlyFees: $includeYearlyFees
        );

        if (empty($feeData['items']) || ($feeData['total'] ?? 0) <= 0) {
            return;
        }

        foreach ($feeData['items'] as $item) {
            $amount = (float) ($item['amount'] ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $this->createWalletTransaction(
                $student,
                $sessionId,
                StudentTransaction::DEBIT,
                $amount,
                'Promotion fee: ' . $item['label'] . ' (Sem ' . $semester . ')',
                $log->id
            );
        }
    }

    private function shouldSyncStudentIdentityFields(StudentAcademicIdentity $identity): bool
    {
        $student = Student::select('id', 'academic_session_id', 'current_semester')
            ->find($identity->student_id);

        if (!$student) {
            return false;
        }

        if ((int) $student->academic_session_id !== (int) $identity->academic_session_id) {
            return false;
        }

        if ($identity->semester_at_time === null) {
            return true;
        }

        return (int) $student->current_semester === (int) $identity->semester_at_time;
    }

    private function ensureReverseSafety(PromotionLog $log): void
    {
        $hasLaterPromotion = PromotionLog::where('student_id', $log->student_id)
            ->where('id', '!=', $log->id)
            ->where('created_at', '>', $log->created_at)
            ->where('is_reversed', false)
            ->exists();

        abort_if(
            $hasLaterPromotion,
            422,
            'This student has later promotion records. Please reverse the most recent promotion first.'
        );

        $targetSessionId = $log->to_session_id ?? $log->from_session_id;

        if (!$targetSessionId) {
            return;
        }

        $hasExternalTransactions = StudentTransaction::where('student_id', $log->student_id)
            ->where('academic_session_id', $targetSessionId)
            ->where('created_at', '>', $log->created_at)
            ->where(function ($query) use ($log) {
                if ($this->hasPromotionLogColumn()) {
                    $query->whereNull('promotion_log_id')
                        ->orWhere('promotion_log_id', '!=', $log->id);

                    return;
                }

                $fromSession = $log->fromSession ?? AcademicSession::find($log->from_session_id);

                if ($log->promotion_type === 'semester') {
                    $query->where('des', 'not like', $this->promotionFeeLikePattern((int) $log->to_semester));

                    return;
                }

                $query->where('des', 'not like', $this->previousDueLikePattern())
                    ->where('des', 'not like', $this->promotionFeeLikePattern((int) $log->to_semester));
            })
            ->exists();

        abort_if(
            $hasExternalTransactions,
            422,
            'Fee or payment entries exist after this promotion. Please reverse or settle them first.'
        );
    }

    private function reversePromotionTransactions(Student $student, PromotionLog $originalLog, PromotionLog $reverseLog): void
    {
        $transactions = $this->hasPromotionLogColumn()
            ? StudentTransaction::where('promotion_log_id', $originalLog->id)
                ->orderBy('id')
                ->get()
            : $this->fallbackPromotionTransactionQuery($originalLog)
                ->orderBy('id')
                ->get();

        foreach ($transactions as $transaction) {
            $amount = (float) ($transaction->type === StudentTransaction::DEBIT
                ? $transaction->debit
                : $transaction->credit);

            if ($amount <= 0) {
                continue;
            }

            $this->createWalletTransaction(
                $student,
                $transaction->academic_session_id,
                $transaction->type === StudentTransaction::DEBIT
                    ? StudentTransaction::CREDIT
                    : StudentTransaction::DEBIT,
                $amount,
                'Promotion reversal: ' . $transaction->des,
                $reverseLog->id
            );
        }
    }

    private function getCurrentIdentityForState(int $studentId, ?int $sessionId, ?int $semester): ?StudentAcademicIdentity
    {
        if (!$sessionId || !$semester) {
            return null;
        }

        return StudentAcademicIdentity::where('student_id', $studentId)
            ->where('academic_session_id', $sessionId)
            ->where('semester_at_time', $semester)
            ->realOnly()
            ->orderBy('id')
            ->first();
    }

    public function getCourseParts(Request $request)
    {
        $parts = CoursePart::where('course_id', $request->course_id)
            ->orderBy('year_number')
            ->orderBy('part_number')
            ->get(['id', 'part_name', 'year_number', 'part_number'])
            ->map(fn($p) => [
                'id'         => $p->id,
                'part_name'  => $p->year_label,
                'year_number'=> $p->year_number,
                'part_number'=> $p->part_number,
            ]);

        return response()->json(['parts' => $parts]);
    }

    // ── Short-term Course Completion ─────────────────────────────────────
    // Used for modular/certificate courses that have no semester promotion.
    public function markComplete(Request $request, Student $student)
    {
        $this->ensurePromotionAccess();
        $this->ensureStaffCanAccessStudent($student);

        abort_unless($student->institute_id === $this->instituteId(), 403);
        abort_unless($student->stream?->course?->isShortTerm(), 422, 'Only short-term (modular) courses can be marked complete via this action.');
        abort_unless($student->status === 'active', 422, 'Student is not active.');

        $data = $request->validate([
            'completion_status' => ['required', 'in:passed_out,failed,dropped'],
            'remarks'           => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($student, $data) {
            // Re-fetch with row lock — student was bound outside the transaction,
            // so a concurrent request could have already changed status.
            $student = Student::where('id', $student->id)->lockForUpdate()->firstOrFail();
            abort_unless($student->status === 'active', 422, 'Student is no longer active.');

            $student->update([
                'status'  => $data['completion_status'],
            ]);

            PromotionLog::create([
                'institute_id'        => $student->institute_id,
                'student_id'          => $student->id,
                'promotion_type'      => 'session',
                'from_session_id'     => $student->academic_session_id,
                'from_course_part_id' => $student->course_part_id,
                'from_semester'       => $student->current_semester,
                'to_session_id'       => $student->academic_session_id,
                'to_course_part_id'   => $student->course_part_id,
                'to_semester'         => $student->current_semester,
                'dues_carried_forward'=> 0,
                'status'              => $data['completion_status'],
                'terminal_status'     => $data['completion_status'],
                'remarks'             => $data['remarks'] ?? 'Short-term course completed.',
                'promoted_by'         => $this->promotedBy(),
                'promoted_by_role'    => $this->promotedByRole(),
            ]);
        });

        return back()->with('success', "Student marked as {$data['completion_status']} successfully.");
    }

    // ── Re-admission ──────────────────────────────────────────────────────────
    // Reinstates a terminal student into an active session.

    public function readmitForm(Student $student)
    {
        $this->ensurePromotionAccess();
        $instituteId = $this->instituteId();

        abort_unless($student->institute_id === $instituteId, 403);
        abort_unless(
            in_array($student->status, self::TERMINAL_STATUSES, true),
            422,
            'Only terminal students (passed_out / backlog / failed / dropped) can be re-admitted.'
        );
        $this->ensureStaffCanAccessStudent($student);

        $student->load(['stream.course', 'coursePart', 'session']);

        $sessions    = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courseParts = $student->stream?->course_id
            ? CoursePart::where('course_id', $student->stream->course_id)
                ->orderBy('year_number')->orderBy('part_number')->get()
            : collect();

        $isStaff = $this->currentStaff() !== null;

        return view('institute.admission.promotions.readmit', compact(
            'student', 'sessions', 'courseParts', 'isStaff'
        ));
    }

    public function readmit(Request $request, Student $student)
    {
        $this->ensurePromotionAccess();
        $instituteId = $this->instituteId();

        abort_unless($student->institute_id === $instituteId, 403);
        abort_unless(
            in_array($student->status, self::TERMINAL_STATUSES, true),
            422,
            'Only terminal students can be re-admitted.'
        );
        $this->ensureStaffCanAccessStudent($student);

        $student->load('stream');
        $courseId = $student->stream?->course_id;

        $data = $request->validate([
            'to_session_id'   => ['required', 'integer',
                Rule::exists('academic_sessions', 'id')->where('institute_id', $instituteId)],
            'course_part_id'  => array_filter([
                'required', 'integer',
                $courseId
                    ? Rule::exists('course_parts', 'id')->where('course_id', $courseId)
                    : 'exists:course_parts,id',
            ]),
            'current_semester'=> ['required', 'integer', 'min:1', 'max:20'],
            'remarks'         => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($student, $data, $instituteId) {
            // Re-fetch with lock to prevent concurrent re-admissions
            $student = Student::where('id', $student->id)
                ->where('institute_id', $instituteId)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                in_array($student->status, self::TERMINAL_STATUSES, true),
                422,
                'Student is no longer in a terminal state.'
            );

            $toSession  = AcademicSession::where('id', $data['to_session_id'])
                ->where('institute_id', $instituteId)
                ->firstOrFail();
            $targetPart = CoursePart::findOrFail($data['course_part_id']);

            // Snapshot identity before re-admission
            $this->snapshotIdentity(
                $student,
                $student->academic_session_id ?? $data['to_session_id'],
                StudentAcademicIdentity::SOURCE_PRE_SESSION_PROMOTE
            );

            $log = PromotionLog::create([
                'institute_id'        => $instituteId,
                'student_id'          => $student->id,
                'promotion_type'      => 'readmission',
                'from_session_id'     => $student->academic_session_id,
                'from_course_part_id' => $student->course_part_id,
                'from_semester'       => $student->current_semester,
                'to_session_id'       => $toSession->id,
                'to_course_part_id'   => $targetPart->id,
                'to_semester'         => (int) $data['current_semester'],
                'dues_carried_forward'=> 0,
                'status'              => 'promoted',
                'remarks'             => trim(
                    'Re-admitted. Previous status: ' . $student->status . '. '
                    . ($data['remarks'] ?? '')
                ),
                'promoted_by'         => $this->promotedBy(),
                'promoted_by_role'    => $this->promotedByRole(),
            ]);

            $student->update([
                'status'              => 'active',
                'academic_session_id' => $toSession->id,
                'course_part_id'      => $targetPart->id,
                'current_semester'    => (int) $data['current_semester'],
            ]);

            $student->refresh();
            $student->load(['stream.course', 'coursePart', 'session']);

            $this->upsertCurrentIdentity(
                $student,
                $toSession->id,
                StudentAcademicIdentity::SOURCE_PROMOTION,
                ['remarks' => 'Re-admitted to ' . $toSession->name]
            );
        });

        return redirect()
            ->route($this->currentStaff() ? 'staff.admissions.promote.semester' : 'admissions.promote.semester')
            ->with('success', "{$student->name} has been re-admitted successfully.");
    }

    public function checkStudentStatus(Request $request)
    {
        $this->ensurePromotionAccess();
        $instituteId = $this->instituteId();
        $results = [];

        foreach ((array) ($request->student_ids ?? []) as $sid) {
            $student = Student::with(['stream.course', 'coursePart'])
                ->where('id', $sid)
                ->where('institute_id', $instituteId)
                ->first();

            if (!$student) {
                continue;
            }

            $this->ensureStaffCanAccessStudent($student);

            $check = $this->checkNextPart($student);

            $results[$sid] = [
                'name'                 => $student->name,
                'current_part'         => $student->coursePart?->part_name ?? '-',
                'current_sem'          => $student->current_semester,
                'can_semester_promote' => $check['can_semester_promote'],
                'can_session_promote'  => $check['can_session_promote'],
                'can_mark_complete'    => $this->canMarkComplete($student),
                'is_last'              => $check['is_last'],
                'next_part'            => $check['next_part']?->part_name,
                'next_semester'        => $check['next_semester'],
                'due'                  => $this->getWalletDue($student, $student->academic_session_id),
            ];
        }

        return response()->json($results);
    }

    public function semesterIndex(Request $request)
    {
        $this->ensurePromotionAccess();
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courses       = Course::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $sessionId     = $request->session_id ?? $activeSession?->id;
        $courseId      = $request->course_id;
        $fromSem       = $request->integer('from_semester', 0);

        $this->ensureStaffCanAccessSession($sessionId ? (int) $sessionId : null);
        $this->ensureStaffCanAccessCourse($courseId ? (int) $courseId : null);

        $query = Student::with(['stream.course', 'coursePart', 'session', 'wallets'])
            ->where('institute_id', $instituteId)
            ->where('status', 'active');
        $this->applyStudentAccessScope($query);

        // Only show students eligible for semester promotion:
        // — not at their year-end semester (those belong in Session Promotion)
        // — non-modular (short-term) courses use mark-complete instead
        $query
            ->whereNotNull('course_part_id')
            ->whereHas('stream.course', fn($cq) => $cq->where('structure_type', '!=', 'modular'))
            ->whereHas('coursePart', function ($cpq) {
                $cpq->whereRaw(
                    '`students`.`current_semester` < `course_parts`.`year_number` * ' .
                    '(SELECT CASE WHEN `c`.`semesters_per_year` > 0 THEN `c`.`semesters_per_year` ' .
                    "WHEN `c`.`structure_type` = 'yearly'    THEN 1 " .
                    "WHEN `c`.`structure_type` = 'trimester' THEN 3 " .
                    'ELSE 2 END ' .
                    'FROM `courses` `c` INNER JOIN `course_streams` `s` ON `s`.`course_id` = `c`.`id` ' .
                    'WHERE `s`.`id` = `students`.`course_stream_id` LIMIT 1)'
                );
            });

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }
        if ($courseId) {
            $query->whereHas('stream', fn($q) => $q->where('course_id', $courseId));
        }
        if ($fromSem) {
            $query->where('current_semester', $fromSem);
        }
        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('student_uid', 'like', "%{$s}%")
                ->orWhere('mobile', 'like', "%{$s}%"));
        }

        $students   = $query->orderBy('name')->paginate(50)->withQueryString();
        foreach ($students as $student) {
            $student->promotion_due = WalletService::getStudentSummary($student, (int) $student->academic_session_id)['total_due'];
        }
        $sessionObj = AcademicSession::find($sessionId);

        return view('institute.admission.promotions.semester', compact(
            'students',
            'sessions',
            'courses',
            'sessionObj',
            'sessionId',
            'activeSession',
            'courseId',
            'fromSem'
        ));
    }

    public function semesterPromote(Request $request)
    {
        $this->ensurePromotionAccess();
        $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
            'remarks'       => 'nullable|string|max:500',
        ]);

        $instituteId = $this->instituteId();
        $promoted = 0;
        $errors = [];

        foreach ($request->student_ids as $sid) {
            try {
                DB::transaction(function () use ($sid, $instituteId, $request, &$promoted) {
                    // lockForUpdate prevents concurrent promotions of the same student
                    // (double-click / two staff tabs) from both reading the same semester
                    // and advancing it twice.
                    $student = Student::with(['stream.course', 'coursePart', 'session'])
                        ->where('id', $sid)
                        ->where('institute_id', $instituteId)
                        ->where('status', 'active')
                        ->lockForUpdate()
                        ->first();

                    if (!$student) {
                        throw new \RuntimeException("Student #{$sid} not found.");
                    }

                    $this->ensureStaffCanAccessStudent($student);

                    if (!$student->coursePart) {
                        throw new \RuntimeException("{$student->name}: current year/part is not set.");
                    }

                    if (!$this->canSemesterPromote($student)) {
                        throw new \RuntimeException("{$student->name} is in the year-end semester. Please use Session Promotion instead.");
                    }

                    $sessionId = (int) $student->academic_session_id;
                    $due = $this->getWalletDue($student, $sessionId);
                    $toSemester = $this->nextSemester($student);
                    $targetPart = $this->targetPartForStudent($student, $toSemester) ?? $student->coursePart;

                    $this->snapshotIdentity($student, $sessionId, StudentAcademicIdentity::SOURCE_PRE_SEM_PROMOTE);

                    $log = PromotionLog::create([
                        'institute_id'         => $instituteId,
                        'student_id'           => $student->id,
                        'promotion_type'       => 'semester',
                        'from_session_id'      => $sessionId,
                        'from_course_part_id'  => $student->course_part_id,
                        'from_semester'        => $student->current_semester,
                        'to_session_id'        => $sessionId,
                        'to_course_part_id'    => $targetPart?->id ?? $student->course_part_id,
                        'to_semester'          => $toSemester,
                        'dues_carried_forward' => $due,
                        'status'               => 'promoted',
                        'remarks'              => $request->remarks,
                        'promoted_by'          => $this->promotedBy(),
                        'promoted_by_role'     => $this->promotedByRole(),
                    ]);

                    $student->update([
                        'course_part_id'   => $targetPart?->id ?? $student->course_part_id,
                        'current_semester' => $toSemester,
                    ]);

                    $student->refresh();
                    $student->load(['stream.course', 'coursePart', 'session']);

                    $this->upsertCurrentIdentity($student, $sessionId, StudentAcademicIdentity::SOURCE_PROMOTION);
                    $this->applyPromotionFee($student, $sessionId, $toSemester, $log);

                    $promoted++;
                });
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
                Log::warning("Semester promotion failed for student {$sid}: " . $e->getMessage());
            }
        }

        $msg = '';
        if ($promoted) {
            $msg .= "{$promoted} student(s) promoted to the next semester within the same session. ";
        }
        if ($errors) {
            $msg .= 'Errors: ' . implode(', ', $errors);
        }

        return back()->with($promoted ? 'success' : 'warning', trim($msg) ?: 'No students were promoted.');
    }

    public function sessionIndex(Request $request)
    {
        $this->ensurePromotionAccess();
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courses       = Course::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $fromSessionId = $request->from_session_id ?? $activeSession?->id;
        $toSessionId   = $request->to_session_id;
        $courseId      = $request->course_id;

        $this->ensureStaffCanAccessSession($fromSessionId ? (int) $fromSessionId : null);
        $this->ensureStaffCanAccessSession($toSessionId ? (int) $toSessionId : null);
        $this->ensureStaffCanAccessCourse($courseId ? (int) $courseId : null);

        $query = Student::with(['stream.course', 'coursePart', 'session', 'wallets'])
            ->where('institute_id', $instituteId)
            ->where('status', 'active');
        $this->applyStudentAccessScope($query);

        // Only show students eligible for session promotion:
        // — at their year-end semester (current_semester == year_number * effective_spy)
        // — non-modular (short-term) courses use mark-complete instead
        $query
            ->whereNotNull('course_part_id')
            ->whereHas('stream.course', fn($cq) => $cq->where('structure_type', '!=', 'modular'))
            ->whereHas('coursePart', function ($cpq) {
                $cpq->whereRaw(
                    '`students`.`current_semester` = `course_parts`.`year_number` * ' .
                    '(SELECT CASE WHEN `c`.`semesters_per_year` > 0 THEN `c`.`semesters_per_year` ' .
                    "WHEN `c`.`structure_type` = 'yearly'    THEN 1 " .
                    "WHEN `c`.`structure_type` = 'trimester' THEN 3 " .
                    'ELSE 2 END ' .
                    'FROM `courses` `c` INNER JOIN `course_streams` `s` ON `s`.`course_id` = `c`.`id` ' .
                    'WHERE `s`.`id` = `students`.`course_stream_id` LIMIT 1)'
                );
            });

        if ($fromSessionId) {
            $query->where('academic_session_id', $fromSessionId);
        }
        if ($courseId) {
            $query->whereHas('stream', fn($q) => $q->where('course_id', $courseId));
        }
        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('student_uid', 'like', "%{$s}%")
                ->orWhere('mobile', 'like', "%{$s}%"));
        }

        $students = $query->orderBy('name')->paginate(50)->withQueryString();
        foreach ($students as $student) {
            $student->promotion_due = WalletService::getStudentSummary($student, (int) $student->academic_session_id)['total_due'];
        }

        // Preload subjects by course_part for the backlog subject picker in the view
        $partIds = $students->pluck('course_part_id')->unique()->filter()->values()->toArray();
        $subjectsByPart = [];
        if ($partIds) {
            $subjectsByPart = Subject::join('course_part_subject', 'course_part_subject.subject_id', '=', 'subjects.id')
                ->whereIn('course_part_subject.course_part_id', $partIds)
                ->select('subjects.id', 'subjects.name', 'subjects.code', 'course_part_subject.course_part_id')
                ->orderBy('subjects.name')
                ->get()
                ->groupBy('course_part_id')
                ->map(fn($g) => $g->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code ?? ''])->values())
                ->toArray();
        }

        $fromSessionObj = AcademicSession::find($fromSessionId);
        $toSessionObj   = AcademicSession::find($toSessionId);

        return view('institute.admission.promotions.session', compact(
            'students',
            'sessions',
            'courses',
            'fromSessionObj',
            'toSessionObj',
            'fromSessionId',
            'toSessionId',
            'activeSession',
            'courseId',
            'subjectsByPart'
        ));
    }

    public function sessionPromote(Request $request)
    {
        $this->ensurePromotionAccess();
        $instituteId = $this->instituteId();
        $request->validate([
            'student_ids'          => 'required|array|min:1',
            'student_ids.*'        => 'integer|exists:students,id',
            'to_session_id'        => ['nullable', 'integer',
                Rule::exists('academic_sessions', 'id')->where('institute_id', $instituteId)],
            'completion_status'    => 'nullable|in:passed_out,backlog,failed,dropped',
            'backlog_subject_ids'  => 'nullable|array',
            'backlog_subject_ids.*'=> ['integer',
                Rule::exists('subjects', 'id')->where('institute_id', $instituteId)],
            'remarks'              => 'nullable|string|max:500',
        ]);

        $promoted = 0;
        $completed = 0;
        $errors = [];

        foreach ($request->student_ids as $sid) {
            try {
                $result = DB::transaction(function () use ($sid, $instituteId, $request) {
                    // lockForUpdate prevents concurrent session-promotions of the same
                    // student from both reading status='active' and double-promoting.
                    $student = Student::with(['stream.course', 'coursePart', 'session'])
                        ->where('id', $sid)
                        ->where('institute_id', $instituteId)
                        ->where('status', 'active')
                        ->lockForUpdate()
                        ->first();

                    if (!$student) {
                        throw new \RuntimeException("Student #{$sid} not found.");
                    }

                    $this->ensureStaffCanAccessStudent($student);

                    if (!$student->coursePart) {
                        throw new \RuntimeException("{$student->name}: current year/part is not set.");
                    }

                    if (!$this->canSessionPromote($student)) {
                        throw new \RuntimeException("{$student->name} is not yet in the year-end semester. Please complete Semester Promotion first.");
                    }

                    $check = $this->checkNextPart($student);
                    $due = $this->getWalletDue($student, $student->academic_session_id);

                    $this->snapshotIdentity(
                        $student,
                        $student->academic_session_id,
                        StudentAcademicIdentity::SOURCE_PRE_SESSION_PROMOTE
                    );

                    if ($check['is_last']) {
                        // ── FINAL YEAR: apply terminal outcome ───────────────────────
                        $terminalStatus = $this->normalizeTerminalStatus($request->input('completion_status'));
                        $log = PromotionLog::create([
                            'institute_id'         => $instituteId,
                            'student_id'           => $student->id,
                            'promotion_type'       => 'session',
                            'from_session_id'      => $student->academic_session_id,
                            'from_course_part_id'  => $student->course_part_id,
                            'from_semester'        => $student->current_semester,
                            'to_session_id'        => null,
                            'to_course_part_id'    => null,
                            'to_semester'          => null,
                            'dues_carried_forward' => $due,
                            'carry_forward_context' => $this->carryForwardContext($student, $student->session),
                            'status'               => $this->promotionLogStatusForTerminal($terminalStatus),
                            'terminal_status'      => $terminalStatus,
                            'remarks'              => trim(ucwords(str_replace('_', ' ', $terminalStatus)) . '. ' . ($request->remarks ?? '')),
                            'promoted_by'          => $this->promotedBy(),
                            'promoted_by_role'     => $this->promotedByRole(),
                        ]);

                        // Backlog subject tracking for final-year backlog outcome
                        if ($terminalStatus === 'backlog') {
                            $subjects = $this->resolveBacklogSubjects($request->input('backlog_subject_ids', []));
                            if ($subjects) {
                                $log->update(['backlog_subjects' => $subjects]);
                            }
                        }

                        // Bug 8: audit ledger entry for outstanding dues at exit
                        $this->recordTerminalDue($student, $student->academic_session_id, $due, $log, $terminalStatus);

                        $this->upsertCurrentIdentity(
                            $student,
                            $student->academic_session_id,
                            StudentAcademicIdentity::SOURCE_PROMOTION,
                            ['remarks' => 'Final outcome: ' . ucwords(str_replace('_', ' ', $terminalStatus))]
                        );

                        $student->update(['status' => $terminalStatus]);

                        return 'completed';
                    }

                    // ── NON-FINAL YEAR: branch on outcome ────────────────────────────
                    $completionStatus = $request->input('completion_status');

                    // DROPPED: student leaves — no session move needed
                    if ($completionStatus === 'dropped') {
                        $droppedLog = PromotionLog::create([
                            'institute_id'         => $instituteId,
                            'student_id'           => $student->id,
                            'promotion_type'       => 'session',
                            'from_session_id'      => $student->academic_session_id,
                            'from_course_part_id'  => $student->course_part_id,
                            'from_semester'        => $student->current_semester,
                            'to_session_id'        => null,
                            'to_course_part_id'    => null,
                            'to_semester'          => null,
                            'dues_carried_forward' => $due,
                            'carry_forward_context' => $this->carryForwardContext($student, $student->session),
                            'status'               => 'dropped',
                            'terminal_status'      => 'dropped',
                            'remarks'              => trim('Dropped. ' . ($request->remarks ?? '')),
                            'promoted_by'          => $this->promotedBy(),
                            'promoted_by_role'     => $this->promotedByRole(),
                        ]);
                        // Bug 8: audit ledger entry for outstanding dues at exit
                        $this->recordTerminalDue($student, $student->academic_session_id, $due, $droppedLog, 'dropped');
                        $student->update(['status' => 'dropped']);
                        return 'completed';
                    }

                    // FAILED: year-back — same year/part, move to new session only
                    if ($completionStatus === 'failed') {
                        if (!$request->filled('to_session_id')) {
                            throw new \RuntimeException("Select a target session for year-back of {$student->name}.");
                        }
                        $toSession   = $this->resolveTargetSession((int) $request->to_session_id, $student);
                        if ((int) $student->academic_session_id === (int) $toSession->id) {
                            throw new \RuntimeException("{$student->name} is already in the {$toSession->name} session.");
                        }
                        $bounds      = $this->currentPartBounds($student);
                        $fromSession = $student->session ?: AcademicSession::find($student->academic_session_id);

                        $log = PromotionLog::create([
                            'institute_id'         => $instituteId,
                            'student_id'           => $student->id,
                            'promotion_type'       => 'session',
                            'from_session_id'      => $student->academic_session_id,
                            'from_course_part_id'  => $student->course_part_id,
                            'from_semester'        => $student->current_semester,
                            'to_session_id'        => $toSession->id,
                            'to_course_part_id'    => $student->course_part_id,  // same part (year-back)
                            'to_semester'          => $bounds['start'],           // reset to year start
                            'dues_carried_forward' => $due,
                            'carry_forward_context' => $this->carryForwardContext($student, $fromSession),
                            'status'               => 'failed',
                            'remarks'              => trim('Year-back. ' . ($request->remarks ?? '')),
                            'promoted_by'          => $this->promotedBy(),
                            'promoted_by_role'     => $this->promotedByRole(),
                        ]);

                        $this->carryForwardDue($student, $fromSession, $toSession, $due, $log);
                        $student->update([
                            'academic_session_id' => $toSession->id,
                            'current_semester'    => $bounds['start'],
                            // course_part_id unchanged — student repeats the same year
                        ]);
                        $student->refresh();
                        $student->load(['stream.course', 'coursePart', 'session']);
                        $this->upsertCurrentIdentity($student, $toSession->id,
                            StudentAcademicIdentity::SOURCE_SESSION_PROMOTION,
                            ['remarks' => 'Year-back from ' . ($fromSession?->name ?? 'Previous Session')]);
                        $this->applyPromotionFee($student, $toSession->id, $bounds['start'], $log);
                        return 'promoted';
                    }

                    // DEFAULT / BACKLOG: promote to next year in new session
                    if (!$request->filled('to_session_id')) {
                        throw new \RuntimeException("Please select a 'To Session' for {$student->name}.");
                    }

                    $toSession = $this->resolveTargetSession((int) $request->to_session_id, $student);

                    if ((int) $student->academic_session_id === (int) $toSession->id) {
                        throw new \RuntimeException("{$student->name} is already in the {$toSession->name} session.");
                    }

                    $toSemester  = $this->nextSemester($student);
                    $nextPart    = $this->targetPartForStudent($student, $toSemester);
                    $fromSession = $student->session ?: AcademicSession::find($student->academic_session_id);
                    $isBacklog   = $completionStatus === 'backlog';

                    if (!$nextPart) {
                        throw new \RuntimeException("Target part for Semester {$toSemester} is missing for {$student->name}.");
                    }

                    $log = PromotionLog::create([
                        'institute_id'         => $instituteId,
                        'student_id'           => $student->id,
                        'promotion_type'       => 'session',
                        'from_session_id'      => $student->academic_session_id,
                        'from_course_part_id'  => $student->course_part_id,
                        'from_semester'        => $student->current_semester,
                        'to_session_id'        => $toSession->id,
                        'to_course_part_id'    => $nextPart->id,
                        'to_semester'          => $toSemester,
                        'dues_carried_forward' => $due,
                        'carry_forward_context' => $this->carryForwardContext($student, $fromSession),
                        'status'               => $isBacklog ? 'backlog' : 'promoted',
                        'remarks'              => $request->remarks,
                        'promoted_by'          => $this->promotedBy(),
                        'promoted_by_role'     => $this->promotedByRole(),
                    ]);

                    // Backlog subject tracking for non-final-year backlog outcome
                    if ($isBacklog) {
                        $subjects = $this->resolveBacklogSubjects($request->input('backlog_subject_ids', []));
                        if ($subjects) {
                            $log->update(['backlog_subjects' => $subjects]);
                        }
                    }

                    $this->syncSubjectsForTargetPart($student, $toSession->id, $nextPart);
                    $this->carryForwardDue($student, $fromSession, $toSession, $due, $log);

                    $student->update([
                        'academic_session_id' => $toSession->id,
                        'course_part_id'      => $nextPart->id,
                        'current_semester'    => $toSemester,
                        'status'              => 'active', // backlog students stay active; they carry subjects to next year
                    ]);

                    $student->refresh();
                    $student->load(['stream.course', 'coursePart', 'session']);

                    $this->upsertCurrentIdentity(
                        $student,
                        $toSession->id,
                        StudentAcademicIdentity::SOURCE_SESSION_PROMOTION,
                        ['remarks' => ($isBacklog ? 'Promoted with backlog from ' : 'Promoted from ')
                            . ($fromSession?->name ?? 'Previous Session')]
                    );

                    $this->applyPromotionFee($student, $toSession->id, $toSemester, $log);

                    return 'promoted';
                });

                if ($result === 'completed') {
                    $completed++;
                } else {
                    $promoted++;
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
                Log::warning("Session promotion failed for student {$sid}: " . $e->getMessage());
            }
        }

        $msg = '';
        $messageTargetSession = $request->filled('to_session_id')
            ? AcademicSession::where('institute_id', $instituteId)->find($request->to_session_id)?->name
            : null;
        if ($promoted) {
            $msg .= "{$promoted} student(s) promoted to " . ($messageTargetSession ?: 'the selected session') . ". ";
        }
        if ($completed) {
            $msg .= "{$completed} student(s) final outcome updated. ";
        }
        if ($errors) {
            $msg .= 'Errors: ' . implode(', ', $errors);
        }

        return back()->with(($promoted || $completed) ? 'success' : 'warning', trim($msg) ?: 'No changes were made.');
    }

    public function reversePromotion(Request $request, PromotionLog $log)
    {
        $this->ensurePromotionAccess();
        $instituteId = $this->instituteId();
        abort_if($log->institute_id !== $instituteId, 403);
        abort_if($log->is_reversed, 422, 'This promotion has already been reversed.');

        $this->ensureReverseSafety($log);

        $student = Student::findOrFail($log->student_id);
        $this->ensureStaffCanAccessStudent($student);

        DB::transaction(function () use ($log, $student, $request) {
            $previousIdentity = $this->getCurrentIdentityForState(
                $student->id,
                $log->from_session_id,
                $log->from_semester
            );

            $student->update([
                'academic_session_id' => $log->from_session_id,
                'course_part_id'      => $log->from_course_part_id,
                'current_semester'    => $log->from_semester,
                'status'              => $previousIdentity?->student_status_snapshot ?? 'active',
                'sr_no'               => $previousIdentity?->form_no ?? $student->sr_no,
                'enrollment_no'       => $previousIdentity?->enrollment_no_snapshot ?? $student->enrollment_no,
                'roll_no'             => $previousIdentity?->roll_no ?? $student->roll_no,
                'institute_form_no'   => $previousIdentity?->institute_form_no_snapshot ?? $student->institute_form_no,
                'exam_form_no'        => $previousIdentity?->exam_form_no_snapshot ?? $student->exam_form_no,
                'uin_no'              => $previousIdentity?->uin_no_snapshot ?? $student->uin_no,
                'reference_no'        => $previousIdentity?->reference_no_snapshot ?? $student->reference_no,
                'admission_source'    => $previousIdentity?->admission_source_snapshot ?? $student->admission_source,
                'admission_source_id' => $previousIdentity?->admission_source_id_snapshot ?? $student->admission_source_id,
                'submitted_date'      => $previousIdentity?->submitted_date_snapshot ?? $student->submitted_date,
                'admission_date'      => $previousIdentity?->admission_date_snapshot ?? $student->admission_date,
            ]);

            if (
                in_array($log->promotion_type, ['session', 'readmission'], true)
                && $log->to_session_id !== $log->from_session_id
                && $log->to_session_id
            ) {
                if ($log->promotion_type === 'session') {
                    StudentSubject::where('student_id', $student->id)
                        ->where('academic_session_id', $log->to_session_id)
                        ->delete();
                }

                StudentAcademicIdentity::where('student_id', $student->id)
                    ->where('academic_session_id', $log->to_session_id)
                    ->whereIn('source', [
                        StudentAcademicIdentity::SOURCE_SESSION_PROMOTION,
                        StudentAcademicIdentity::SOURCE_PROMOTION,
                    ])
                    ->delete();
            }

            if ($log->promotion_type === 'semester') {
                StudentAcademicIdentity::where('student_id', $student->id)
                    ->where('academic_session_id', $log->to_session_id)
                    ->where('semester_at_time', $log->to_semester)
                    ->where('source', StudentAcademicIdentity::SOURCE_PROMOTION)
                    ->delete();
            }

            $reverseLog = PromotionLog::create([
                'institute_id'        => $log->institute_id,
                'student_id'          => $log->student_id,
                'promotion_type'      => $log->promotion_type,
                'from_session_id'     => $log->to_session_id,
                'from_course_part_id' => $log->to_course_part_id,
                'from_semester'       => $log->to_semester,
                'to_session_id'       => $log->from_session_id,
                'to_course_part_id'   => $log->from_course_part_id,
                'to_semester'         => $log->from_semester,
                'status'              => 'reversed',
                'terminal_status'     => $log->terminal_status,
                'remarks'             => 'Reversal of log #' . $log->id . '. Reason: ' . ($request->reason ?? 'Not given'),
                'promoted_by'         => $this->promotedBy(),
                'promoted_by_role'    => $this->promotedByRole(),
            ]);

            $this->reversePromotionTransactions($student, $log, $reverseLog);

            $log->update([
                'is_reversed'        => true,
                'reversed_by_log_id' => $reverseLog->id,
                'reversed_at'        => now(),
                'reversed_by'        => $this->promotedBy(),
            ]);
        });

        return back()->with('success', "Promotion reversed successfully. {$student->name} has been restored to the previous state.");
    }

    public function identityIndex(Request $request)
    {
        $this->ensurePromotionAccess();
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courses       = Course::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $courseParts   = CoursePart::with('course')
            ->whereHas('course', fn($q) => $q->where('institute_id', $instituteId))
            ->orderBy('course_id')
            ->orderBy('year_number')
            ->get();
        $sessionId     = $request->session_id ?? $activeSession?->id;

        $this->ensureStaffCanAccessSession($sessionId ? (int) $sessionId : null);
        $this->ensureStaffCanAccessCourse($request->filled('course_id') ? (int) $request->course_id : null);

        $query = StudentAcademicIdentity::with(['student.stream.course', 'student.coursePart', 'session', 'course', 'coursePart'])
            ->where('institute_id', $instituteId)
            ->realOnly();
        $this->applyIdentityAccessScope($query);

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }
        if ($request->course_id) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->course_part_id) {
            $query->where('course_part_id', $request->course_part_id);
        }
        if ($request->current_semester) {
            $query->where('semester_at_time', $request->current_semester);
        }
        if ($request->pending === 'roll') {
            $query->whereNull('roll_no');
        }
        if ($request->pending === 'form') {
            $query->whereNull('form_no');
        }
        if ($request->pending === 'both') {
            $query->whereNull('roll_no')->whereNull('form_no');
        }
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('student', fn($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('student_uid', 'like', "%{$s}%"));
        }

        $identities = $query->orderBy('academic_session_id')
            ->orderBy('semester_at_time')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        $pendingCountQuery = StudentAcademicIdentity::where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)
            ->realOnly()
            ->where(fn($q) => $q->whereNull('roll_no')->orWhereNull('form_no'));
        $this->applyIdentityAccessScope($pendingCountQuery);
        $pendingCount = $pendingCountQuery->count();

        return view('institute.admission.promotions.identity', compact(
            'identities',
            'sessions',
            'courses',
            'courseParts',
            'sessionId',
            'activeSession',
            'pendingCount'
        ));
    }

    public function bulkCorrectionIndex(Request $request)
    {
        $extra = [];
        $token = $request->query('upload_token');
        if ($token) {
            $upload = $this->readBulkCorrectionUpload($token);
            if ($upload && (int) ($upload['institute_id'] ?? 0) === $this->instituteId()) {
                $extra['mappingUpload'] = $this->buildBulkMappingUpload(
                    $token,
                    $upload['headers'] ?? [],
                    $upload['rows'] ?? []
                );
            }
        }

        return view('institute.admission.bulk-correction', $this->bulkCorrectionPageData($request, $extra));
    }

    public function bulkCorrectionTemplate(Request $request)
    {
        return $this->identityTemplate($request);
    }

    public function bulkCorrectionUpload(Request $request)
    {
        $request->validate([
            'bulk_file' => 'required|file|mimes:csv,txt,xlsx',
            'session_id' => 'nullable|integer',
            'course_id' => 'nullable|integer',
            'course_part_id' => 'nullable|integer',
            'current_semester' => 'nullable|integer|min:1|max:20',
        ]);

        try {
            $rows = SimpleSpreadsheet::read(
                $request->file('bulk_file')->getRealPath(),
                $request->file('bulk_file')->getClientOriginalExtension()
            );
        } catch (\Throwable $exception) {
            return back()->with('warning', $exception->getMessage());
        }

        if (count($rows) < 2) {
            return back()->with('warning', 'Uploaded file is empty or has no student rows.');
        }

        $headerRow = array_map(fn($heading) => trim((string) $heading), array_shift($rows));
        $token = $this->storeBulkCorrectionUpload([
            'institute_id' => $this->instituteId(),
            'context' => [
                'session_id' => $request->input('session_id'),
                'course_id' => $request->input('course_id'),
                'course_part_id' => $request->input('course_part_id'),
                'current_semester' => $request->input('current_semester'),
            ],
            'headers' => $headerRow,
            'rows' => $rows,
            'original_name' => $request->file('bulk_file')->getClientOriginalName(),
        ]);

        return redirect()->route('admissions.bulk-correction', array_filter([
            'session_id' => $request->input('session_id'),
            'course_id' => $request->input('course_id'),
            'course_part_id' => $request->input('course_part_id'),
            'current_semester' => $request->input('current_semester'),
            'upload_token' => $token,
        ]));
    }

    public function bulkCorrectionApply(Request $request)
    {
        $request->validate([
            'upload_token' => 'required|string',
            'identity_column' => 'required|string',
            'field_map' => 'nullable|array',
            'field_map.*' => 'nullable|string',
        ]);

        $upload = $this->readBulkCorrectionUpload($request->input('upload_token'));
        if (!$upload || (int) ($upload['institute_id'] ?? 0) !== $this->instituteId()) {
            return redirect()->route('admissions.bulk-correction')->with('warning', 'Uploaded file session expired. Please upload the file again.');
        }

        $headers = $upload['headers'] ?? [];
        $rows = $upload['rows'] ?? [];
        $context = $upload['context'] ?? [];
        $identityColumn = trim((string) $request->input('identity_column'));
        $fieldMap = array_filter((array) $request->input('field_map', []), fn($value) => trim((string) $value) !== '');

        if (!in_array($identityColumn, $headers, true)) {
            return redirect()->route('admissions.bulk-correction', array_filter($context + ['upload_token' => $request->input('upload_token')]))
                ->with('warning', 'Selected UIN column was not found in uploaded file.');
        }

        $duplicateColumns = collect($fieldMap)->duplicates()->filter();
        if ($duplicateColumns->isNotEmpty()) {
            return redirect()->route('admissions.bulk-correction', array_filter($context + ['upload_token' => $request->input('upload_token')]))
                ->with('warning', 'Same Excel column cannot be mapped to multiple software fields.');
        }

        if (empty($fieldMap)) {
            return redirect()->route('admissions.bulk-correction', array_filter($context + ['upload_token' => $request->input('upload_token')]))
                ->with('warning', 'Please map at least one software field to an Excel column.');
        }

        $instituteId = $this->instituteId();
        $fields = collect($this->bulkCorrectionFields($instituteId))->keyBy('key')->all();
        $headerIndexMap = [];
        foreach ($headers as $index => $header) {
            $headerIndexMap[$header] = $index;
        }

        $totalRows = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $successRows = [];

        $contextRequest = new Request(array_filter($context, fn($value) => $value !== null && $value !== ''));

        foreach ($rows as $offset => $row) {
            if (count(array_filter($row, fn($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $rowNumber = $offset + 2;
            $totalRows++;
            $uin = trim((string) ($row[$headerIndexMap[$identityColumn] ?? null] ?? ''));

            if ($uin === '') {
                $errors[] = ['row' => $rowNumber, 'uin' => '', 'student' => '', 'error' => 'Selected UIN column value is required.'];
                $skipped++;
                continue;
            }

            $student = $this->bulkCorrectionStudentQuery($contextRequest, $instituteId)
                ->where('uin_no', $uin)
                ->with('educationDetails')
                ->first();

            if (!$student) {
                $errors[] = ['row' => $rowNumber, 'uin' => $uin, 'student' => '', 'error' => 'Student not found in selected session/course/semester context.'];
                $skipped++;
                continue;
            }

            $mappedData = ['uin_no' => $uin];
            foreach ($fieldMap as $fieldKey => $excelColumn) {
                if (!isset($headerIndexMap[$excelColumn], $fields[$fieldKey])) {
                    continue;
                }
                $mappedData[$fieldKey] = trim((string) ($row[$headerIndexMap[$excelColumn]] ?? ''));
            }

            [$studentUpdate, $educationUpdate, $rowErrors] = $this->validateBulkCorrectionRow($mappedData, $fields, $student);

            if ($rowErrors) {
                foreach ($rowErrors as $error) {
                    $errors[] = ['row' => $rowNumber, 'uin' => $uin, 'student' => $student->name, 'error' => $error];
                }
                $skipped++;
                continue;
            }

            if (!$studentUpdate && !$educationUpdate) {
                $errors[] = ['row' => $rowNumber, 'uin' => $uin, 'student' => $student->name, 'error' => 'No mapped values found for update.'];
                $skipped++;
                continue;
            }

            $academicFields = ['course_stream_id', 'course_part_id', 'current_semester'];
            $isAcademicChange = !empty(array_intersect($academicFields, array_keys($studentUpdate)));

            if ($isAcademicChange) {
                $student->loadMissing(['stream.course', 'coursePart']);
            }
            $oldSnapshot = $isAcademicChange
                ? \App\Services\StudentAcademicChangeService::buildSnapshot($student)
                : null;

            DB::transaction(function () use ($student, $studentUpdate, $educationUpdate, $isAcademicChange, $oldSnapshot) {
                if ($studentUpdate) {
                    $student->update($studentUpdate);
                }

                foreach ($educationUpdate as $examName => $values) {
                    if (!collect($values)->contains(fn($value) => filled($value))) {
                        continue;
                    }

                    StudentEducationDetail::updateOrCreate(
                        ['student_id' => $student->id, 'exam_name' => $examName],
                        $values + ['exam_name' => $examName]
                    );
                }

                if ($isAcademicChange && $oldSnapshot) {
                    $student->refresh()->load(['stream.course', 'coursePart']);
                    $sessionId   = (int) $student->academic_session_id;
                    $subjectIds  = \App\Services\StudentAcademicChangeService::currentSubjectIds($student, $sessionId);
                    $newSnapshot = \App\Services\StudentAcademicChangeService::buildSnapshot($student);
                    $adjustment  = \App\Services\StudentAcademicChangeService::applyFeeDelta(
                        $student, $oldSnapshot, $newSnapshot,
                        'Academic data corrected via bulk update'
                    );
                    \App\Services\StudentAcademicChangeService::syncCurrentIdentity($student, $subjectIds);
                    \App\Services\StudentAcademicChangeService::createChangeLog(
                        $student, $oldSnapshot, $newSnapshot, $adjustment,
                        'web', auth()->user()?->name ?? 'Bulk Correction'
                    );
                }
            });

            $successRows[] = ['row' => $rowNumber, 'uin' => $uin, 'student' => $student->name];
            $updated++;
        }

        $this->deleteBulkCorrectionUpload($request->input('upload_token'));

        $report = [
            'total_rows' => $totalRows,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'success_rows' => $successRows,
        ];

        $message = "{$updated} student records updated. {$skipped} rows failed.";
        return redirect()->route('admissions.bulk-correction', array_filter($context))
            ->with($errors ? 'warning' : 'success', $message)
            ->with('bulkReport', $report);
    }

    public function identityTemplate(Request $request)
    {
        $instituteId = $this->instituteId();
        $fields = $this->bulkCorrectionFields($instituteId);
        $headers = array_column($fields, 'label');

        $students = $this->bulkCorrectionStudentQuery($request, $instituteId)
            ->with(['session', 'stream.course.type', 'coursePart', 'educationDetails'])
            ->orderBy('name')
            ->get();

        $rows = $students->map(function (Student $student) use ($fields) {
            return array_map(fn($field) => $this->bulkExportValue($student, $field), $fields);
        })->all();

        return SimpleSpreadsheet::download('student-detail-correction-template.xlsx', $headers, $rows);
    }

    public function identityUpdate(Request $request, StudentAcademicIdentity $identity)
    {
        $this->ensurePromotionAccess();
        abort_if($identity->institute_id !== $this->instituteId(), 403);
        $request->validate([
            'roll_no' => 'nullable|string|max:50',
            'form_no' => 'nullable|string|max:50',
        ]);

        if ($request->roll_no) {
            $exists = StudentAcademicIdentity::where('institute_id', $identity->institute_id)
                ->where('academic_session_id', $identity->academic_session_id)
                ->where('semester_at_time', $identity->semester_at_time)
                ->where('roll_no', $request->roll_no)
                ->where('student_id', '!=', $identity->student_id)
                ->exists();

            if ($exists) {
                return back()->withErrors([
                    'roll_no' => "Roll No {$request->roll_no} already exists in this semester.",
                ]);
            }
        }

        if ($request->form_no) {
            $exists = StudentAcademicIdentity::where('institute_id', $identity->institute_id)
                ->where('academic_session_id', $identity->academic_session_id)
                ->where('semester_at_time', $identity->semester_at_time)
                ->where('form_no', $request->form_no)
                ->where('student_id', '!=', $identity->student_id)
                ->exists();

            if ($exists) {
                return back()->withErrors([
                    'form_no' => "Form No {$request->form_no} already exists in this semester.",
                ]);
            }
        }

        $identity->update([
            'roll_no' => $request->roll_no ?: $identity->roll_no,
            'form_no' => $request->form_no ?: $identity->form_no,
        ]);

        if ($this->shouldSyncStudentIdentityFields($identity)) {
            $update = [];
            if ($request->roll_no) {
                $update['roll_no'] = $request->roll_no;
            }
            if ($request->form_no) {
                $update['sr_no'] = $request->form_no;
            }
            if ($update) {
                Student::where('id', $identity->student_id)->update($update);
            }
        }

        return back()->with('success', 'Identity updated.');
    }

    public function identityBulkUpdate(Request $request)
    {
        $this->ensurePromotionAccess();
        $instituteId = $this->instituteId();

        if ($request->hasFile('bulk_file')) {
            $request->validate([
                'bulk_file' => 'required|file|mimes:csv,txt,xlsx',
                'identity_column' => 'nullable|string|max:100',
                'session_id' => 'nullable|integer',
                'course_id' => 'nullable|integer',
                'course_part_id' => 'nullable|integer',
                'current_semester' => 'nullable|integer|min:1|max:20',
            ]);

            return $this->importStudentBulkSpreadsheet($request, $instituteId);
        }

        $request->validate(['identities' => 'required|array']);

        $errors = [];
        $updated = 0;

        foreach ($request->identities as $id => $data) {
            $identity = StudentAcademicIdentity::where('id', $id)
                ->where('institute_id', $instituteId)
                ->first();

            if (!$identity) {
                continue;
            }

            $rollNo = $data['roll_no'] ?? null;
            $formNo = $data['form_no'] ?? null;

            if ($rollNo) {
                $exists = StudentAcademicIdentity::where('institute_id', $instituteId)
                    ->where('academic_session_id', $identity->academic_session_id)
                    ->where('semester_at_time', $identity->semester_at_time)
                    ->where('roll_no', $rollNo)
                    ->where('student_id', '!=', $identity->student_id)
                    ->exists();

                if ($exists) {
                    $errors[] = "Roll No {$rollNo} duplicate hai, skip kiya gaya";
                    continue;
                }
            }

            if ($formNo) {
                $exists = StudentAcademicIdentity::where('institute_id', $instituteId)
                    ->where('academic_session_id', $identity->academic_session_id)
                    ->where('semester_at_time', $identity->semester_at_time)
                    ->where('form_no', $formNo)
                    ->where('student_id', '!=', $identity->student_id)
                    ->exists();

                if ($exists) {
                    $errors[] = "Form No {$formNo} duplicate hai, skip kiya gaya";
                    continue;
                }
            }

            $identity->update([
                'roll_no' => $rollNo,
                'form_no' => $formNo,
            ]);

            if ($this->shouldSyncStudentIdentityFields($identity)) {
                $studentUpdate = [];
                if ($rollNo) {
                    $studentUpdate['roll_no'] = $rollNo;
                }
                if ($formNo) {
                    $studentUpdate['sr_no'] = $formNo;
                }
                if ($studentUpdate) {
                    Student::where('id', $identity->student_id)->update($studentUpdate);
                }
            }

            $updated++;
        }

        $msg = "{$updated} records updated.";
        if ($errors) {
            $msg .= ' ' . count($errors) . ' errors: ' . implode(', ', $errors);
        }

        return back()->with($errors ? 'warning' : 'success', $msg);
    }

    private function importStudentBulkSpreadsheet(Request $request, int $instituteId)
    {
        try {
            $rows = SimpleSpreadsheet::read(
                $request->file('bulk_file')->getRealPath(),
                $request->file('bulk_file')->getClientOriginalExtension()
            );
        } catch (\Throwable $exception) {
            return back()->with('warning', $exception->getMessage());
        }

        if (count($rows) < 2) {
            return back()->with('warning', 'Uploaded file is empty or has no student rows.');
        }

        $fields = collect($this->bulkCorrectionFields($instituteId))->keyBy('key')->all();
        $headerRow = array_shift($rows);
        $headerMap = $this->bulkHeaderMap($headerRow, $fields);
        $identityColumn = $this->normalizeBulkHeading($request->input('identity_column') ?: 'UIN No.');

        if (!in_array('uin_no', $headerMap, true)) {
            foreach ($headerRow as $index => $heading) {
                if ($this->normalizeBulkHeading($heading) === $identityColumn) {
                    $headerMap[$index] = 'uin_no';
                }
            }
        }

        if (!in_array('uin_no', $headerMap, true)) {
            return back()->with('warning', 'Uploaded file must include UIN No. column, or select the correct UIN column name.');
        }

        $totalRows = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $successRows = [];

        foreach ($rows as $offset => $row) {
            $rowNumber = $offset + 2;
            if (count(array_filter($row, fn($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $totalRows++;
            $data = [];
            foreach ($headerMap as $index => $fieldKey) {
                if (!$fieldKey || !isset($fields[$fieldKey])) {
                    continue;
                }
                $data[$fieldKey] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            $uin = trim((string) ($data['uin_no'] ?? ''));
            if ($uin === '') {
                $errors[] = ['row' => $rowNumber, 'uin' => '', 'student' => '', 'error' => 'UIN No. is required.'];
                $skipped++;
                continue;
            }

            $student = $this->bulkCorrectionStudentQuery($request, $instituteId)
                ->where('uin_no', $uin)
                ->with('educationDetails')
                ->first();

            if (!$student) {
                $errors[] = ['row' => $rowNumber, 'uin' => $uin, 'student' => '', 'error' => 'Student not found in selected session/course/semester context.'];
                $skipped++;
                continue;
            }

            [$studentUpdate, $educationUpdate, $rowErrors] = $this->validateBulkCorrectionRow($data, $fields, $student);

            if ($rowErrors) {
                foreach ($rowErrors as $error) {
                    $errors[] = ['row' => $rowNumber, 'uin' => $uin, 'student' => $student->name, 'error' => $error];
                }
                $skipped++;
                continue;
            }

            if (!$studentUpdate && !$educationUpdate) {
                $errors[] = ['row' => $rowNumber, 'uin' => $uin, 'student' => $student->name, 'error' => 'No updatable fields found.'];
                $skipped++;
                continue;
            }

            $academicFields = ['course_stream_id', 'course_part_id', 'current_semester'];
            $isAcademicChange = !empty(array_intersect($academicFields, array_keys($studentUpdate)));

            if ($isAcademicChange) {
                $student->loadMissing(['stream.course', 'coursePart']);
            }
            $oldSnapshot = $isAcademicChange
                ? \App\Services\StudentAcademicChangeService::buildSnapshot($student)
                : null;

            DB::transaction(function () use ($student, $studentUpdate, $educationUpdate, $isAcademicChange, $oldSnapshot) {
                if ($studentUpdate) {
                    $student->update($studentUpdate);
                }

                foreach ($educationUpdate as $examName => $values) {
                    if (!collect($values)->contains(fn($value) => filled($value))) {
                        continue;
                    }

                    StudentEducationDetail::updateOrCreate(
                        ['student_id' => $student->id, 'exam_name' => $examName],
                        $values + ['exam_name' => $examName]
                    );
                }

                if ($isAcademicChange && $oldSnapshot) {
                    $student->refresh()->load(['stream.course', 'coursePart']);
                    $sessionId   = (int) $student->academic_session_id;
                    $subjectIds  = \App\Services\StudentAcademicChangeService::currentSubjectIds($student, $sessionId);
                    $newSnapshot = \App\Services\StudentAcademicChangeService::buildSnapshot($student);
                    $adjustment  = \App\Services\StudentAcademicChangeService::applyFeeDelta(
                        $student, $oldSnapshot, $newSnapshot,
                        'Academic data corrected via bulk update'
                    );
                    \App\Services\StudentAcademicChangeService::syncCurrentIdentity($student, $subjectIds);
                    \App\Services\StudentAcademicChangeService::createChangeLog(
                        $student, $oldSnapshot, $newSnapshot, $adjustment,
                        'web', auth()->user()?->name ?? 'Bulk Correction'
                    );
                }
            });

            $successRows[] = ['row' => $rowNumber, 'uin' => $uin, 'student' => $student->name];
            $updated++;
        }

        $report = [
            'total_rows' => $totalRows,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'success_rows' => $successRows,
        ];

        $message = "{$updated} student records updated. {$skipped} rows failed.";
        return back()->with($errors ? 'warning' : 'success', $message)->with('bulkReport', $report);
    }

    private function bulkCorrectionPageData(Request $request, array $extra = []): array
    {
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courses       = Course::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $courseParts   = CoursePart::with('course')
            ->whereHas('course', fn($q) => $q->where('institute_id', $instituteId))
            ->orderBy('course_id')
            ->orderBy('year_number')
            ->get();
        $sessionId = $request->input('session_id', $activeSession?->id);

        $countRequest = new Request(array_merge($request->all(), ['session_id' => $sessionId]));
        $studentsCount = $this->bulkCorrectionStudentQuery($countRequest, $instituteId)->count();

        return array_merge([
            'activeSession' => $activeSession,
            'sessions' => $sessions,
            'courses' => $courses,
            'courseParts' => $courseParts,
            'sessionId' => $sessionId,
            'studentsCount' => $studentsCount,
            'mappingFields' => $this->bulkCorrectionMappableFields($instituteId),
            'mappingFieldSections' => $this->bulkCorrectionMappingSections($instituteId),
        ], $extra);
    }

    private function bulkCorrectionMappableFields(int $instituteId): array
    {
        return array_values(array_filter(
            $this->bulkCorrectionFields($instituteId),
            fn($field) => !in_array($field['type'], ['readonly', 'identity'], true)
        ));
    }

    private function bulkCorrectionMappingSections(int $instituteId): array
    {
        $fields = $this->bulkCorrectionMappableFields($instituteId);
        $sections = [];

        foreach ($fields as $field) {
            $sectionKey = $field['section_key'] ?? 'other_details';
            if (!isset($sections[$sectionKey])) {
                $sections[$sectionKey] = [
                    'key' => $sectionKey,
                    'label' => $field['section_label'] ?? 'Other Details',
                    'icon' => $field['section_icon'] ?? 'bi-folder2-open',
                    'order' => $field['section_order'] ?? 99,
                    'fields' => [],
                    'subsections' => [],
                ];
            }

            if (!empty($field['subsection_key'])) {
                $subKey = $field['subsection_key'];
                if (!isset($sections[$sectionKey]['subsections'][$subKey])) {
                    $sections[$sectionKey]['subsections'][$subKey] = [
                        'key' => $subKey,
                        'label' => $field['subsection_label'] ?? ucfirst(str_replace('_', ' ', $subKey)),
                        'fields' => [],
                    ];
                }
                $sections[$sectionKey]['subsections'][$subKey]['fields'][] = $field;
            } else {
                $sections[$sectionKey]['fields'][] = $field;
            }
        }

        uasort($sections, fn($a, $b) => ($a['order'] <=> $b['order']) ?: strcmp($a['label'], $b['label']));

        return array_map(function ($section) {
            $section['subsections'] = array_values($section['subsections']);
            return $section;
        }, array_values($sections));
    }

    private function bulkSectionMeta(string $sectionKey): array
    {
        return match ($sectionKey) {
            'office' => ['section_key' => 'office', 'section_label' => 'Office Details', 'section_icon' => 'bi-briefcase', 'section_order' => 1],
            'course' => ['section_key' => 'course', 'section_label' => 'Course Details', 'section_icon' => 'bi-book', 'section_order' => 2],
            'personal' => ['section_key' => 'personal', 'section_label' => 'Personal Details', 'section_icon' => 'bi-person', 'section_order' => 3],
            'address' => ['section_key' => 'address', 'section_label' => 'Address Details', 'section_icon' => 'bi-geo-alt', 'section_order' => 4],
            'scholarship' => ['section_key' => 'scholarship', 'section_label' => 'Scholarship Details', 'section_icon' => 'bi-award', 'section_order' => 5],
            'education' => ['section_key' => 'education', 'section_label' => 'Education Details', 'section_icon' => 'bi-mortarboard', 'section_order' => 6],
            default => ['section_key' => 'other_details', 'section_label' => 'Other Details', 'section_icon' => 'bi-folder2-open', 'section_order' => 99],
        };
    }

    private function bulkSectionMetaForFieldKey(string $key): array
    {
        return match ($key) {
            'academic_session_id', 'sr_no', 'enrollment_no', 'roll_no', 'exam_form_no', 'uin_no', 'reference_no', 'admission_type', 'admission_source', 'gap_year', 'admission_date', 'submitted_date', 'institute_form_no'
                => $this->bulkSectionMeta('office'),
            'course_type_id', 'course_stream_id', 'course_part_id', 'current_semester'
                => $this->bulkSectionMeta('course'),
            'name', 'father_name', 'father_mobile', 'mother_name', 'mobile', 'email', 'dob', 'gender', 'guardian_mobile', 'guardian_name', 'guardian_relation', 'religion', 'category', 'special_category', 'nationality', 'aadhar_no', 'apaar_no', 'student_type', 'marital_status'
                => $this->bulkSectionMeta('personal'),
            'perm_village', 'perm_post', 'perm_thana', 'perm_district', 'perm_state', 'perm_pincode', 'comm_address', 'comm_city', 'comm_post', 'comm_thana', 'comm_district', 'comm_state', 'comm_pincode'
                => $this->bulkSectionMeta('address'),
            'has_scholarship', 'scholarship_name', 'scholarship_type', 'scholarship_authority', 'scholarship_applied_date', 'scholarship_amount', 'scholarship_ref_no'
                => $this->bulkSectionMeta('scholarship'),
            default => $this->bulkSectionMeta('other'),
        };
    }

    private function buildBulkMappingUpload(string $token, array $headers, array $rows): array
    {
        $fields = $this->bulkCorrectionMappableFields($this->instituteId());
        $headerSuggestions = [];
        foreach ($headers as $header) {
            $headerSuggestions[$header] = $this->normalizeBulkHeading($header);
        }

        $fieldSuggestions = [];
        foreach ($fields as $field) {
            $suggested = '';
            $fieldAliases = [
                $this->normalizeBulkHeading($field['label']),
                $this->normalizeBulkHeading($field['key']),
            ];

            foreach ($headers as $header) {
                $normalizedHeader = $headerSuggestions[$header];
                if (in_array($normalizedHeader, $fieldAliases, true)) {
                    $suggested = $header;
                    break;
                }
            }

            $fieldSuggestions[$field['key']] = $suggested;
        }

        $identitySuggestion = '';
        foreach ($headers as $header) {
            if (in_array($this->normalizeBulkHeading($header), ['uin', 'uin_no', 'uin_number', 'enrollment'], true)) {
                $identitySuggestion = $header;
                break;
            }
        }

        return [
            'token' => $token,
            'headers' => $headers,
            'row_count' => count(array_filter($rows, fn($row) => count(array_filter($row, fn($value) => trim((string) $value) !== '')) > 0)),
            'identity_suggestion' => $identitySuggestion,
            'field_suggestions' => $fieldSuggestions,
            'sample_rows' => array_slice($rows, 0, 3),
            'original_name' => $this->readBulkCorrectionUpload($token)['original_name'] ?? 'uploaded-file',
        ];
    }

    private function storeBulkCorrectionUpload(array $payload): string
    {
        $token = (string) Str::uuid();
        Storage::disk('local')->put(
            "bulk-correction/{$token}.json",
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $token;
    }

    private function readBulkCorrectionUpload(string $token): ?array
    {
        $path = "bulk-correction/{$token}.json";
        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        $data = json_decode((string) Storage::disk('local')->get($path), true);
        return is_array($data) ? $data : null;
    }

    private function deleteBulkCorrectionUpload(string $token): void
    {
        Storage::disk('local')->delete("bulk-correction/{$token}.json");
    }

    private function bulkCorrectionStudentQuery(Request $request, int $instituteId)
    {
        $query = Student::where('institute_id', $instituteId);

        if ($request->filled('session_id')) {
            $query->where('academic_session_id', $request->integer('session_id'));
        }

        if ($request->filled('course_id')) {
            $query->whereHas('stream', fn($stream) => $stream->where('course_id', $request->integer('course_id')));
        }

        if ($request->filled('course_part_id')) {
            $query->where('course_part_id', $request->integer('course_part_id'));
        }

        if ($request->filled('current_semester')) {
            $query->where('current_semester', $request->integer('current_semester'));
        }

        return $query;
    }

    private function bulkCorrectionFields(int $instituteId): array
    {
        $fields = [
            array_merge(['key' => 'student_uid', 'label' => 'Student UID', 'type' => 'readonly'], $this->bulkSectionMeta('office')),
            array_merge(['key' => 'uin_no', 'label' => 'UIN No.', 'type' => 'identity'], $this->bulkSectionMeta('office')),
            array_merge(['key' => 'academic_session_id', 'label' => 'Academic Session ID', 'type' => 'integer'], $this->bulkSectionMeta('office')),
            array_merge(['key' => 'academic_session', 'label' => 'Academic Session', 'type' => 'readonly'], $this->bulkSectionMeta('office')),
            array_merge(['key' => 'course_type_id', 'label' => 'Course Type ID', 'type' => 'integer'], $this->bulkSectionMeta('course')),
            array_merge(['key' => 'course_type', 'label' => 'Course Type', 'type' => 'readonly'], $this->bulkSectionMeta('course')),
            array_merge(['key' => 'course_id', 'label' => 'Course ID', 'type' => 'readonly'], $this->bulkSectionMeta('course')),
            array_merge(['key' => 'course', 'label' => 'Course', 'type' => 'readonly'], $this->bulkSectionMeta('course')),
            array_merge(['key' => 'course_stream_id', 'label' => 'Stream ID', 'type' => 'integer'], $this->bulkSectionMeta('course')),
            array_merge(['key' => 'course_stream', 'label' => 'Stream', 'type' => 'readonly'], $this->bulkSectionMeta('course')),
            array_merge(['key' => 'course_part_id', 'label' => 'Course Part ID', 'type' => 'integer'], $this->bulkSectionMeta('course')),
            array_merge(['key' => 'course_part', 'label' => 'Course Part', 'type' => 'readonly'], $this->bulkSectionMeta('course')),
            array_merge(['key' => 'current_semester', 'label' => 'Current Semester', 'type' => 'integer'], $this->bulkSectionMeta('course')),
        ];

        $seen = collect($fields)->pluck('key')->flip()->all();
        $formConfig = AdmissionFormController::getActiveConfig($instituteId, 'admission');
        $sections = AdmissionFormController::getSections('admission');

        foreach ($sections as $section) {
            foreach ($section['fields'] as $field) {
                $key = $field['key'];
                $config = $formConfig[$key] ?? [];
                if (!($config['section_enabled'] ?? true) || !($config['enabled'] ?? $field['enabled'] ?? false)) {
                    continue;
                }
                if (isset($seen[$key]) || in_array($key, ['photo', 'form_no', 'academic_session'], true)) {
                    continue;
                }

                $fields[] = [
                    'key' => $key,
                    'label' => $field['label'],
                    'type' => $this->bulkFieldType($key),
                    'required' => (bool) ($config['required'] ?? false),
                ] + $this->bulkSectionMetaForFieldKey($key);
                $seen[$key] = true;
            }
        }

        foreach ($this->bulkEducationFieldMap($instituteId) as $field) {
            $fields[] = $field;
        }

        return $fields;
    }

    private function bulkEducationFieldMap(int $instituteId): array
    {
        $formConfig = AdmissionFormController::getActiveConfig($instituteId, 'admission');
        $examFields = [
            'edu_10th' => '10TH',
            'edu_12th' => '12TH',
            'edu_graduation' => 'Graduation',
            'edu_other' => 'Other',
        ];
        $columns = [
            'education_stream' => 'STREAM',
            'institute_name' => 'Institute Name',
            'roll_number' => 'Roll No.',
            'passing_year' => 'Passing Year',
            'district' => 'District',
            'division' => 'Division',
            'board_university' => 'Board/University',
            'obtained_marks' => 'Obtained Marks',
            'max_marks' => 'Max Marks',
            'percentage' => 'Percentage',
        ];
        $fields = [];

        foreach ($examFields as $configKey => $examName) {
            $config = $formConfig[$configKey] ?? [];
            if (!($config['section_enabled'] ?? true) || !($config['enabled'] ?? false)) {
                continue;
            }

            foreach ($columns as $column => $label) {
                $fields[] = [
                    'key' => "education:{$examName}:{$column}",
                    'label' => "{$examName} {$label}",
                    'type' => in_array($column, ['obtained_marks', 'max_marks', 'percentage'], true) ? 'numeric' : 'string',
                    'education' => true,
                    'exam' => $examName,
                    'column' => $column,
                    'required' => (bool) ($config['required'] ?? false),
                    'subsection_key' => strtolower(str_replace([' ', '/'], '_', $examName)),
                    'subsection_label' => $examName,
                ] + $this->bulkSectionMeta('education');
            }
        }

        return $fields;
    }

    private function bulkFieldType(string $key): string
    {
        return match ($key) {
            'dob', 'admission_date', 'submitted_date', 'scholarship_applied_date' => 'date',
            'mobile', 'father_mobile', 'mother_mobile', 'guardian_mobile' => 'mobile',
            'name', 'father_name', 'mother_name', 'guardian_name' => 'person_name',
            'email' => 'email',
            'gap_year', 'comm_same_as_perm', 'has_scholarship' => 'boolean',
            'scholarship_amount' => 'numeric',
            'academic_session_id', 'course_type_id', 'course_stream_id', 'course_part_id', 'current_semester' => 'integer',
            default => 'string',
        };
    }

    private function bulkHeaderMap(array $headerRow, array $fields): array
    {
        $aliases = [];
        foreach ($fields as $field) {
            $aliases[$this->normalizeBulkHeading($field['label'])] = $field['key'];
            $aliases[$this->normalizeBulkHeading($field['key'])] = $field['key'];
        }

        $aliases += [
            'uin' => 'uin_no',
            'uin_number' => 'uin_no',
            'enrollment' => 'uin_no',
            'student_id' => 'student_uid',
            'application_no' => 'student_uid',
            'student_name' => 'name',
            'date_of_birth' => 'dob',
            'father' => 'father_name',
            'mother' => 'mother_name',
            'aadhar' => 'aadhar_no',
        ];

        $map = [];
        foreach ($headerRow as $index => $heading) {
            $normalized = $this->normalizeBulkHeading($heading);
            if (isset($aliases[$normalized])) {
                $map[$index] = $aliases[$normalized];
            }
        }

        return $map;
    }

    private function normalizeBulkHeading($heading): string
    {
        $heading = trim((string) $heading);
        $heading = preg_replace('/^\xEF\xBB\xBF/', '', $heading);
        $heading = strtolower($heading);
        return trim(preg_replace('/[^a-z0-9]+/', '_', $heading), '_');
    }

    private function bulkExportValue(Student $student, array $field): string
    {
        if (!empty($field['education'])) {
            $education = $student->educationDetails->first(fn($row) => strcasecmp((string) $row->exam_name, (string) $field['exam']) === 0);
            return (string) ($education?->{$field['column']} ?? '');
        }

        return match ($field['key']) {
            'academic_session' => (string) ($student->session?->name ?? ''),
            'course_type' => (string) ($student->stream?->course?->type?->name ?? ''),
            'course_id' => (string) ($student->stream?->course_id ?? ''),
            'course' => (string) ($student->stream?->course?->name ?? ''),
            'course_stream' => (string) ($student->stream?->name ?? ''),
            'course_part' => (string) ($student->coursePart?->year_label ?? ''),
            default => $this->formatBulkValue($student->{$field['key']} ?? ''),
        };
    }

    private function formatBulkValue($value): string
    {
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) ($value ?? '');
    }

    private function validateBulkCorrectionRow(array $data, array $fields, Student $student): array
    {
        $studentUpdate = [];
        $educationUpdate = [];
        $errors = [];
        $studentColumns = array_flip((new Student())->getFillable());

        foreach ($data as $key => $value) {
            $field = $fields[$key] ?? null;
            if (!$field || in_array($field['type'] ?? '', ['readonly', 'identity'], true)) {
                continue;
            }

            if (!empty($field['education'])) {
                $normalized = $this->normalizeBulkValue($value, $field, $errors);
                if ($normalized !== '__invalid__') {
                    $educationUpdate[$field['exam']][$field['column']] = $normalized;
                }
                continue;
            }

            if (!isset($studentColumns[$key])) {
                continue;
            }

            $normalized = $this->normalizeBulkValue($value, $field, $errors);
            if ($normalized === '__invalid__') {
                continue;
            }

            $studentUpdate[$key] = $normalized;
        }

        foreach (['academic_session_id', 'course_type_id', 'course_stream_id', 'course_part_id'] as $foreignKey) {
            if (array_key_exists($foreignKey, $studentUpdate) && filled($studentUpdate[$foreignKey])) {
                if (!$this->bulkForeignKeyValid($foreignKey, (int) $studentUpdate[$foreignKey], $student->institute_id)) {
                    $errors[] = "{$fields[$foreignKey]['label']} is invalid for this institute.";
                }
            }
        }

        if (isset($studentUpdate['course_stream_id'], $studentUpdate['course_part_id'])) {
            $streamCourseId = CourseStreamSubject::query()
                ->join('course_streams', 'course_stream_subjects.course_stream_id', '=', 'course_streams.id')
                ->where('course_streams.id', $studentUpdate['course_stream_id'])
                ->value('course_streams.course_id');
            $partCourseId = CoursePart::where('id', $studentUpdate['course_part_id'])->value('course_id');
            if ($streamCourseId && $partCourseId && (int) $streamCourseId !== (int) $partCourseId) {
                $errors[] = 'Stream ID and Course Part ID do not belong to same course.';
            }
        }

        return [$studentUpdate, $educationUpdate, $errors];
    }

    private function normalizeBulkValue(string $value, array $field, array &$errors)
    {
        $value = trim($value);
        $label = $field['label'];
        $required = (bool) ($field['required'] ?? false);

        if ($value === '') {
            if ($required) {
                $errors[] = "{$label} is required.";
                return '__invalid__';
            }
            return null;
        }

        return match ($field['type'] ?? 'string') {
            'person_name' => $this->validatePersonNameBulkValue($value, $label, $errors),
            'mobile' => $this->validateMobileBulkValue($value, $label, $errors),
            'email' => $this->validateEmailBulkValue($value, $label, $errors),
            'date' => $this->validateDateBulkValue($value, $label, $errors),
            'integer' => $this->validateIntegerBulkValue($value, $label, $errors),
            'numeric' => $this->validateNumericBulkValue($value, $label, $errors),
            'boolean' => $this->validateBooleanBulkValue($value, $label, $errors),
            default => mb_substr($value, 0, 255),
        };
    }

    private function validatePersonNameBulkValue(string $value, string $label, array &$errors)
    {
        if (!preg_match("/^[\pL .'-]+$/u", $value)) {
            $errors[] = "{$label} should contain letters only.";
            return '__invalid__';
        }

        return mb_substr($value, 0, 100);
    }

    private function validateMobileBulkValue(string $value, string $label, array &$errors)
    {
        $digits = preg_replace('/\D+/', '', $value);
        if (!preg_match('/^\d{10,15}$/', $digits)) {
            $errors[] = "{$label} must be 10 to 15 digits.";
            return '__invalid__';
        }

        return $digits;
    }

    private function validateEmailBulkValue(string $value, string $label, array &$errors)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "{$label} is not a valid email.";
            return '__invalid__';
        }

        return mb_substr($value, 0, 100);
    }

    private function validateDateBulkValue(string $value, string $label, array &$errors)
    {
        if (is_numeric($value) && (float) $value > 20000) {
            return \Carbon\Carbon::create(1899, 12, 30)->addDays((int) $value)->format('Y-m-d');
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                // Try the next accepted format.
            }
        }

        $errors[] = "{$label} date format is invalid. Use YYYY-MM-DD.";
        return '__invalid__';
    }

    private function validateIntegerBulkValue(string $value, string $label, array &$errors)
    {
        if (!preg_match('/^-?\d+$/', $value)) {
            $errors[] = "{$label} must be a number.";
            return '__invalid__';
        }

        return (int) $value;
    }

    private function validateNumericBulkValue(string $value, string $label, array &$errors)
    {
        if (!is_numeric($value)) {
            $errors[] = "{$label} must be numeric.";
            return '__invalid__';
        }

        return $value;
    }

    private function validateBooleanBulkValue(string $value, string $label, array &$errors)
    {
        $normalized = strtolower($value);
        if (in_array($normalized, ['1', 'yes', 'true', 'y'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'no', 'false', 'n'], true)) {
            return false;
        }

        $errors[] = "{$label} must be yes/no or 1/0.";
        return '__invalid__';
    }

    private function bulkForeignKeyValid(string $field, int $id, int $instituteId): bool
    {
        return match ($field) {
            'academic_session_id' => AcademicSession::where('institute_id', $instituteId)->where('id', $id)->exists(),
            'course_type_id' => CourseType::where('institute_id', $instituteId)->where('id', $id)->exists(),
            'course_stream_id' => CourseStreamSubject::query()
                ->join('course_streams', 'course_stream_subjects.course_stream_id', '=', 'course_streams.id')
                ->join('courses', 'course_streams.course_id', '=', 'courses.id')
                ->where('course_streams.id', $id)
                ->where('courses.institute_id', $instituteId)
                ->exists()
                || Course::where('institute_id', $instituteId)->whereHas('streams', fn($q) => $q->where('id', $id))->exists(),
            'course_part_id' => Course::where('institute_id', $instituteId)->whereHas('parts', fn($q) => $q->where('id', $id))->exists(),
            default => true,
        };
    }

    public function report(Request $request)
    {
        $this->ensurePromotionAccess();
        $instituteId = $this->instituteId();
        $sessions    = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();

        $this->ensureStaffCanAccessSession($request->filled('from_session_id') ? (int) $request->from_session_id : null);
        $this->ensureStaffCanAccessSession($request->filled('to_session_id') ? (int) $request->to_session_id : null);

        $query = PromotionLog::with([
            'student.stream.course',
            'fromSession',
            'toSession',
            'fromCoursePart',
            'toCoursePart',
        ])->where('institute_id', $instituteId);
        $this->applyPromotionLogAccessScope($query);

        if ($request->type) {
            $query->where('promotion_type', $request->type);
        }
        if ($request->status) {
            $status = (string) $request->status;
            if (in_array($status, self::TERMINAL_STATUSES, true)) {
                $query->where('terminal_status', $status);
            } else {
                $query->where('status', $status);
            }
        }
        if ($request->from_session_id) {
            $query->where('from_session_id', $request->from_session_id);
        }
        if ($request->to_session_id) {
            $query->where('to_session_id', $request->to_session_id);
        }
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('student', fn($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('student_uid', 'like', "%{$s}%"));
        }

        $logs  = $query->orderByDesc('created_at')->paginate(50)->withQueryString();
        $total = (clone $query)->count();

        return view('institute.admission.promotions.report', compact('logs', 'sessions', 'total'));
    }

    public function outcomesIndex(Request $request)
    {
        $this->ensurePromotionAccess();
        $instituteId = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);
        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courses = Course::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $sessionId = $request->input('session_id', $activeSession?->id);
        $courseId = $request->input('course_id');
        $status = $request->input('status');

        $this->ensureStaffCanAccessSession($sessionId ? (int) $sessionId : null);
        $this->ensureStaffCanAccessCourse($courseId ? (int) $courseId : null);

        $query = Student::with(['stream.course', 'coursePart', 'session'])
            ->where('institute_id', $instituteId)
            ->whereIn('status', self::TERMINAL_STATUSES);
        $this->applyStudentAccessScope($query);

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }
        if ($courseId) {
            $query->whereHas('stream', fn($q) => $q->where('course_id', $courseId));
        }
        if ($status && in_array($status, self::TERMINAL_STATUSES, true)) {
            $query->where('status', $status);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('student_uid', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($request->export === 'csv') {
            $all = $query->orderBy('name')->get();
            $allLogs = PromotionLog::where('institute_id', $instituteId)
                ->whereIn('student_id', $all->pluck('id'))
                ->where(function ($inner) {
                    $inner->whereIn('terminal_status', self::TERMINAL_STATUSES)
                        ->orWhereIn('status', ['completed', 'backlog', 'failed', 'dropped']);
                })
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('student_id');

            $headers = ['#', 'Std ID', 'Name', 'Father Name', 'Mother Name', 'Roll No', 'Enroll No', 'UIN No',
                        'Course', 'Stream', 'Session', 'Semester', 'Outcome', 'Due', 'Updated By', 'Date'];

            $filename = 'final-outcomes.csv';
            return response()->stream(function () use ($all, $allLogs, $headers) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $headers);
                foreach ($all as $i => $s) {
                    $log = $allLogs->get($s->id)?->first();
                    $outcome = $log?->terminal_status ?: $s->status;
                    $due = (float) ($log?->dues_carried_forward ?? 0);
                    fputcsv($handle, [
                        $i + 1,
                        $s->student_uid ?? '',
                        $s->name,
                        $s->father_name ?? '',
                        $s->mother_name ?? '',
                        $s->roll_no ?? '',
                        $s->enrollment_no ?? '',
                        $s->uin_no ?? '',
                        $s->stream?->course?->name ?? '',
                        $s->stream?->name ?? '',
                        $s->session?->name ?? '',
                        $s->current_semester ?? '',
                        ucwords(str_replace('_', ' ', $outcome ?? '')),
                        $due > 0 ? number_format($due, 2) : 'Clear',
                        $log?->promoted_by ?? '',
                        $log?->created_at?->format('d/m/Y') ?? '',
                    ]);
                }
                fclose($handle);
            }, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $students = $query->orderBy('name')->paginate(50)->withQueryString();
        $logs = PromotionLog::where('institute_id', $instituteId)
            ->whereIn('student_id', $students->pluck('id'))
            ->where(function ($inner) {
                $inner->whereIn('terminal_status', self::TERMINAL_STATUSES)
                    ->orWhereIn('status', ['completed', 'backlog', 'failed', 'dropped']);
            })
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('student_id');

        return view('institute.admission.promotions.outcomes', compact(
            'students',
            'logs',
            'sessions',
            'courses',
            'sessionId',
            'courseId',
            'status',
            'activeSession'
        ));
    }
}
