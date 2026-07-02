<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\CenterWallet;
use App\Models\ChannelWallet;
use App\Models\ChequePayment;
use App\Models\CoursePart;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use App\Models\FeeType;
use App\Models\InstituteTransaction;
use App\Models\InstituteWallet;
use App\Models\PartnerCommissionEntry;
use App\Models\PromotionLog;
use App\Models\Student;
use App\Models\StudentAcademicIdentity;
use App\Models\StudentSubject;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use App\Models\InstituteTransportSetting;
use App\Models\TransportAllocation;
use App\Models\TransportPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WalletService
{
    private static function resolveCoursePartForSemester(Student $student, int $sessionId, ?int $semester, ?StudentAcademicIdentity $identity = null): ?CoursePart
    {
        $student->loadMissing(['stream.course', 'coursePart']);

        $courseId = (int) ($student->stream?->course_id ?? 0);
        if ($courseId <= 0 || !$semester) {
            return $identity?->coursePart ?? $student->coursePart;
        }

        $course        = $student->stream?->course;
        $structureType = strtolower((string) ($course?->structure_type ?? ''));
        $spy           = $course?->effectiveSemestersPerYear() ?? 2;

        if ($structureType === 'semester' || $structureType === 'trimester') {
            $semesterPart = CoursePart::where('course_id', $courseId)
                ->where('part_number', (int) $semester)
                ->first();

            if ($semesterPart) {
                return $semesterPart;
            }
        }

        $targetYear = (int) ceil(((int) $semester) / max(1, $spy));

        return CoursePart::where('course_id', $courseId)
            ->where('year_number', max(1, $targetYear))
            ->orderBy('part_number')
            ->first()
            ?: ($identity?->coursePart ?? $student->coursePart);
    }

    private static function createStudentTransaction(array $attributes): StudentTransaction
    {
        return StudentTransaction::create($attributes);
    }

    private static function createInstituteTransaction(array $attributes): InstituteTransaction
    {
        return InstituteTransaction::create($attributes);
    }

    private static function updateTransportAllocationBalance(TransportAllocation $allocation, float $paidAmount): void
    {
        $allocation->paid_amount = round((float) $allocation->paid_amount + $paidAmount, 2);

        if ((float) $allocation->paid_amount <= 0) {
            $allocation->status = 'active';
        } elseif ((float) $allocation->paid_amount + 0.01 < (float) $allocation->fee_amount) {
            $allocation->status = 'partial';
        } else {
            $allocation->status = 'paid';
        }

        $allocation->save();
    }

    /**
     * Resolve the currently authenticated user's ID across all guards.
     * Returns null when no guard has an authenticated user (e.g. CLI / queue).
     */
    private static function resolveActorId(): ?int
    {
        foreach (['staff', 'center', 'partner', 'web'] as $guard) {
            $id = auth()->guard($guard)->id();
            if ($id !== null) {
                return (int) $id;
            }
        }
        return null;
    }

    public static function chargeCustomFeeItems(FeeInvoice $invoice, iterable $items): void
    {
        $student = $invoice->student ?? $invoice->student()->first();
        if (!$student) {
            return;
        }

        $sessionId = (int) $invoice->academic_session_id;
        $wallet = StudentWallet::firstOrCreate(
            ['student_id' => $student->id, 'academic_session_id' => $sessionId],
            ['institute_id' => $invoice->institute_id, 'main_b' => 0.00]
        );
        // Lock the row for the rest of this transaction — guards against a concurrent
        // double-submit interleaving its own read-modify-write of the same wallet balance.
        $wallet = StudentWallet::where('id', $wallet->id)->lockForUpdate()->first();

        foreach ($items as $item) {
            if (empty($item['is_custom'])) {
                continue;
            }

            $assignedFee = (float) ($item['total_fee'] ?? 0);
            $chargeAmount = $assignedFee > 0
                ? $assignedFee
                : (float) ($item['amount'] ?? 0) + (float) ($item['discount'] ?? 0);
            if ($chargeAmount <= 0) {
                continue;
            }

            $feeName = trim((string) ($item['fee_name'] ?? 'Custom Fee'));
            $opBal = (float) $wallet->main_b;
            $clBal = $opBal - $chargeAmount;

            self::createStudentTransaction([
                'student_id'          => $student->id,
                'institute_id'        => $invoice->institute_id,
                'academic_session_id' => $sessionId,
                'des'                 => 'Custom fee charged: ' . $feeName . ' - Invoice: ' . $invoice->invoice_no,
                'credit'              => 0.00,
                'debit'               => $chargeAmount,
                'type'                => StudentTransaction::DEBIT,
                'date'                => $invoice->payment_date,
                'op_bal'              => $opBal,
                'cl_bal'              => $clBal,
                'fee_invoice_id'      => $invoice->id,
                'by_user_id'          => self::resolveActorId(),
            ]);

            $wallet->main_b = $clBal;
            $wallet->save();
        }

        JournalService::safePostCustomFeeAssigned($invoice, $items);
    }

    public static function chargeFineItems(FeeInvoice $invoice, iterable $items): void
    {
        $student = $invoice->student ?? $invoice->student()->first();
        if (!$student) {
            return;
        }

        $sessionId = (int) $invoice->academic_session_id;
        $wallet = StudentWallet::firstOrCreate(
            ['student_id' => $student->id, 'academic_session_id' => $sessionId],
            ['institute_id' => $invoice->institute_id, 'main_b' => 0.00]
        );
        // Lock the row for the rest of this transaction — guards against a concurrent
        // double-submit interleaving its own read-modify-write of the same wallet balance.
        $wallet = StudentWallet::where('id', $wallet->id)->lockForUpdate()->first();

        foreach ($items as $item) {
            $fineAmount = (float) ($item['fine'] ?? 0);
            if ($fineAmount <= 0) {
                continue;
            }

            $feeName = trim((string) ($item['fee_name'] ?? 'Fee'));
            $opBal = (float) $wallet->main_b;
            $clBal = $opBal - $fineAmount;

            self::createStudentTransaction([
                'student_id'          => $student->id,
                'institute_id'        => $invoice->institute_id,
                'academic_session_id' => $sessionId,
                'des'                 => 'Fine charged: ' . $feeName . ' - Invoice: ' . $invoice->invoice_no,
                'credit'              => 0.00,
                'debit'               => $fineAmount,
                'type'                => StudentTransaction::DEBIT,
                'date'                => $invoice->payment_date,
                'op_bal'              => $opBal,
                'cl_bal'              => $clBal,
                'fee_invoice_id'      => $invoice->id,
                'by_user_id'          => self::resolveActorId(),
            ]);

            $wallet->main_b = $clBal;
            $wallet->save();
        }

        JournalService::safePostFineAssigned($invoice, $items);
    }

    /**
     * Writes the actual financial effect of a fee collection: FeeInvoiceItem rows,
     * custom/fine charges, the wallet+income credit, transport settlement, and partner
     * commission. Shared by the immediate-collection path in FeeCollectionController::store()
     * and FeeApprovalController::approve() (for invoices that were held for admin approval) —
     * both must produce an identical result, so this is the single place that does it.
     *
     * Expects $invoice->total_amount/discount/paid_amount/invoice_no to already be set to
     * their final values by the caller.
     */
    public static function settleApprovedInvoice(FeeInvoice $invoice, iterable $validItems): void
    {
        foreach ($validItems as $item) {
            $feeType = !empty($item['fee_type_id'])
                ? FeeType::find($item['fee_type_id'])
                : null;

            $fine = (float) ($item['fine'] ?? 0);

            $assignedFee = (float) ($item['total_fee'] ?? 0);
            if ($assignedFee <= 0) {
                // fallback for custom fees where no assigned fee exists
                $assignedFee = (float) ($item['amount'] ?? 0) + (float) ($item['discount'] ?? 0);
            }

            FeeInvoiceItem::create([
                'fee_invoice_id' => $invoice->id,
                'fee_type_id'    => $feeType?->id,
                'subject_id'     => !empty($item['subject_id']) ? (int) $item['subject_id'] : null,
                'item_type'      => $item['item_type'] ?: null,
                'fee_name'       => $item['fee_name'] ?? ($feeType?->name ?? 'Fee'),
                'amount'         => (float) ($item['amount'] ?? 0),
                'discount'       => (float) ($item['discount'] ?? 0),
                'fine'           => $fine,
                'total_fee'      => $assignedFee,
            ]);
        }

        self::chargeCustomFeeItems($invoice, $validItems);
        self::chargeFineItems($invoice, $validItems);
        self::onFeeCollection($invoice);

        // Settle transport allocations collected via this invoice
        foreach ($validItems as $tItem) {
            if (($tItem['item_type'] ?? '') === 'transport' && !empty($tItem['transport_allocation_id'])) {
                self::settleTransportFromInvoice(
                    (int) $tItem['transport_allocation_id'],
                    (float) ($tItem['amount'] ?? 0),
                    $invoice->id,
                    self::resolveActorId()
                );
            }
        }

        if ($partner = auth()->guard('partner')->user()) {
            $pct = (float) $partner->commission_percent;
            if ($pct > 0) {
                PartnerCommissionEntry::create([
                    'partner_id'         => $partner->id,
                    'fee_invoice_id'     => $invoice->id,
                    'paid_amount'        => (float) $invoice->paid_amount,
                    'commission_percent' => $pct,
                    'commission_amount'  => round((float) $invoice->paid_amount * $pct / 100, 2),
                ]);
            }
        }
    }

    public static function onAdmission(Student $student): void
    {
        $sessionId = $student->academic_session_id;
        if (!$sessionId) {
            return;
        }

        $subjectIds = $student->studentSubjects()
            ->where('academic_session_id', $sessionId)
            ->pluck('subject_id')
            ->toArray();

        $coursePart = $student->coursePart?->year_number ?? 1;
        $session = AcademicSession::find($sessionId);
        $semester = $session?->current_semester ?? 1;

        $feeData = FeeCalculatorService::calculate(
            instituteId:     $student->institute_id,
            sessionId:       $sessionId,
            courseId:        $student->stream?->course_id ?? 0,
            coursePart:      $coursePart,
            semester:        $semester,
            studentType:     $student->student_type ?? 'regular',
            admissionSource: $student->admission_source ?? 'direct',
            category:        $student->category ?? 'general',
            gender:          $student->gender ?? 'other',
            subjectIds:      $subjectIds,
            courseStreamId:  $student->course_stream_id,
            coursePartId:    $student->course_part_id
        );

        if (empty($feeData['items']) || ($feeData['total'] ?? 0) <= 0) {
            return;
        }

        DB::transaction(function () use ($student, $sessionId, $feeData) {
            $wallet = StudentWallet::firstOrCreate(
                [
                    'student_id'          => $student->id,
                    'academic_session_id' => $sessionId,
                ],
                ['institute_id' => $student->institute_id, 'main_b' => 0.00]
            );

            foreach ($feeData['items'] as $item) {
                $amount = (float) ($item['amount'] ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                $opBal = (float) $wallet->main_b;
                $clBal = $opBal - $amount;

                self::createStudentTransaction([
                    'student_id'          => $student->id,
                    'institute_id'        => $student->institute_id,
                    'academic_session_id' => $sessionId,
                    'des'                 => 'Fee charged: ' . $item['label'],
                    'credit'              => 0.00,
                    'debit'               => $amount,
                    'type'                => StudentTransaction::DEBIT,
                    'date'                => now()->toDateString(),
                    'op_bal'              => $opBal,
                    'cl_bal'              => $clBal,
                    'by_user_id'          => self::resolveActorId(),
                ]);

                $wallet->main_b = $clBal;
                $wallet->save();
            }
        });

        JournalService::safePostAdmissionFeeAssigned($student, $feeData);
    }

    public static function onFeeCollection(FeeInvoice $invoice): void
    {
        $sessionId = $invoice->academic_session_id;
        $instituteId = $invoice->institute_id;
        $cashAmount = (float) $invoice->paid_amount;
        $discAmount = (float) ($invoice->discount ?? 0);
        $student = $invoice->student;

        DB::transaction(function () use ($invoice, $sessionId, $instituteId, $cashAmount, $discAmount, $student) {
            $studentWallet = StudentWallet::firstOrCreate(
                ['student_id' => $student->id, 'academic_session_id' => $sessionId],
                ['institute_id' => $instituteId, 'main_b' => 0.00]
            );
            // Lock the row for the rest of this transaction — guards against a concurrent
            // double-submit (e.g. double-click on Collect) interleaving its own
            // read-modify-write of the same wallet balance, which would silently drop
            // one of the two credits.
            $studentWallet = StudentWallet::where('id', $studentWallet->id)->lockForUpdate()->first();

            if ($cashAmount > 0) {
                $opBal = (float) $studentWallet->main_b;
                $clBal = $opBal + $cashAmount;

                self::createStudentTransaction([
                    'student_id'          => $student->id,
                    'institute_id'        => $instituteId,
                    'academic_session_id' => $sessionId,
                    'des'                 => 'Fee paid - Invoice: ' . $invoice->invoice_no,
                    'credit'              => $cashAmount,
                    'debit'               => 0.00,
                    'type'                => StudentTransaction::CREDIT,
                    'date'                => $invoice->payment_date,
                    'op_bal'              => $opBal,
                    'cl_bal'              => $clBal,
                    'fee_invoice_id'      => $invoice->id,
                    'by_user_id'          => self::resolveActorId(),
                ]);

                $studentWallet->main_b = $clBal;
                $studentWallet->save();
            }

            if ($discAmount > 0) {
                $opBal = (float) $studentWallet->main_b;
                $clBal = $opBal + $discAmount;

                self::createStudentTransaction([
                    'student_id'          => $student->id,
                    'institute_id'        => $instituteId,
                    'academic_session_id' => $sessionId,
                    'des'                 => 'Discount granted - Invoice: ' . $invoice->invoice_no,
                    'credit'              => $discAmount,
                    'debit'               => 0.00,
                    'type'                => StudentTransaction::CREDIT,
                    'date'                => $invoice->payment_date,
                    'op_bal'              => $opBal,
                    'cl_bal'              => $clBal,
                    'fee_invoice_id'      => $invoice->id,
                    'by_user_id'          => self::resolveActorId(),
                ]);

                $studentWallet->main_b = $clBal;
                $studentWallet->save();
            }

            $instWallet = InstituteWallet::firstOrCreate(
                ['institute_id' => $instituteId, 'academic_session_id' => $sessionId],
                ['main_b' => 0.00]
            );
            // This row is shared across every fee collection for the institute+session,
            // so it sees far more concurrent writers than a per-student wallet — lock it.
            $instWallet = InstituteWallet::where('id', $instWallet->id)->lockForUpdate()->first();

            if ($cashAmount > 0) {
                $iOpBal = (float) $instWallet->main_b;
                $iClBal = $iOpBal + $cashAmount;

                self::createInstituteTransaction([
                    'institute_id'        => $instituteId,
                    'academic_session_id' => $sessionId,
                    'des'                 => 'Fee received: ' . $student->name . ' - ' . $invoice->invoice_no,
                    'credit'              => $cashAmount,
                    'debit'               => 0.00,
                    'type'                => InstituteTransaction::CREDIT,
                    'date'                => $invoice->payment_date,
                    'op_bal'              => $iOpBal,
                    'cl_bal'              => $iClBal,
                    'fee_invoice_id'      => $invoice->id,
                    'source_type'         => 'fee_invoice',
                    'source_id'           => $invoice->id,
                    'by_user_id'          => self::resolveActorId(),
                ]);

                $instWallet->main_b = $iClBal;
                $instWallet->save();
            }

            // Auto-create cheque tracking record inside transaction so it rolls back with wallet
            if (in_array(strtolower($invoice->payment_mode ?? ''), ['cheque', 'dd'])) {
                ChequePayment::firstOrCreate(
                    ['fee_invoice_id' => $invoice->id],
                    [
                        'institute_id'        => $invoice->institute_id,
                        'academic_session_id' => $invoice->academic_session_id,
                        'cheque_no'           => $invoice->transaction_ref ?? 'N/A',
                        'drawee_bank'         => $invoice->bank_name ?? null,
                        'cheque_date'         => $invoice->payment_date,
                        'amount'              => $invoice->paid_amount,
                        'status'              => ChequePayment::STATUS_PENDING,
                        'created_by'          => self::resolveActorId(),
                    ]
                );
            }
        });

        JournalService::safePostFeeCollection($invoice);
    }

    /**
     * @param  float|null  $overrideAmount  Pass a prorated/adjusted amount without mutating the model's fee_amount.
     */
    public static function chargeTransportAllocation(TransportAllocation $allocation, ?float $overrideAmount = null): void
    {
        DB::transaction(function () use ($allocation, $overrideAmount) {
            $allocation->loadMissing(['student', 'route', 'stop', 'vehicle', 'driver']);

            $amount = round((float) ($overrideAmount ?? $allocation->fee_amount), 2);
            if ($amount <= 0) {
                return;
            }

            // Yearly billing: skip charge if student already paid for this academic_year on this route
            if ($allocation->route?->billing_frequency === 'yearly') {
                $setting = InstituteTransportSetting::forInstitute((int) $allocation->institute_id);
                if ($setting->yearly_fee_cross_session) {
                    // CRIT-3 fix: verify session belongs to this institute
                    $session = AcademicSession::where('id', $allocation->academic_session_id)
                        ->where('institute_id', $allocation->institute_id)
                        ->first();
                    $academicYear = $session?->academic_year;

                    if ($academicYear) {
                        $yearSessionIds = AcademicSession::where('institute_id', $allocation->institute_id)
                            ->where('academic_year', $academicYear)
                            ->pluck('id');

                        // CRIT-2 fix: lock all related allocation rows before check to prevent race condition
                        TransportAllocation::where('student_id', $allocation->student_id)
                            ->where('transport_route_id', $allocation->transport_route_id)
                            ->whereIn('academic_session_id', $yearSessionIds)
                            ->lockForUpdate()
                            ->get();

                        $alreadyCharged = TransportAllocation::where('student_id', $allocation->student_id)
                            ->where('transport_route_id', $allocation->transport_route_id)
                            ->whereIn('academic_session_id', $yearSessionIds)
                            ->where('id', '!=', $allocation->id)
                            ->where('charged_amount', '>', 0)
                            ->exists();

                        if ($alreadyCharged) {
                            $allocation->charged_amount = 0;
                            $allocation->status = 'active';
                            $allocation->save();
                            return;
                        }
                    }
                }
            }

            $wallet = StudentWallet::firstOrCreate(
                [
                    'student_id' => $allocation->student_id,
                    'academic_session_id' => $allocation->academic_session_id,
                ],
                [
                    'institute_id' => $allocation->institute_id,
                    'main_b' => 0.00,
                ]
            );

            $wallet = StudentWallet::where('student_id', $allocation->student_id)
                ->where('academic_session_id', $allocation->academic_session_id)
                ->lockForUpdate()
                ->first() ?: $wallet;

            $opBal = (float) $wallet->main_b;
            $clBal = round($opBal - $amount, 2);

            self::createStudentTransaction([
                'student_id'          => $allocation->student_id,
                'institute_id'        => $allocation->institute_id,
                'academic_session_id' => $allocation->academic_session_id,
                'des'                 => sprintf(
                    'Transport fee charged - %s%s',
                    $allocation->route?->name ?? 'Route',
                    $allocation->stop ? ' / ' . $allocation->stop->stop_name : ''
                ),
                'credit'              => 0.00,
                'debit'               => $amount,
                'type'                => StudentTransaction::DEBIT,
                'date'                => $allocation->start_date?->toDateString() ?? now()->toDateString(),
                'op_bal'              => $opBal,
                'cl_bal'              => $clBal,
                'transport_allocation_id' => $allocation->id,
                'by_user_id'          => self::resolveActorId(),
            ]);

            $wallet->main_b = $clBal;
            $wallet->save();

            $allocation->charged_amount = $amount;
            $allocation->paid_amount = round((float) $allocation->paid_amount, 2);
            $allocation->status = $allocation->paid_amount <= 0 ? 'active' : ((float) $allocation->paid_amount + 0.01 < $amount ? 'partial' : 'paid');
            $allocation->save();
        });
    }

    public static function settleTransportFromInvoice(
        int $allocationId,
        float $amount,
        int $invoiceId,
        ?int $actorId = null
    ): void {
        DB::transaction(function () use ($allocationId, $amount, $invoiceId, $actorId) {
            $allocation = TransportAllocation::where('id', $allocationId)->lockForUpdate()->first();
            if (!$allocation) {
                return;
            }

            $amount = min(round($amount, 2), max(0, round((float) $allocation->balance, 2)));
            if ($amount <= 0) {
                return;
            }

            // Use the invoice's actual payment mode/date so transport collection reports
            // (cash/upi/cheque/online breakdown) reflect what staff actually selected,
            // not a generic "invoice" bucket.
            $invoice = FeeInvoice::find($invoiceId);

            TransportPayment::create([
                'transport_allocation_id' => $allocation->id,
                'student_id'              => $allocation->student_id,
                'institute_id'            => $allocation->institute_id,
                'academic_session_id'     => $allocation->academic_session_id,
                'amount'                  => $amount,
                'payment_date'            => $invoice?->payment_date ?? now()->toDateString(),
                'payment_mode'            => $invoice?->payment_mode ?? 'invoice',
                'reference_no'            => $invoice?->transaction_ref,
                'note'                    => 'Collected via fee invoice #' . $invoiceId,
                'fee_invoice_id'          => $invoiceId,
                'by_user_id'              => $actorId ?? self::resolveActorId(),
            ]);

            self::updateTransportAllocationBalance($allocation, $amount);
        });
    }

    public static function resolveAcademicContext(Student $student, int $sessionId): array
    {
        $student->loadMissing(['stream.course', 'coursePart']);

        $identity = StudentAcademicIdentity::with('coursePart')
            ->where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->realOnly()
            ->orderByDesc('semester_at_time')
            ->orderByDesc('id')
            ->first();

        $semester = $identity?->semester_at_time;
        if (!$semester && (int) $student->academic_session_id === $sessionId) {
            $semester = (int) ($student->current_semester ?? 1);
        }

        $coursePart = self::resolveCoursePartForSemester($student, $sessionId, $semester, $identity);

        $coursePartYear = (int) ($coursePart?->year_number ?? 1);
        $subjectQuery = StudentSubject::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId);

        if ($coursePartYear > 0) {
            $subjectQuery->where('year_number', $coursePartYear);
        }

        $subjectIds = $subjectQuery->pluck('subject_id')->unique()->values()->all();

        return [
            'identity'         => $identity,
            'semester'         => (int) ($semester ?: 1),
            'course_id'        => (int) ($identity?->course_id ?: ($student->stream?->course_id ?? 0)),
            'course_part'      => $coursePart,
            'course_part_year' => $coursePartYear,
            'subject_ids'      => $subjectIds,
        ];
    }

    public static function currentStatePromotionLog(Student $student, int $sessionId, ?int $semester = null): ?PromotionLog
    {
        $semester = $semester ?: self::resolveAcademicContext($student, $sessionId)['semester'];

        return PromotionLog::with('fromSession')
            ->where('student_id', $student->id)
            ->where('is_reversed', false)
            ->where('status', 'promoted')
            ->where('to_session_id', $sessionId)
            ->where('to_semester', $semester)
            ->latest('id')
            ->first();
    }

    public static function getAlreadyPaidByFeeName(
        Student $student,
        int $sessionId,
        ?PromotionLog $promotionLog = null
    ): Collection {
        $rawPaid = FeeInvoiceItem::whereHas('invoice', function ($query) use ($student, $sessionId, $promotionLog) {
                $query->where('student_id', $student->id)
                    ->where('academic_session_id', $sessionId)
                    ->where('is_cancelled', false);

                if ($promotionLog) {
                    $query->where('created_at', '>=', $promotionLog->created_at);
                }
            })
            ->selectRaw('fee_name, subject_id, item_type, SUM(amount) as paid_total, SUM(COALESCE(discount,0)) as discount_total, SUM(COALESCE(fine,0)) as fine_total')
            ->groupBy('fee_name', 'subject_id', 'item_type')
            ->get();

        $subjectPaidTotal = 0.0;
        $subjectDiscTotal = 0.0;
        $subjectFineTotal = 0.0;
        $practicalPaidTotal = 0.0;
        $practicalDiscTotal = 0.0;
        $practicalFineTotal = 0.0;
        $otherPaid = collect();

        foreach ($rawPaid as $row) {
            $name = strtolower((string) $row->fee_name);
            $itemType = strtolower(trim((string) ($row->item_type ?? '')));
            $hasSubject = !empty($row->subject_id);

            if (
                $itemType === 'subject' ||
                (
                    str_contains($name, 'subject fee') &&
                    !str_contains($name, 'all subjects') &&
                    !str_contains($name, 'practical')
                )
            ) {
                $subjectPaidTotal += (float) $row->paid_total;
                $subjectDiscTotal += (float) $row->discount_total;
                $subjectFineTotal += (float) $row->fine_total;
                continue;
            }

            if (
                ($itemType === 'practical' && $hasSubject) ||
                (
                    str_contains($name, 'practical fee') &&
                    !str_contains($name, 'all subjects') &&
                    !str_contains($name, 'subject fee')
                )
            ) {
                $otherPaid->put((string) $row->fee_name, (object) [
                    'fee_name'       => (string) $row->fee_name,
                    'paid_total'     => (float) $row->paid_total,
                    'discount_total' => (float) $row->discount_total,
                    'fine_total'     => (float) $row->fine_total,
                ]);
                continue;
            }

            if (str_contains($name, 'subject fee (all subjects)')) {
                $subjectPaidTotal += (float) $row->paid_total;
                $subjectDiscTotal += (float) $row->discount_total;
                $subjectFineTotal += (float) $row->fine_total;
                continue;
            }

            if (str_contains($name, 'practical fee (all subjects)')) {
                $practicalPaidTotal += (float) $row->paid_total;
                $practicalDiscTotal += (float) $row->discount_total;
                $practicalFineTotal += (float) $row->fine_total;
                continue;
            }

            $otherPaid->push($row);
        }

        $alreadyPaid = $otherPaid->keyBy('fee_name');

        if ($subjectPaidTotal > 0 || $subjectDiscTotal > 0 || $subjectFineTotal > 0) {
            $alreadyPaid->put('Subject Fee (All Subjects)', (object) [
                'fee_name'       => 'Subject Fee (All Subjects)',
                'paid_total'     => $subjectPaidTotal,
                'discount_total' => $subjectDiscTotal,
                'fine_total'     => $subjectFineTotal,
            ]);
        }

        if ($practicalPaidTotal > 0 || $practicalDiscTotal > 0 || $practicalFineTotal > 0) {
            $alreadyPaid->put('Practical Fee (All Subjects)', (object) [
                'fee_name'       => 'Practical Fee (All Subjects)',
                'paid_total'     => $practicalPaidTotal,
                'discount_total' => $practicalDiscTotal,
                'fine_total'     => $practicalFineTotal,
            ]);
        }

        return $alreadyPaid;
    }

    public static function buildPromotionAwareFeeState(Student $student, int $sessionId): array
    {
        $student->loadMissing(['stream.course', 'coursePart']);

        $context = self::resolveAcademicContext($student, $sessionId);
        $promotionLog = self::currentStatePromotionLog($student, $sessionId, $context['semester']);

        $feeData = ['total' => 0.0, 'items' => []];

        if ($context['course_id'] > 0 && $student->stream) {
            try {
                $feeData = FeeCalculatorService::calculate(
                    instituteId:     $student->institute_id,
                    sessionId:       $sessionId,
                    courseId:        $context['course_id'],
                    coursePart:      $context['course_part_year'],
                    semester:        $context['semester'],
                    studentType:     $student->student_type ?? 'regular',
                    admissionSource: $student->admission_source ?? 'direct',
                    category:        $student->category ?? 'general',
                    gender:          $student->gender ?? 'other',
                    subjectIds:      $context['subject_ids'],
                    courseStreamId:  $student->course_stream_id,
                    coursePartId:    $context['course_part']->id ?? $student->course_part_id
                );
            } catch (\Throwable $e) {
                $feeData = ['total' => 0.0, 'items' => []];
            }
        }

        $previousDueItems = StudentTransaction::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('type', StudentTransaction::DEBIT)
            ->where('des', 'like', 'Previous Due (%')
            ->selectRaw('des as label, SUM(debit) as amount')
            ->groupBy('des')
            ->orderBy('des')
            ->get()
            ->map(fn($row) => [
                'type'        => 'previous_due',
                'fee_type_id' => null,
                'label'       => $row->label,
                'amount'      => (float) $row->amount,
            ]);

        // Controls the date filter in getAlreadyPaidByFeeName.
        // For semester promotion with no Sem N rules we show Sem N-1 items and need ALL payments.
        $effectivePromotionLog = $promotionLog;

        if ($previousDueItems->isEmpty() && $promotionLog) {
            $dueAmount = (float) $promotionLog->dues_carried_forward;

            // Semester promotion where current semester has no fee rules:
            // show the previous semester's fee items so Total Charged / Total Paid display correctly.
            if ($promotionLog->promotion_type === 'semester' && empty($feeData['items'])) {
                $fromSemester = (int) $promotionLog->from_semester;
                try {
                    $prevData = FeeCalculatorService::calculate(
                        instituteId:     $student->institute_id,
                        sessionId:       $sessionId,
                        courseId:        $context['course_id'],
                        coursePart:      $context['course_part_year'],
                        semester:        $fromSemester,
                        studentType:     $student->student_type ?? 'regular',
                        admissionSource: $student->admission_source ?? 'direct',
                        category:        $student->category ?? 'general',
                        gender:          $student->gender ?? 'other',
                        subjectIds:      $context['subject_ids'],
                        courseStreamId:  $student->course_stream_id,
                        coursePartId:    $context['course_part']->id ?? $student->course_part_id
                    );
                } catch (\Throwable $e) {
                    $prevData = ['total' => 0.0, 'items' => []];
                }

                if (($prevData['total'] ?? 0) > 0) {
                    $feeData = $prevData;          // replace empty Sem N data with Sem N-1 items
                    $effectivePromotionLog = null; // include ALL session payments in already_paid
                    $dueAmount = 0;                // no separate previous_due item needed
                }
            }

            if ($dueAmount > 0) {
                $previousDueItems = collect([[
                    'type'        => 'previous_due',
                    'fee_type_id' => null,
                    'label'       => $promotionLog->promotion_type === 'semester'
                        ? 'Previous Due (Before Semester ' . $promotionLog->to_semester . ')'
                        : 'Previous Due (' . ($promotionLog->fromSession?->name ?? 'Previous Session') . ')',
                    'amount'      => $dueAmount,
                ]]);
            }
        }

        $items = array_merge(
            $previousDueItems->all(),
            $feeData['items'] ?? []
        );

        // Transport fee — active allocation with pending balance
        $transportAllocation = TransportAllocation::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('is_active', true)
            ->whereIn('status', ['active', 'partial'])
            ->with(['route', 'stop'])
            ->first();

        $transportTotal = 0.0;
        if ($transportAllocation && (float) $transportAllocation->balance > 0) {
            $tLabel = 'Transport Fee';
            if ($transportAllocation->route) {
                $tLabel .= ' — ' . $transportAllocation->route->name;
            }
            if ($transportAllocation->stop) {
                $tLabel .= ' / ' . $transportAllocation->stop->stop_name;
            }
            $transportTotal = round((float) $transportAllocation->balance, 2);
            $items[] = [
                'type'                    => 'transport',
                'fee_type_id'             => null,
                'label'                   => $tLabel,
                'subject_id'              => null,
                'amount'                  => $transportTotal,
                'transport_allocation_id' => $transportAllocation->id,
            ];
        }

        return [
            'total'         => (float) ($feeData['total'] ?? 0) + (float) $previousDueItems->sum('amount') + $transportTotal,
            'items'         => $items,
            'grouped_items' => self::groupFeeItems($items),
            'already_paid'  => self::getAlreadyPaidByFeeName($student, $sessionId, $effectivePromotionLog),
            'context'       => $context,
            'promotion_log' => $promotionLog,
        ];
    }

    public static function getFineByFee(Student $student, int $sessionId): array
    {
        $fineTxns = StudentTransaction::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('des', 'like', 'Fine charged:%')
            ->get(['des', 'debit']);

        $fineReversedTxns = StudentTransaction::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('des', 'like', 'Fine reversed%')
            ->get(['des', 'credit']);

        $fineByFee = [];
        foreach ($fineTxns as $txn) {
            if (preg_match('/^Fine charged: (.+?) - Invoice:/i', $txn->des, $m)) {
                $feeName = trim($m[1]);
                $fineByFee[$feeName] = ($fineByFee[$feeName] ?? 0) + (float) $txn->debit;
            }
        }
        foreach ($fineReversedTxns as $txn) {
            if (preg_match('/^Fine reversed - Fine charged: (.+?) - Invoice:/i', $txn->des, $m)) {
                $feeName = trim($m[1]);
                $fineByFee[$feeName] = max(0, ($fineByFee[$feeName] ?? 0) - (float) $txn->credit);
            }
        }

        return $fineByFee;
    }

    public static function buildPendingRows(Student $student, int $sessionId): Collection
    {
        $feeState = self::buildPromotionAwareFeeState($student, $sessionId);
        $items = $feeState['grouped_items'] ?? $feeState['items'] ?? [];
        $alreadyPaid = $feeState['already_paid'] ?? collect();

        $fineByFee = self::getFineByFee($student, $sessionId);

        return collect($items)->map(function ($item) use ($alreadyPaid, $fineByFee) {
            $label   = $item['label'] ?? 'Fee';
            $charged = (float) ($item['amount'] ?? 0);

            // Transport: 'amount' is already the current pending balance from allocation.
            // alreadyPaid subtraction would double-count, so return pending = charged directly.
            if (($item['type'] ?? '') === 'transport') {
                return [
                    'name'       => $label,
                    'charged'    => $charged,
                    'collection' => 0.0,
                    'discount'   => 0.0,
                    'fine'       => 0.0,
                    'paid'       => 0.0,
                    'pending'    => $charged,
                ];
            }

            $paidData   = $alreadyPaid->get($label);
            $collection = (float) ($paidData?->paid_total ?? 0);
            $discount   = (float) ($paidData?->discount_total ?? 0);
            $fineFromTxn = (float) ($fineByFee[$label] ?? 0);
            $fineFromItem = (float) ($paidData?->fine_total ?? 0);
            $fine = max($fineFromTxn, $fineFromItem);
            $collectionApplied = min($collection, $charged + $fine);
            $discountApplied   = min($discount, max(0, $charged + $fine - $collectionApplied));

            return [
                'name'       => $label,
                'charged'    => $charged,
                'collection' => $collectionApplied,
                'discount'   => $discountApplied,
                'fine'       => $fine,
                'paid'       => $collectionApplied + $discountApplied,
                'pending'    => max(0, $charged + $fine - $collectionApplied - $discountApplied),
            ];
        })->filter(fn($row) => $row['charged'] > 0)->values();
    }

    public static function getStudentSummary(Student $student, int $sessionId): array
    {
        $wallet = StudentWallet::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->first();
        $walletBalance = (float) ($wallet?->main_b ?? 0.00);

        $totalCollection = (float) StudentTransaction::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('des', 'like', 'Fee paid%')
            ->sum('credit');

        $collectionReversed = (float) StudentTransaction::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('des', 'like', 'Fee cancelled%')
            ->sum('debit');

        $totalDiscount = (float) StudentTransaction::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('des', 'like', 'Discount granted%')
            ->sum('credit');

        $discountReversed = (float) StudentTransaction::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('des', 'like', 'Discount reversed%')
            ->sum('debit');

        $totalFineCharged = (float) StudentTransaction::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('des', 'like', 'Fine charged:%')
            ->sum('debit');

        $totalFineReversed = (float) StudentTransaction::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->where('des', 'like', 'Fine reversed%')
            ->sum('credit');

        $netFine = max(0, $totalFineCharged - $totalFineReversed);

        // Use buildPendingRows so fine-coverage logic is consistent across the app
        $pendingRows = self::buildPendingRows($student, $sessionId);

        $totalCollectionApplied = (float) $pendingRows->sum('collection');
        $totalDiscountApplied   = (float) $pendingRows->sum('discount');
        $totalCharged           = (float) $pendingRows->sum('charged');
        $totalDue               = (float) $pendingRows->sum('pending');
        $balance                = $totalCollectionApplied + $totalDiscountApplied - $totalCharged - $netFine;
        $netCollection = max(0, $totalCollection - $collectionReversed);
        $netDiscount = max(0, $totalDiscount - $discountReversed);

        $libraryFineDue = (float) max(0, (float) DB::table('library_transactions as lt')
            ->join('library_members as lm', 'lm.id', '=', 'lt.library_member_id')
            ->where('lm.student_id', $student->id)
            ->where('lm.institute_id', $student->institute_id)
            ->where('lt.fine_amount', '>', DB::raw('lt.fine_paid'))
            ->selectRaw('COALESCE(SUM(lt.fine_amount - lt.fine_paid), 0) as amt')
            ->value('amt'));

        return [
            'balance'           => $balance,
            'wallet_balance'    => $walletBalance,
            'total_due'         => $totalDue + $libraryFineDue,
            'total_paid'        => $totalCollectionApplied,
            'total_collection'  => $totalCollectionApplied,
            'total_discount'    => $totalDiscountApplied,
            'total_fine'        => $netFine,
            'total_charged'     => $totalCharged,
            'library_fine_due'  => $libraryFineDue,
            'is_clear'          => ($totalDue + $libraryFineDue) <= 0,
            'ledger_collection' => $netCollection,
            'ledger_discount'   => $netDiscount,
        ];
    }

    public static function createInstituteWallet(int $instituteId, int $sessionId): void
    {
        InstituteWallet::firstOrCreate(
            ['institute_id' => $instituteId, 'academic_session_id' => $sessionId],
            ['main_b' => 0.00]
        );
    }

    /**
     * Sum of all "Fee charged:" debit transactions for a student in a session.
     * This reflects the original fee assigned at admission and is stable across
     * semester promotions (unlike total_charged which can vary based on fee rules).
     */
    public static function getOriginalFeeCharged(int $studentId, int $sessionId): float
    {
        return (float) StudentTransaction::where('student_id', $studentId)
            ->where('academic_session_id', $sessionId)
            ->where('type', StudentTransaction::DEBIT)
            ->where('des', 'like', 'Fee charged:%')
            ->sum('debit');
    }

    public static function onFeeCancel(FeeInvoice $invoice): void
    {
        $sessionId = $invoice->academic_session_id;
        $instituteId = $invoice->institute_id;
        $cashAmount = (float) $invoice->paid_amount;
        $discAmount = (float) ($invoice->discount ?? 0);
        $student = $invoice->student;

        DB::transaction(function () use ($invoice, $sessionId, $instituteId, $cashAmount, $discAmount, $student) {
            $studentWallet = StudentWallet::where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)
                ->first();

            if ($studentWallet && $cashAmount > 0) {
                $opBal = (float) $studentWallet->main_b;
                $clBal = $opBal - $cashAmount;

                self::createStudentTransaction([
                    'student_id'          => $student->id,
                    'institute_id'        => $instituteId,
                    'academic_session_id' => $sessionId,
                    'des'                 => 'Fee cancelled - Invoice: ' . $invoice->invoice_no,
                    'credit'              => 0.00,
                    'debit'               => $cashAmount,
                    'type'                => StudentTransaction::DEBIT,
                    'date'                => now()->toDateString(),
                    'op_bal'              => $opBal,
                    'cl_bal'              => $clBal,
                    'fee_invoice_id'      => $invoice->id,
                    'by_user_id'          => self::resolveActorId(),
                ]);

                $studentWallet->main_b = $clBal;
                $studentWallet->save();
            }

            if ($studentWallet && $discAmount > 0) {
                $opBal = (float) $studentWallet->main_b;
                $clBal = $opBal - $discAmount;

                self::createStudentTransaction([
                    'student_id'          => $student->id,
                    'institute_id'        => $instituteId,
                    'academic_session_id' => $sessionId,
                    'des'                 => 'Discount reversed - Invoice: ' . $invoice->invoice_no,
                    'credit'              => 0.00,
                    'debit'               => $discAmount,
                    'type'                => StudentTransaction::DEBIT,
                    'date'                => now()->toDateString(),
                    'op_bal'              => $opBal,
                    'cl_bal'              => $clBal,
                    'fee_invoice_id'      => $invoice->id,
                    'by_user_id'          => self::resolveActorId(),
                ]);

                $studentWallet->main_b = $clBal;
                $studentWallet->save();
            }

            $instWallet = InstituteWallet::where('institute_id', $instituteId)
                ->where('academic_session_id', $sessionId)
                ->first();

            if ($instWallet && $cashAmount > 0) {
                $iOpBal = (float) $instWallet->main_b;
                $iClBal = $iOpBal - $cashAmount;

                self::createInstituteTransaction([
                    'institute_id'        => $instituteId,
                    'academic_session_id' => $sessionId,
                    'des'                 => 'Fee cancelled: ' . $student->name . ' - ' . $invoice->invoice_no,
                    'credit'              => 0.00,
                    'debit'               => $cashAmount,
                    'type'                => InstituteTransaction::DEBIT,
                    'date'                => now()->toDateString(),
                    'op_bal'              => $iOpBal,
                    'cl_bal'              => $iClBal,
                    'fee_invoice_id'      => $invoice->id,
                    'source_type'         => 'fee_invoice',
                    'source_id'           => $invoice->id,
                    'by_user_id'          => self::resolveActorId(),
                ]);

                $instWallet->main_b = $iClBal;
                $instWallet->save();
            }

            $customChargeTransactions = StudentTransaction::where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)
                ->where('fee_invoice_id', $invoice->id)
                ->where('type', StudentTransaction::DEBIT)
                ->where('des', 'like', 'Custom fee charged:%')
                ->orderBy('id')
                ->get();

            foreach ($customChargeTransactions as $chargeTransaction) {
                $amount = (float) $chargeTransaction->debit;
                if ($amount <= 0) {
                    continue;
                }

                $opBal = (float) $studentWallet->main_b;
                $clBal = $opBal + $amount;

                self::createStudentTransaction([
                    'student_id'          => $student->id,
                    'institute_id'        => $instituteId,
                    'academic_session_id' => $sessionId,
                    'des'                 => 'Custom fee reversed - ' . $chargeTransaction->des,
                    'credit'              => $amount,
                    'debit'               => 0.00,
                    'type'                => StudentTransaction::CREDIT,
                    'date'                => now()->toDateString(),
                    'op_bal'              => $opBal,
                    'cl_bal'              => $clBal,
                    'fee_invoice_id'      => $invoice->id,
                    'by_user_id'          => self::resolveActorId(),
                ]);

                $studentWallet->main_b = $clBal;
                $studentWallet->save();
            }

            $fineChargeTransactions = StudentTransaction::where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)
                ->where('fee_invoice_id', $invoice->id)
                ->where('type', StudentTransaction::DEBIT)
                ->where('des', 'like', 'Fine charged:%')
                ->orderBy('id')
                ->get();

            foreach ($fineChargeTransactions as $chargeTransaction) {
                if (!$studentWallet) {
                    break;
                }

                $amount = (float) $chargeTransaction->debit;
                if ($amount <= 0) {
                    continue;
                }

                $opBal = (float) $studentWallet->main_b;
                $clBal = $opBal + $amount;

                self::createStudentTransaction([
                    'student_id'          => $student->id,
                    'institute_id'        => $instituteId,
                    'academic_session_id' => $sessionId,
                    'des'                 => 'Fine reversed - ' . $chargeTransaction->des,
                    'credit'              => $amount,
                    'debit'               => 0.00,
                    'type'                => StudentTransaction::CREDIT,
                    'date'                => now()->toDateString(),
                    'op_bal'              => $opBal,
                    'cl_bal'              => $clBal,
                    'fee_invoice_id'      => $invoice->id,
                    'by_user_id'          => self::resolveActorId(),
                ]);

                $studentWallet->main_b = $clBal;
                $studentWallet->save();
            }

            // Reverse transport payments linked to this invoice
            $transportPayments = TransportPayment::where('fee_invoice_id', $invoice->id)
                ->where('is_reversed', false)
                ->get();

            foreach ($transportPayments as $tp) {
                $allocation = TransportAllocation::where('id', $tp->transport_allocation_id)->lockForUpdate()->first();
                if (!$allocation) {
                    continue;
                }

                $tp->update(['is_reversed' => true]);

                $allocation->paid_amount = max(0, round((float) $allocation->paid_amount - (float) $tp->amount, 2));
                if ((float) $allocation->paid_amount <= 0) {
                    $allocation->status = 'active';
                } elseif ((float) $allocation->paid_amount + 0.01 < (float) $allocation->fee_amount) {
                    $allocation->status = 'partial';
                } else {
                    $allocation->status = 'paid';
                }
                $allocation->save();
            }

            // Refund center/partner token wallet if the invoice was collected via one
            if ($cashAmount > 0) {
                if ($invoice->collected_by_center_id) {
                    $centerWallet = CenterWallet::where('center_id', (int) $invoice->collected_by_center_id)
                        ->where('institute_id', $instituteId)
                        ->first();
                    $centerWallet?->refund($cashAmount, $invoice->id, self::resolveActorId(), 'Fee cancellation refund');
                } elseif ($invoice->collected_by_partner_id) {
                    $channelWallet = ChannelWallet::where('channel_partner_id', (int) $invoice->collected_by_partner_id)
                        ->where('institute_id', $instituteId)
                        ->first();
                    $channelWallet?->refund($cashAmount, $invoice->id, self::resolveActorId(), 'Fee cancellation refund');
                }
            }
        });

        JournalService::safeReverseFeeCancellation($invoice);
    }

    private static function groupFeeItems(array $items): array
    {
        $items = collect($items);
        $subjectFeeTotal = (float) $items->where('type', 'subject')->sum('amount');
        $practicalItems = $items->where('type', 'practical')->values();
        $otherItems = $items->whereNotIn('type', ['subject', 'practical'])->values();
        $groupedItems = collect();

        $getHierarchy = static function (array $item): int {
            $label = strtolower((string) ($item['label'] ?? ''));

            if (($item['type'] ?? null) === 'previous_due' || str_contains($label, 'previous due')) {
                return 0;
            }
            if (str_contains($label, 'registration')) {
                return 1;
            }
            if (str_contains($label, 'course')) {
                return 2;
            }
            if (str_contains($label, 'subject')) {
                return 3;
            }
            if (str_contains($label, 'practical')) {
                return 4;
            }
            if (str_contains($label, 'exam')) {
                return 5;
            }
            if (str_contains($label, 'admit')) {
                return 6;
            }
            if (str_contains($label, 'library')) {
                return 7;
            }
            if (str_contains($label, 'transport')) {
                return 8;
            }

            return 9;
        };

        foreach ($otherItems as $item) {
            $groupedItems->push(array_merge($item, [
                'hierarchy' => $getHierarchy($item),
            ]));
        }

        if ($subjectFeeTotal > 0) {
            $groupedItems->push([
                'type'        => 'subject_combined',
                'fee_type_id' => null,
                'label'       => 'Subject Fee (All Subjects)',
                'amount'      => $subjectFeeTotal,
                'hierarchy'   => 3,
            ]);
        }

        foreach ($practicalItems as $item) {
            $groupedItems->push(array_merge($item, [
                'hierarchy' => 4,
            ]));
        }

        return $groupedItems->sortBy('hierarchy')->values()->toArray();
    }
}
