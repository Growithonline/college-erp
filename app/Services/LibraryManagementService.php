<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\InstituteTransaction;
use App\Models\InstituteWallet;
use App\Models\Library\LibraryBookCopy;
use App\Models\Library\LibraryMember;
use App\Models\Library\LibraryReservation;
use App\Models\Library\LibraryRuleSet;
use App\Models\Library\LibraryTransaction;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class LibraryManagementService
{
    public static function syncStudentMember(Student $student): ?LibraryMember
    {
        if (!$student->id || !$student->institute_id) {
            return null;
        }

        $status = self::studentLibraryStatus($student->status);
        $member = LibraryMember::where('student_id', $student->id)->first()
            ?: LibraryMember::where('institute_id', $student->institute_id)
                ->where('member_code', 'LIB-STU-' . str_pad((string) $student->id, 6, '0', STR_PAD_LEFT))
                ->first()
            ?: new LibraryMember();

        $member->fill([
            'institute_id' => $student->institute_id,
            'member_type' => 'student',
            'student_id' => $student->id,
            'rule_set_id' => $member->rule_set_id ?: self::defaultRuleSetId((int) $student->institute_id, 'student'),
            'member_code' => $member->member_code ?: 'LIB-STU-' . str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'name' => $student->name,
            'mobile' => $student->mobile,
            'email' => $student->email,
            'status' => $status['status'],
            'joined_on' => $student->admission_date ?? now()->toDateString(),
            'blocked_reason' => $status['blocked_reason'],
        ]);

        $member->save();
        $student->setRelation('libraryMember', $member);

        return $member;
    }

    public static function syncStaffMember(StaffMember $staffMember): ?LibraryMember
    {
        if (!$staffMember->id || !$staffMember->institute_id) {
            return null;
        }

        $memberType = self::staffMemberType($staffMember->role?->name);
        $status = self::staffLibraryStatus($staffMember->status);
        $memberCode = 'LIB-' . strtoupper(substr($memberType, 0, 3)) . '-' . str_pad((string) $staffMember->id, 6, '0', STR_PAD_LEFT);
        $member = LibraryMember::where('staff_member_id', $staffMember->id)->first()
            ?: LibraryMember::where('institute_id', $staffMember->institute_id)
                ->where('member_code', $memberCode)
                ->first()
            ?: new LibraryMember();

        $member->fill([
            'institute_id' => $staffMember->institute_id,
            'member_type' => $memberType,
            'staff_member_id' => $staffMember->id,
            'rule_set_id' => $member->rule_set_id ?: self::defaultRuleSetId((int) $staffMember->institute_id, $memberType),
            'member_code' => $member->member_code ?: $memberCode,
            'name' => $staffMember->name,
            'mobile' => $staffMember->mobile,
            'email' => $staffMember->email,
            'status' => $status['status'],
            'joined_on' => $staffMember->joining_date ?? now()->toDateString(),
            'blocked_reason' => $status['blocked_reason'],
        ]);

        $member->save();
        $staffMember->setRelation('libraryMember', $member);

        return $member;
    }

    public static function ensureMemberCanBorrow(LibraryMember $member): LibraryRuleSet
    {
        self::expireReservations((int) $member->institute_id);

        if ($member->status !== 'active') {
            throw ValidationException::withMessages([
                'library_member_id' => 'Member active nahi hai.',
            ]);
        }

        $rule = $member->ruleSet;
        if (!$rule || !$rule->is_active) {
            throw ValidationException::withMessages([
                'library_member_id' => 'Member ke liye active rule set assign nahi hai.',
            ]);
        }

        if (self::memberHasOverdues($member)) {
            throw ValidationException::withMessages([
                'library_member_id' => 'Member ke paas overdue books pending hain.',
            ]);
        }

        if (self::memberOutstandingFine($member) > 0) {
            throw ValidationException::withMessages([
                'library_member_id' => 'Member ka pending library fine clear nahi hai.',
            ]);
        }

        if ($member->activeTransactions()->count() >= $rule->max_books) {
            throw ValidationException::withMessages([
                'library_member_id' => 'Member issue limit cross kar chuka hai.',
            ]);
        }

        return $rule;
    }

    public static function ensureCopyCanBeIssuedToMember(LibraryBookCopy $copy, LibraryMember $member): void
    {
        if ($copy->status !== 'available') {
            throw ValidationException::withMessages([
                'library_book_copy_id' => 'Book copy available nahi hai.',
            ]);
        }

        $reservation = self::firstActiveReservation((int) $copy->book_id, (int) $copy->institute_id);
        if ($reservation && (int) $reservation->library_member_id !== (int) $member->id) {
            throw ValidationException::withMessages([
                'library_book_copy_id' => 'Is title par reservation queue pending hai. Pehle reserved member ko issue karo.',
            ]);
        }
    }

    public static function ensureTransactionCanRenew(LibraryTransaction $transaction): void
    {
        if ($transaction->current_status !== 'issued') {
            throw ValidationException::withMessages([
                'renew' => 'Sirf active issued transaction renew ho sakti hai.',
            ]);
        }

        if ($transaction->renew_count >= $transaction->max_renewals_snapshot) {
            throw ValidationException::withMessages([
                'renew' => 'Maximum renew limit complete ho chuki hai.',
            ]);
        }

        $member = $transaction->member()->with('ruleSet')->firstOrFail();
        if ($member->status !== 'active') {
            throw ValidationException::withMessages([
                'renew' => 'Inactive member ki renewal allow nahi hai.',
            ]);
        }

        if (!$member->ruleSet?->is_active) {
            throw ValidationException::withMessages([
                'renew' => 'Member ke liye active rule set assign nahi hai.',
            ]);
        }

        if (self::memberOutstandingFine($member) > 0) {
            throw ValidationException::withMessages([
                'renew' => 'Pending fine clear karke renewal karo.',
            ]);
        }

        if (self::memberHasOverdues($member)) {
            throw ValidationException::withMessages([
                'renew' => 'Member ke paas overdue books hain. Pehle return karo, phir renew karo.',
            ]);
        }

        if ($transaction->copy) {
            $reservation = self::firstActiveReservation((int) $transaction->copy->book_id, (int) $transaction->institute_id);
            if ($reservation && (int) $reservation->library_member_id !== (int) $member->id) {
                throw ValidationException::withMessages([
                    'renew' => 'Is title par dusre member ki reservation pending hai. Renew allow nahi hai.',
                ]);
            }
        }
    }

    public static function ensureReservationCanBeCreated(LibraryMember $member, int $bookId, int $instituteId): void
    {
        self::expireReservations($instituteId);

        if ($member->status !== 'active') {
            throw ValidationException::withMessages([
                'library_member_id' => 'Sirf active member reservation kar sakta hai.',
            ]);
        }

        if (!$member->ruleSet?->allow_reservation) {
            throw ValidationException::withMessages([
                'library_member_id' => 'Is member ke rule set me reservation allowed nahi hai.',
            ]);
        }

        if (self::memberHasOverdues($member)) {
            throw ValidationException::withMessages([
                'library_member_id' => 'Overdue books hone par reservation allow nahi hai.',
            ]);
        }

        if (self::memberOutstandingFine($member) > 0) {
            throw ValidationException::withMessages([
                'library_member_id' => 'Pending fine clear karke reservation karo.',
            ]);
        }

        $alreadyIssuedSameTitle = $member->transactions()
            ->where('current_status', 'issued')
            ->whereHas('copy', fn($query) => $query->where('book_id', $bookId))
            ->exists();

        if ($alreadyIssuedSameTitle) {
            throw ValidationException::withMessages([
                'book_id' => 'Member ke paas is title ki copy already issued hai.',
            ]);
        }
    }

    public static function ensureReservationCanBeFulfilled(LibraryReservation $reservation): void
    {
        self::expireReservations((int) $reservation->institute_id);

        if ($reservation->status !== 'pending') {
            throw ValidationException::withMessages([
                'reservation' => 'Sirf pending reservation fulfill ho sakti hai.',
            ]);
        }

        $firstPending = self::firstActiveReservation((int) $reservation->book_id, (int) $reservation->institute_id);
        if (!$firstPending || (int) $firstPending->id !== (int) $reservation->id) {
            throw ValidationException::withMessages([
                'reservation' => 'Queue order ke hisab se ye reservation abhi fulfill nahi ho sakti.',
            ]);
        }
    }

    public static function calculateFine(LibraryTransaction $transaction, Carbon $returnedOn): float
    {
        $dueOn = Carbon::parse($transaction->due_on);
        $delayDays = max(0, $dueOn->diffInDays($returnedOn, false));
        $chargeableDays = max(0, $delayDays - (int) $transaction->grace_days_snapshot);

        return $chargeableDays * (float) $transaction->fine_per_day_snapshot;
    }

    public static function finalizeReturn(
        LibraryTransaction $transaction,
        Carbon $returnedOn,
        string $returnMode,
        float $penaltyAmount,
        ?string $remarks,
        string $actorName
    ): void {
        $transaction->loadMissing(['member.student', 'copy.book']);

        $overdueFine = self::calculateFine($transaction, $returnedOn);
        $fineAmount = $overdueFine + max(0, $penaltyAmount);
        $previousFine = (float) $transaction->fine_amount;

        $copyStatus = match ($returnMode) {
            'lost' => 'lost',
            'damaged' => 'damaged',
            default => self::firstActiveReservation((int) $transaction->copy->book_id, (int) $transaction->institute_id) ? 'reserved' : 'available',
        };

        $transaction->update([
            'current_status' => $returnMode,
            'returned_on' => $returnedOn->toDateString(),
            'fine_amount' => $fineAmount,
            'remarks' => trim((string) $remarks) ?: $transaction->remarks,
            'returned_by' => $actorName,
        ]);

        $transaction->copy()->update([
            'status' => $copyStatus,
            'condition_note' => trim((string) $remarks) ?: $transaction->copy->condition_note,
        ]);

        if ($fineAmount > $previousFine) {
            self::postFineChargeToWallet($transaction, $fineAmount - $previousFine, ucfirst($returnMode));
        }

        if (in_array($returnMode, ['lost', 'damaged']) && self::hasReservationsTable()) {
            $hasOtherAvailable = LibraryBookCopy::where('book_id', $transaction->copy->book_id)
                ->where('institute_id', $transaction->institute_id)
                ->where('status', 'available')
                ->exists();

            if (!$hasOtherAvailable) {
                LibraryReservation::forInstitute((int) $transaction->institute_id)
                    ->where('book_id', $transaction->copy->book_id)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);
            }
        }
    }

    public static function postFinePaymentToWallet(LibraryTransaction $transaction, float $amount, string $paymentDate): void
    {
        if ($amount <= 0) {
            return;
        }

        $member = $transaction->member()->with('student')->first();
        $instituteId = (int) $transaction->institute_id;

        if ($member?->student) {
            $student   = $member->student;
            $sessionId = (int) $student->academic_session_id;

            $wallet = StudentWallet::firstOrCreate(
                ['student_id' => $student->id, 'academic_session_id' => $sessionId],
                ['institute_id' => $student->institute_id, 'main_b' => 0.00]
            );

            $opBal = (float) $wallet->main_b;
            $clBal = $opBal + $amount;

            StudentTransaction::create([
                'student_id'          => $student->id,
                'institute_id'        => $student->institute_id,
                'academic_session_id' => $sessionId,
                'des'                 => 'Library fine paid - ' . ($transaction->copy->book->title ?? 'Book') . ' / ' . ($transaction->copy->accession_no ?? '-'),
                'credit'              => $amount,
                'debit'               => 0.00,
                'type'                => StudentTransaction::CREDIT,
                'date'                => $paymentDate,
                'op_bal'              => $opBal,
                'cl_bal'              => $clBal,
                'by_user_id'          => self::resolveActorId(),
            ]);

            $wallet->update(['main_b' => $clBal]);
        }

        $sessionId = $member?->student?->academic_session_id
            ?? AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->value('id');

        if (!$sessionId) {
            return;
        }

        $instWallet = InstituteWallet::firstOrCreate(
            ['institute_id' => $instituteId, 'academic_session_id' => $sessionId],
            ['main_b' => 0.00]
        );
        $iOpBal = (float) $instWallet->main_b;
        $iClBal = $iOpBal + $amount;

        InstituteTransaction::create([
            'institute_id'        => $instituteId,
            'academic_session_id' => $sessionId,
            'des'                 => 'Library fine paid - ' . ($transaction->copy->book->title ?? 'Book') . ' / ' . ($transaction->copy->accession_no ?? '-'),
            'credit'              => $amount,
            'debit'               => 0.00,
            'type'                => InstituteTransaction::CREDIT,
            'date'                => $paymentDate,
            'op_bal'              => $iOpBal,
            'cl_bal'              => $iClBal,
            'by_user_id'          => self::resolveActorId(),
        ]);

        $instWallet->update(['main_b' => $iClBal]);
    }

    public static function noDueSummaryForStudent(Student $student): array
    {
        $member = $student->libraryMember;
        $activeIssues = $member?->activeTransactions ?? collect();
        $pendingFine = $member ? self::memberOutstandingFine($member) : 0.0;

        return [
            'member' => $member,
            'active_issues' => $activeIssues,
            'active_issue_count' => $activeIssues->count(),
            'pending_fine' => $pendingFine,
            'is_clear' => $activeIssues->isEmpty() && $pendingFine <= 0.0,
        ];
    }

    public static function memberOutstandingFine(LibraryMember $member): float
    {
        return (float) $member->transactions()
            ->selectRaw('COALESCE(SUM(fine_amount - fine_paid), 0) as amount')
            ->value('amount');
    }

    public static function memberHasOverdues(LibraryMember $member): bool
    {
        return $member->transactions()
            ->where('current_status', 'issued')
            ->whereDate('due_on', '<', now()->toDateString())
            ->exists();
    }

    public static function expireReservations(int $instituteId): void
    {
        static $done = [];

        if (isset($done[$instituteId]) || !self::hasReservationsTable()) {
            return;
        }

        $done[$instituteId] = true;

        LibraryReservation::forInstitute($instituteId)
            ->where('status', 'pending')
            ->whereNotNull('expires_on')
            ->whereDate('expires_on', '<', now()->toDateString())
            ->update(['status' => 'expired']);
    }

    public static function firstActiveReservation(int $bookId, int $instituteId): ?LibraryReservation
    {
        if (!self::hasReservationsTable()) {
            return null;
        }

        self::expireReservations($instituteId);

        return LibraryReservation::forInstitute($instituteId)
            ->where('book_id', $bookId)
            ->where('status', 'pending')
            ->orderBy('reserved_on')
            ->orderBy('id')
            ->first();
    }

    public static function staffMemberType(?string $roleName): string
    {
        $roleName = strtolower((string) $roleName);

        foreach (['faculty', 'teacher', 'lecturer', 'professor'] as $needle) {
            if (str_contains($roleName, $needle)) {
                return 'faculty';
            }
        }

        return 'staff';
    }

    public static function defaultRuleSetId(int $instituteId, string $memberType): ?int
    {
        return LibraryRuleSet::forInstitute($instituteId)
            ->where('member_type', $memberType)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');
    }

    public static function studentLibraryStatus(?string $studentStatus): array
    {
        return match (strtolower((string) $studentStatus)) {
            'active' => ['status' => 'active', 'blocked_reason' => null],
            'suspended', 'blocked' => ['status' => 'blocked', 'blocked_reason' => 'Student status is ' . ucfirst((string) $studentStatus)],
            'pending' => ['status' => 'inactive', 'blocked_reason' => 'Admission is pending approval.'],
            default => ['status' => 'inactive', 'blocked_reason' => null],
        };
    }

    public static function staffLibraryStatus(bool|string|null $staffStatus): array
    {
        if ($staffStatus === true || $staffStatus === 1 || $staffStatus === '1' || strtolower((string) $staffStatus) === 'active') {
            return ['status' => 'active', 'blocked_reason' => null];
        }

        return ['status' => 'inactive', 'blocked_reason' => null];
    }

    private static function postFineChargeToWallet(LibraryTransaction $transaction, float $amount, string $label): void
    {
        $member = $transaction->member;
        if (!$member?->student || $amount <= 0) {
            return;
        }

        $wallet = StudentWallet::firstOrCreate(
            [
                'student_id' => $member->student->id,
                'academic_session_id' => $member->student->academic_session_id,
            ],
            [
                'institute_id' => $member->student->institute_id,
                'main_b' => 0.00,
            ]
        );

        $opBal = (float) $wallet->main_b;
        $clBal = $opBal - $amount;

        StudentTransaction::create([
            'student_id' => $member->student->id,
            'institute_id' => $member->student->institute_id,
            'academic_session_id' => $member->student->academic_session_id,
            'des' => 'Library fine charged: ' . $label . ' - ' . ($transaction->copy->book->title ?? 'Book') . ' / ' . ($transaction->copy->accession_no ?? '-'),
            'credit' => 0.00,
            'debit' => $amount,
            'type' => StudentTransaction::DEBIT,
            'date' => now()->toDateString(),
            'op_bal' => $opBal,
            'cl_bal' => $clBal,
            'by_user_id' => self::resolveActorId(),
        ]);

        $wallet->update(['main_b' => $clBal]);
    }

    private static function resolveActorId(): ?int
    {
        foreach (['library_staff', 'staff', 'center', 'partner', 'web'] as $guard) {
            $id = auth()->guard($guard)->id();
            if ($id !== null) {
                return (int) $id;
            }
        }

        return null;
    }

    public static function postBulkFinePaymentToWallets(LibraryMember $member, float $amount, string $paymentDate, string $description): void
    {
        if ($amount <= 0) {
            return;
        }

        $instituteId = (int) $member->institute_id;

        if ($member->student) {
            $student   = $member->student;
            $sessionId = (int) $student->academic_session_id;

            $wallet  = StudentWallet::firstOrCreate(
                ['student_id' => $student->id, 'academic_session_id' => $sessionId],
                ['institute_id' => $student->institute_id, 'main_b' => 0.00]
            );
            $opBal = (float) $wallet->main_b;
            $clBal = $opBal + $amount;

            StudentTransaction::create([
                'student_id'          => $student->id,
                'institute_id'        => $student->institute_id,
                'academic_session_id' => $sessionId,
                'des'                 => $description,
                'credit'              => $amount,
                'debit'               => 0.00,
                'type'                => StudentTransaction::CREDIT,
                'date'                => $paymentDate,
                'op_bal'              => $opBal,
                'cl_bal'              => $clBal,
                'by_user_id'          => self::resolveActorId(),
            ]);

            $wallet->update(['main_b' => $clBal]);
        }

        $sessionId = $member->student?->academic_session_id
            ?? AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->value('id');

        if (!$sessionId) {
            return;
        }

        $instWallet = InstituteWallet::firstOrCreate(
            ['institute_id' => $instituteId, 'academic_session_id' => $sessionId],
            ['main_b' => 0.00]
        );
        $iOpBal = (float) $instWallet->main_b;
        $iClBal = $iOpBal + $amount;

        InstituteTransaction::create([
            'institute_id'        => $instituteId,
            'academic_session_id' => $sessionId,
            'des'                 => $description,
            'credit'              => $amount,
            'debit'               => 0.00,
            'type'                => InstituteTransaction::CREDIT,
            'date'                => $paymentDate,
            'op_bal'              => $iOpBal,
            'cl_bal'              => $iClBal,
            'source_type'         => 'library_fine',
            'by_user_id'          => self::resolveActorId(),
        ]);

        $instWallet->update(['main_b' => $iClBal]);
    }

    public static function hasReservationsTable(): bool
    {
        static $checked = null;

        if ($checked !== null) {
            return $checked;
        }

        return $checked = Schema::hasTable('library_reservations');
    }
}
