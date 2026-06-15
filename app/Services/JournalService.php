<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\FeeType;
use App\Models\FinanceSetting;
use App\Models\InstituteBankAccount;
use App\Models\SalaryRecord;
use App\Models\Student;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class JournalService
{
    private const ENTRY_KEY_ADMISSION_FEE = 'fee-assigned:admission:student:%d:session:%d';
    private const ENTRY_KEY_CUSTOM_FEE = 'fee-assigned:custom:invoice:%d';
    private const ENTRY_KEY_FINE_FEE = 'fee-assigned:fine:invoice:%d';
    private const ENTRY_KEY_FEE_COLLECTION = 'fee-collected:invoice:%d';
    private const ENTRY_KEY_EXPENSE_PAYMENT = 'expense-payment:%d';
    private const ENTRY_KEY_SALARY_PAYMENT = 'salary-payment:%d';
    private const ENTRY_KEY_LIBRARY_FINE = 'library-fine-collection:receipt:%s';

    public static function post(array $header, array $lines): JournalEntry
    {
        $instituteId = (int) ($header['institute_id'] ?? 0);
        if ($instituteId <= 0) {
            throw new InvalidArgumentException('Valid institute_id is required for journal posting.');
        }

        $normalizedLines = self::normalizeLines($lines);
        self::assertBalanced($normalizedLines);
        self::assertAccountOwnership($instituteId, $normalizedLines);

        $entryKey = $header['entry_key'] ?? null;
        if ($entryKey) {
            $existing = JournalEntry::with('lines.account')
                ->where('institute_id', $instituteId)
                ->where('entry_key', $entryKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($header, $normalizedLines, $instituteId) {
            $totals = collect($normalizedLines);

            $entry = JournalEntry::create([
                'institute_id' => $instituteId,
                'academic_session_id' => $header['academic_session_id'] ?? null,
                'date' => $header['date'] ?? now()->toDateString(),
                'entry_key' => $header['entry_key'] ?? null,
                'reference_type' => $header['reference_type'] ?? null,
                'reference_id' => $header['reference_id'] ?? null,
                'status' => $header['status'] ?? JournalEntry::STATUS_POSTED,
                'narration' => $header['narration'] ?? null,
                'total_debit' => $totals->where('entry_type', 'debit')->sum('amount'),
                'total_credit' => $totals->where('entry_type', 'credit')->sum('amount'),
                'reversal_of_entry_id' => $header['reversal_of_entry_id'] ?? null,
                'posted_at' => $header['posted_at'] ?? now(),
                'created_by' => $header['created_by'] ?? null,
                'created_by_role' => $header['created_by_role'] ?? null,
                'meta' => $header['meta'] ?? null,
            ]);

            foreach ($normalizedLines as $index => $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'line_no' => $index + 1,
                    'entry_type' => $line['entry_type'],
                    'amount' => $line['amount'],
                    'narration' => $line['narration'] ?? null,
                    'meta' => $line['meta'] ?? null,
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    public static function reverse(JournalEntry $entry, array $overrides = []): JournalEntry
    {
        if ($entry->status === JournalEntry::STATUS_REVERSED && $entry->reversals()->exists()) {
            return $entry->reversals()->latest('id')->first();
        }

        $entry->loadMissing('lines');

        $reversalLines = $entry->lines->map(function ($line) {
            return [
                'account_id' => (int) $line->account_id,
                'entry_type' => $line->entry_type === 'debit' ? 'credit' : 'debit',
                'amount' => (float) $line->amount,
                'narration' => $line->narration,
                'meta' => $line->meta,
            ];
        })->all();

        $reversal = self::post([
            'institute_id' => $entry->institute_id,
            'academic_session_id' => $overrides['academic_session_id'] ?? $entry->academic_session_id,
            'date' => $overrides['date'] ?? now()->toDateString(),
            'entry_key' => $overrides['entry_key'] ?? ($entry->entry_key ? $entry->entry_key . ':reversal' : null),
            'reference_type' => $overrides['reference_type'] ?? $entry->reference_type,
            'reference_id' => $overrides['reference_id'] ?? $entry->reference_id,
            'narration' => $overrides['narration'] ?? ('Reversal: ' . ($entry->narration ?? 'Journal Entry')),
            'reversal_of_entry_id' => $entry->id,
            'created_by' => $overrides['created_by'] ?? null,
            'created_by_role' => $overrides['created_by_role'] ?? null,
            'meta' => $overrides['meta'] ?? ['reversed_entry_id' => $entry->id],
        ], $reversalLines);

        $entry->update([
            'status' => JournalEntry::STATUS_REVERSED,
            'reversed_at' => now(),
            'reversed_by_user_id' => $overrides['created_by'] ?? null,
        ]);

        return $reversal;
    }

    public static function safePostAdmissionFeeAssigned(Student $student, array $feeData): ?JournalEntry
    {
        return self::safely(function () use ($student, $feeData) {
            $student->loadMissing(['stream.course', 'coursePart']);

            $lines = self::buildReceivableAgainstIncomeLines(
                instituteId: (int) $student->institute_id,
                rawItems: $feeData['items'] ?? [],
                academicSessionId: (int) $student->academic_session_id
            );

            if (empty($lines)) {
                return null;
            }

            return self::post([
                'institute_id' => (int) $student->institute_id,
                'academic_session_id' => (int) $student->academic_session_id,
                'date' => now()->toDateString(),
                'entry_key' => sprintf(
                    self::ENTRY_KEY_ADMISSION_FEE,
                    (int) $student->id,
                    (int) $student->academic_session_id
                ),
                'reference_type' => 'student_admission_fee_due',
                'reference_id' => (int) $student->id,
                'narration' => 'Admission fee due generated for ' . $student->name,
                'created_by' => self::resolveActorId(),
                'created_by_role' => self::resolveActorRole(),
                'meta' => [
                    'student_id' => (int) $student->id,
                    'student_uid' => $student->student_uid,
                    'source' => 'wallet_on_admission',
                ],
            ], $lines);
        });
    }

    public static function safePostCustomFeeAssigned(FeeInvoice $invoice, iterable $items): ?JournalEntry
    {
        return self::safely(function () use ($invoice, $items) {
            $invoice->loadMissing('student');

            $rawItems = collect($items)
                ->filter(fn($item) => !empty($item['is_custom']))
                ->map(function ($item) {
                    return [
                        'fee_type_id' => $item['fee_type_id'] ?? null,
                        'label' => trim((string) ($item['fee_name'] ?? 'Custom Fee')),
                        'amount' => (float) ($item['amount'] ?? 0)
                            + (float) ($item['discount'] ?? 0)
                            + (float) ($item['fine'] ?? 0),
                    ];
                })
                ->filter(fn($item) => (float) $item['amount'] > 0)
                ->values()
                ->all();

            $lines = self::buildReceivableAgainstIncomeLines(
                instituteId: (int) $invoice->institute_id,
                rawItems: $rawItems,
                academicSessionId: (int) $invoice->academic_session_id
            );

            if (empty($lines)) {
                return null;
            }

            return self::post([
                'institute_id' => (int) $invoice->institute_id,
                'academic_session_id' => (int) $invoice->academic_session_id,
                'date' => optional($invoice->payment_date)->toDateString() ?: now()->toDateString(),
                'entry_key' => sprintf(self::ENTRY_KEY_CUSTOM_FEE, (int) $invoice->id),
                'reference_type' => 'fee_invoice_custom_due',
                'reference_id' => (int) $invoice->id,
                'narration' => 'Custom fee due generated for invoice ' . $invoice->invoice_no,
                'created_by' => self::resolveActorId(),
                'created_by_role' => self::resolveActorRole(),
                'meta' => [
                    'invoice_id' => (int) $invoice->id,
                    'student_id' => (int) $invoice->student_id,
                    'source' => 'wallet_custom_fee_charge',
                ],
            ], $lines);
        });
    }

    public static function safePostFineAssigned(FeeInvoice $invoice, iterable $items): ?JournalEntry
    {
        return self::safely(function () use ($invoice, $items) {
            $invoice->loadMissing('student');

            $fineRows = collect($items)
                ->filter(fn($item) => empty($item['is_custom']) && (float) ($item['fine'] ?? 0) > 0)
                ->map(function ($item) {
                    return [
                        'label' => trim((string) ($item['fee_name'] ?? 'Fine')),
                        'amount' => (float) ($item['fine'] ?? 0),
                    ];
                })
                ->values();

            if ($fineRows->isEmpty()) {
                return null;
            }

            $settings = self::settings((int) $invoice->institute_id);
            $receivable = self::accountOrFail($settings?->fees_receivable_account_id);
            $fineIncome = self::fineIncomeAccount((int) $invoice->institute_id, $settings);
            $totalFine = round((float) $fineRows->sum('amount'), 2);

            return self::post([
                'institute_id' => (int) $invoice->institute_id,
                'academic_session_id' => (int) $invoice->academic_session_id,
                'date' => optional($invoice->payment_date)->toDateString() ?: now()->toDateString(),
                'entry_key' => sprintf(self::ENTRY_KEY_FINE_FEE, (int) $invoice->id),
                'reference_type' => 'fee_invoice_fine_due',
                'reference_id' => (int) $invoice->id,
                'narration' => 'Fine due generated for invoice ' . $invoice->invoice_no,
                'created_by' => self::resolveActorId(),
                'created_by_role' => self::resolveActorRole(),
                'meta' => [
                    'invoice_id' => (int) $invoice->id,
                    'student_id' => (int) $invoice->student_id,
                    'source' => 'wallet_fine_charge',
                ],
            ], [
                [
                    'account_id' => (int) $receivable->id,
                    'entry_type' => 'debit',
                    'amount' => $totalFine,
                    'narration' => 'Fine receivable',
                ],
                [
                    'account_id' => (int) $fineIncome->id,
                    'entry_type' => 'credit',
                    'amount' => $totalFine,
                    'narration' => 'Fine income',
                ],
            ]);
        });
    }

    public static function safePostFeeCollection(FeeInvoice $invoice): ?JournalEntry
    {
        return self::safely(function () use ($invoice) {
            $invoice->loadMissing(['student', 'bankAccount']);

            $cashAmount = round((float) $invoice->paid_amount, 2);
            $discountAmount = round((float) ($invoice->discount ?? 0), 2);
            $clearedAmount = round($cashAmount + $discountAmount, 2);

            if ($clearedAmount <= 0) {
                return null;
            }

            $settings = self::settings((int) $invoice->institute_id);
            $receivable = self::accountOrFail($settings?->fees_receivable_account_id);
            $cashOrBank = self::collectionAccount($invoice, $settings);

            $lines = [];
            if ($cashAmount > 0) {
                $lines[] = [
                    'account_id' => (int) $cashOrBank->id,
                    'entry_type' => 'debit',
                    'amount' => $cashAmount,
                    'narration' => 'Cash / bank received',
                ];
            }

            if ($discountAmount > 0) {
                $discountAccount = self::accountOrFail($settings?->discount_allowed_account_id);
                $lines[] = [
                    'account_id' => (int) $discountAccount->id,
                    'entry_type' => 'debit',
                    'amount' => $discountAmount,
                    'narration' => 'Discount allowed',
                ];
            }

            $lines[] = [
                'account_id' => (int) $receivable->id,
                'entry_type' => 'credit',
                'amount' => $clearedAmount,
                'narration' => 'Fee receivable cleared',
            ];

            return self::post([
                'institute_id' => (int) $invoice->institute_id,
                'academic_session_id' => (int) $invoice->academic_session_id,
                'date' => optional($invoice->payment_date)->toDateString() ?: now()->toDateString(),
                'entry_key' => sprintf(self::ENTRY_KEY_FEE_COLLECTION, (int) $invoice->id),
                'reference_type' => 'fee_invoice_collection',
                'reference_id' => (int) $invoice->id,
                'narration' => 'Fee collected from ' . ($invoice->student?->name ?? 'Student') . ' - ' . $invoice->invoice_no,
                'created_by' => self::resolveActorId(),
                'created_by_role' => self::resolveActorRole(),
                'meta' => [
                    'invoice_id' => (int) $invoice->id,
                    'student_id' => (int) $invoice->student_id,
                    'payment_mode' => $invoice->payment_mode,
                    'bank_account_id' => $invoice->bank_account_id,
                ],
            ], $lines);
        });
    }

    public static function safePostLibraryFineCollection(
        int     $instituteId,
        float   $totalAmount,
        string  $paymentMode,
        ?int    $bankAccountId,
        string  $paymentDate,
        string  $receiptNo,
        string  $narration,
        ?int    $academicSessionId = null
    ): ?JournalEntry {
        return self::safely(function () use (
            $instituteId, $totalAmount, $paymentMode, $bankAccountId,
            $paymentDate, $receiptNo, $narration, $academicSessionId
        ) {
            $amount = round($totalAmount, 2);
            if ($amount <= 0) {
                return null;
            }

            $settings    = self::settings($instituteId);
            $cashOrBank  = self::collectionAccountFromPayment($instituteId, $paymentMode, $bankAccountId, $settings);
            $fineIncome  = self::fineIncomeAccount($instituteId, $settings);

            return self::post([
                'institute_id'       => $instituteId,
                'academic_session_id' => $academicSessionId,
                'date'               => $paymentDate,
                'entry_key'          => sprintf(self::ENTRY_KEY_LIBRARY_FINE, $receiptNo),
                'reference_type'     => 'library_fine_collection',
                'narration'          => $narration,
                'created_by'         => self::resolveActorId(),
                'created_by_role'    => self::resolveActorRole(),
                'meta'               => [
                    'receipt_no'   => $receiptNo,
                    'payment_mode' => $paymentMode,
                    'bank_account_id' => $bankAccountId,
                ],
            ], [
                [
                    'account_id' => (int) $cashOrBank->id,
                    'entry_type' => 'debit',
                    'amount'     => $amount,
                    'narration'  => $paymentMode === 'cash' ? 'Cash received' : 'Bank received',
                ],
                [
                    'account_id' => (int) $fineIncome->id,
                    'entry_type' => 'credit',
                    'amount'     => $amount,
                    'narration'  => 'Library fine income',
                ],
            ]);
        });
    }

    public static function safeReverseFeeCancellation(FeeInvoice $invoice): void
    {
        self::safely(function () use ($invoice) {
            $date = optional($invoice->cancelled_at)->toDateString() ?: now()->toDateString();
            $createdBy = $invoice->cancelled_by ?: self::resolveActorId();
            $createdByRole = self::resolveActorRole();

            foreach ([
                sprintf(self::ENTRY_KEY_FEE_COLLECTION, (int) $invoice->id),
                sprintf(self::ENTRY_KEY_CUSTOM_FEE, (int) $invoice->id),
                sprintf(self::ENTRY_KEY_FINE_FEE, (int) $invoice->id),
            ] as $entryKey) {
                self::reverseByEntryKey(
                    instituteId: (int) $invoice->institute_id,
                    entryKey: $entryKey,
                    overrides: [
                        'date' => $date,
                        'created_by' => $createdBy,
                        'created_by_role' => $createdByRole,
                        'narration' => 'Reversal due to invoice cancellation: ' . $invoice->invoice_no,
                    ]
                );
            }

            return null;
        });
    }

    public static function safePostExpense(Expense $expense): ?JournalEntry
    {
        return self::safely(function () use ($expense) {
            $expense->loadMissing(['expenseAccount', 'paymentAccount', 'bankAccount']);

            $amount = round((float) $expense->amount, 2);
            if ($amount <= 0) {
                return null;
            }

            $expenseAccount = self::accountOrFail((int) $expense->expense_account_id);
            $paymentAccount = self::accountOrFail($expense->payment_account_id ? (int) $expense->payment_account_id : null);

            return self::post([
                'institute_id' => (int) $expense->institute_id,
                'academic_session_id' => $expense->academic_session_id ? (int) $expense->academic_session_id : null,
                'date' => optional($expense->expense_date)->toDateString() ?: now()->toDateString(),
                'entry_key' => sprintf(self::ENTRY_KEY_EXPENSE_PAYMENT, (int) $expense->id),
                'reference_type' => 'expense_payment',
                'reference_id' => (int) $expense->id,
                'narration' => 'Expense paid: ' . ($expense->expenseAccount?->name ?? 'Expense'),
                'created_by' => self::resolveActorId(),
                'created_by_role' => self::resolveActorRole(),
                'meta' => [
                    'expense_id' => (int) $expense->id,
                    'payment_mode' => $expense->payment_mode,
                    'bank_account_id' => $expense->bank_account_id,
                    'vendor_name' => $expense->vendor_name,
                    'bill_no' => $expense->bill_no,
                ],
            ], [
                [
                    'account_id' => (int) $expenseAccount->id,
                    'entry_type' => 'debit',
                    'amount' => $amount,
                    'narration' => $expense->description ?: 'Expense recognized',
                ],
                [
                    'account_id' => (int) $paymentAccount->id,
                    'entry_type' => 'credit',
                    'amount' => $amount,
                    'narration' => $expense->payment_mode === 'cash' ? 'Cash paid' : 'Bank paid',
                ],
            ]);
        });
    }

    public static function safePostSalaryPayment(SalaryRecord $salaryRecord): ?JournalEntry
    {
        return self::safely(function () use ($salaryRecord) {
            $salaryRecord->loadMissing(['staffMember', 'expenseAccount', 'paymentAccount', 'bankAccount']);

            $netPay = round((float) $salaryRecord->paid_amount, 2);
            if ($netPay <= 0) {
                return null;
            }

            $instituteId    = (int) $salaryRecord->institute_id;
            $expenseAccount = self::accountOrFail($salaryRecord->expense_account_id ? (int) $salaryRecord->expense_account_id : null);
            $paymentAccount = self::accountOrFail($salaryRecord->payment_account_id ? (int) $salaryRecord->payment_account_id : null);

            // Gross = basic + all allowances (before any deductions)
            $grossSalary   = round((float) $salaryRecord->basic_salary + (float) $salaryRecord->allowances, 2);
            $pfEmployee    = round((float) ($salaryRecord->pf_employee      ?? 0), 2);
            $pfEmployer    = round((float) ($salaryRecord->pf_employer      ?? 0), 2);
            $esiEmployee   = round((float) ($salaryRecord->esi_employee     ?? 0), 2);
            $esiEmployer   = round((float) ($salaryRecord->esi_employer     ?? 0), 2);
            $tds           = round((float) ($salaryRecord->tds              ?? 0), 2);
            $profTax       = round((float) ($salaryRecord->professional_tax ?? 0), 2);
            $loanDeduction = round((float) ($salaryRecord->loan_deduction   ?? 0), 2);

            $lines = [];

            // ── Debits ────────────────────────────────────────────────────
            // Gross salary expense (basic + allowances)
            $lines[] = [
                'account_id' => (int) $expenseAccount->id,
                'entry_type' => 'debit',
                'amount'     => $grossSalary,
                'narration'  => 'Salary expense (gross)',
            ];

            // PF employer expense
            if ($pfEmployer > 0) {
                $pfExpAcc = self::accountByCodeOrFail($instituteId, '3003');
                $lines[] = [
                    'account_id' => (int) $pfExpAcc->id,
                    'entry_type' => 'debit',
                    'amount'     => $pfEmployer,
                    'narration'  => 'PF employer contribution (12%)',
                ];
            }

            // ESI employer expense
            if ($esiEmployer > 0) {
                $esiExpAcc = self::accountByCodeOrFail($instituteId, '3004');
                $lines[] = [
                    'account_id' => (int) $esiExpAcc->id,
                    'entry_type' => 'debit',
                    'amount'     => $esiEmployer,
                    'narration'  => 'ESI employer contribution (3.25%)',
                ];
            }

            // ── Credits ───────────────────────────────────────────────────
            // Net cash/bank paid to employee
            $lines[] = [
                'account_id' => (int) $paymentAccount->id,
                'entry_type' => 'credit',
                'amount'     => $netPay,
                'narration'  => $salaryRecord->payment_mode === 'cash' ? 'Cash salary paid' : 'Bank salary paid',
            ];

            // PF Payable (employee + employer combined — deposited together to EPFO)
            $totalPf = $pfEmployee + $pfEmployer;
            if ($totalPf > 0) {
                $pfPayable = self::accountByCodeOrFail($instituteId, '4002');
                $lines[] = [
                    'account_id' => (int) $pfPayable->id,
                    'entry_type' => 'credit',
                    'amount'     => $totalPf,
                    'narration'  => "PF payable — employee ₹{$pfEmployee} + employer ₹{$pfEmployer}",
                ];
            }

            // ESI Payable (employee + employer combined)
            $totalEsi = $esiEmployee + $esiEmployer;
            if ($totalEsi > 0) {
                $esiPayable = self::accountByCodeOrFail($instituteId, '4003');
                $lines[] = [
                    'account_id' => (int) $esiPayable->id,
                    'entry_type' => 'credit',
                    'amount'     => $totalEsi,
                    'narration'  => "ESI payable — employee ₹{$esiEmployee} + employer ₹{$esiEmployer}",
                ];
            }

            // TDS Payable
            if ($tds > 0) {
                $tdsPayable = self::accountByCodeOrFail($instituteId, '4001');
                $lines[] = [
                    'account_id' => (int) $tdsPayable->id,
                    'entry_type' => 'credit',
                    'amount'     => $tds,
                    'narration'  => 'TDS withheld from salary',
                ];
            }

            // Professional Tax Payable
            if ($profTax > 0) {
                $ptPayable = self::accountByCodeOrFail($instituteId, '4004');
                $lines[] = [
                    'account_id' => (int) $ptPayable->id,
                    'entry_type' => 'credit',
                    'amount'     => $profTax,
                    'narration'  => 'Professional tax withheld',
                ];
            }

            // Staff Advance Receivable (loan/advance recovery)
            if ($loanDeduction > 0) {
                $advanceRec = self::accountByCodeOrFail($instituteId, '1004');
                $lines[] = [
                    'account_id' => (int) $advanceRec->id,
                    'entry_type' => 'credit',
                    'amount'     => $loanDeduction,
                    'narration'  => 'Loan/advance recovered from salary',
                ];
            }

            return self::post([
                'institute_id'       => $instituteId,
                'academic_session_id' => $salaryRecord->academic_session_id ? (int) $salaryRecord->academic_session_id : null,
                'date'               => optional($salaryRecord->payment_date)->toDateString() ?: now()->toDateString(),
                'entry_key'          => sprintf(self::ENTRY_KEY_SALARY_PAYMENT, (int) $salaryRecord->id),
                'reference_type'     => 'salary_payment',
                'reference_id'       => (int) $salaryRecord->id,
                'narration'          => 'Salary paid to ' . ($salaryRecord->staffMember?->name ?? 'Staff'),
                'created_by'         => self::resolveActorId(),
                'created_by_role'    => self::resolveActorRole(),
                'meta' => [
                    'salary_record_id' => (int) $salaryRecord->id,
                    'staff_member_id'  => (int) $salaryRecord->staff_member_id,
                    'salary_month'     => (int) $salaryRecord->salary_month,
                    'salary_year'      => (int) $salaryRecord->salary_year,
                    'payment_mode'     => $salaryRecord->payment_mode,
                    'bank_account_id'  => $salaryRecord->bank_account_id,
                ],
            ], $lines);
        });
    }

    public static function safePostStaffLoanDisbursement(\App\Models\StaffLoan $loan): ?JournalEntry
    {
        return self::safely(function () use ($loan) {
            $loan->loadMissing('staffMember');

            $amount      = round((float) $loan->principal_amount, 2);
            if ($amount <= 0) {
                return null;
            }

            $instituteId = (int) $loan->institute_id;
            $settings    = self::settings($instituteId);
            $advanceRec  = self::accountByCodeOrFail($instituteId, '1004');
            $cashOrBank  = self::accountOrFail($settings?->cash_account_id);

            return self::post([
                'institute_id'    => $instituteId,
                'date'            => now()->toDateString(),
                'entry_key'       => 'staff-loan-disbursement:' . $loan->id,
                'reference_type'  => 'staff_loan',
                'reference_id'    => $loan->id,
                'narration'       => ucfirst($loan->loan_type) . ' disbursed to ' . ($loan->staffMember?->name ?? 'Staff'),
                'created_by'      => self::resolveActorId(),
                'created_by_role' => self::resolveActorRole(),
                'meta'            => ['loan_id' => $loan->id, 'loan_type' => $loan->loan_type],
            ], [
                [
                    'account_id' => (int) $advanceRec->id,
                    'entry_type' => 'debit',
                    'amount'     => $amount,
                    'narration'  => ucfirst($loan->loan_type) . ' receivable created',
                ],
                [
                    'account_id' => (int) $cashOrBank->id,
                    'entry_type' => 'credit',
                    'amount'     => $amount,
                    'narration'  => 'Cash disbursed as ' . $loan->loan_type,
                ],
            ]);
        });
    }

    public static function safeReverseExpense(Expense $expense, ?string $reason = null): ?JournalEntry
    {
        return self::safely(function () use ($expense, $reason) {
            if (!$expense->journal_entry_id) {
                return null;
            }

            $entry = JournalEntry::find($expense->journal_entry_id);
            if (!$entry) {
                return null;
            }

            return self::reverse($entry, [
                'date' => now()->toDateString(),
                'created_by' => self::resolveActorId(),
                'created_by_role' => self::resolveActorRole(),
                'narration' => 'Expense reversal' . ($reason ? ': ' . $reason : ''),
                'meta' => [
                    'reason' => $reason,
                    'source' => 'expense_reversal',
                    'expense_id' => (int) $expense->id,
                ],
            ]);
        });
    }

    public static function safeReverseSalaryPayment(SalaryRecord $salaryRecord, ?string $reason = null): ?JournalEntry
    {
        return self::safely(function () use ($salaryRecord, $reason) {
            if (!$salaryRecord->journal_entry_id) {
                return null;
            }

            $entry = JournalEntry::find($salaryRecord->journal_entry_id);
            if (!$entry) {
                return null;
            }

            return self::reverse($entry, [
                'date' => now()->toDateString(),
                'created_by' => self::resolveActorId(),
                'created_by_role' => self::resolveActorRole(),
                'narration' => 'Salary reversal' . ($reason ? ': ' . $reason : ''),
                'meta' => [
                    'reason' => $reason,
                    'source' => 'salary_reversal',
                    'salary_record_id' => (int) $salaryRecord->id,
                ],
            ]);
        });
    }

    private static function normalizeLines(array $lines): array
    {
        $normalized = [];

        foreach ($lines as $line) {
            $accountId = (int) ($line['account_id'] ?? 0);
            $entryType = strtolower((string) ($line['entry_type'] ?? ''));
            $amount = round((float) ($line['amount'] ?? 0), 2);

            if ($accountId <= 0 || !in_array($entryType, ['debit', 'credit'], true) || $amount <= 0) {
                continue;
            }

            $normalized[] = [
                'account_id' => $accountId,
                'entry_type' => $entryType,
                'amount' => $amount,
                'narration' => $line['narration'] ?? null,
                'meta' => $line['meta'] ?? null,
            ];
        }

        if (empty($normalized)) {
            throw new InvalidArgumentException('Journal entry must contain at least one valid line.');
        }

        return $normalized;
    }

    private static function assertBalanced(array $lines): void
    {
        $debit = round(collect($lines)->where('entry_type', 'debit')->sum('amount'), 2);
        $credit = round(collect($lines)->where('entry_type', 'credit')->sum('amount'), 2);

        if ($debit <= 0 || $credit <= 0 || abs($debit - $credit) > 0.009) {
            throw new InvalidArgumentException('Journal entry is not balanced.');
        }
    }

    private static function assertAccountOwnership(int $instituteId, array $lines): void
    {
        $accountIds = collect($lines)->pluck('account_id')->unique()->values();
        $matched = Account::where('institute_id', $instituteId)
            ->whereIn('id', $accountIds)
            ->count();

        if ($matched !== $accountIds->count()) {
            throw new InvalidArgumentException('One or more accounts do not belong to the selected institute.');
        }
    }

    private static function buildReceivableAgainstIncomeLines(int $instituteId, array $rawItems, int $academicSessionId): array
    {
        $settings = self::settings($instituteId);
        $receivable = self::accountOrFail($settings?->fees_receivable_account_id);
        $groupedCredits = [];

        foreach ($rawItems as $item) {
            $amount = round((float) ($item['amount'] ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            $account = self::incomeAccountForItem($instituteId, $item, $settings);
            $accountId = (int) $account->id;
            $groupedCredits[$accountId] = ($groupedCredits[$accountId] ?? 0) + $amount;
        }

        if (empty($groupedCredits)) {
            return [];
        }

        $lines = [[
            'account_id' => (int) $receivable->id,
            'entry_type' => 'debit',
            'amount' => round((float) array_sum($groupedCredits), 2),
            'narration' => 'Fee receivable created',
            'meta' => ['academic_session_id' => $academicSessionId],
        ]];

        foreach ($groupedCredits as $accountId => $amount) {
            $lines[] = [
                'account_id' => $accountId,
                'entry_type' => 'credit',
                'amount' => round((float) $amount, 2),
                'narration' => 'Fee income recognized',
                'meta' => ['academic_session_id' => $academicSessionId],
            ];
        }

        return $lines;
    }

    private static function incomeAccountForItem(int $instituteId, array $item, ?FinanceSetting $settings = null): Account
    {
        $feeTypeId = !empty($item['fee_type_id']) ? (int) $item['fee_type_id'] : null;
        if ($feeTypeId) {
            $feeType = FeeType::find($feeTypeId);
            if ($feeType?->income_account_id) {
                return self::accountOrFail((int) $feeType->income_account_id);
            }
        }

        $label = strtolower(trim((string) ($item['label'] ?? $item['fee_name'] ?? '')));
        $itemType = strtolower(trim((string) ($item['type'] ?? '')));
        $targetCode = match (true) {
            str_contains($label, 'registration') => '2001',
            in_array($itemType, ['course', 'course_assignment'], true), str_contains($label, 'course') => '2002',
            in_array($itemType, ['subject', 'subject_assignment', 'practical', 'subject_combined', 'practical_combined'], true),
                str_contains($label, 'subject'),
                str_contains($label, 'practical') => '2003',
            str_contains($label, 'exam'), str_contains($label, 'admit') => '2004',
            str_contains($label, 'fine'), str_contains($label, 'late') => '2005',
            default => '2300',
        };

        return self::accountByCodeOrFail($instituteId, $targetCode);
    }

    private static function collectionAccount(FeeInvoice $invoice, ?FinanceSetting $settings = null): Account
    {
        if ($invoice->payment_mode === 'cash') {
            return self::accountOrFail($settings?->cash_account_id);
        }

        if ($invoice->bankAccount?->gl_account_id) {
            return self::accountOrFail((int) $invoice->bankAccount->gl_account_id);
        }

        return self::accountByCodeOrFail((int) $invoice->institute_id, '1001');
    }

    private static function collectionAccountFromPayment(
        int            $instituteId,
        string         $paymentMode,
        ?int           $bankAccountId,
        ?FinanceSetting $settings = null
    ): Account {
        if ($paymentMode === 'cash') {
            return self::accountOrFail($settings?->cash_account_id);
        }

        if ($bankAccountId) {
            $bank = InstituteBankAccount::find($bankAccountId);
            if ($bank?->gl_account_id) {
                return self::accountOrFail((int) $bank->gl_account_id);
            }
        }

        return self::accountByCodeOrFail($instituteId, '1001');
    }

    private static function fineIncomeAccount(int $instituteId, ?FinanceSetting $settings = null): Account
    {
        if ($settings?->fine_income_account_id) {
            return self::accountOrFail((int) $settings->fine_income_account_id);
        }

        return self::accountByCodeOrFail($instituteId, '2005');
    }

    private static function reverseByEntryKey(int $instituteId, string $entryKey, array $overrides = []): ?JournalEntry
    {
        $entry = JournalEntry::where('institute_id', $instituteId)
            ->where('entry_key', $entryKey)
            ->first();

        if (!$entry) {
            return null;
        }

        return self::reverse($entry, $overrides + [
            'entry_key' => $entryKey . ':reversal',
        ]);
    }

    private static function settings(int $instituteId): ?FinanceSetting
    {
        $settings = FinanceSetting::where('institute_id', $instituteId)->first();
        if ($settings) {
            return $settings;
        }

        AccountingSetupService::bootstrapInstitute($instituteId);

        return FinanceSetting::where('institute_id', $instituteId)->first();
    }

    private static function accountByCodeOrFail(int $instituteId, string $code): Account
    {
        $account = Account::where('institute_id', $instituteId)
            ->where('code', $code)
            ->first();

        if (!$account) {
            throw new InvalidArgumentException("Required account {$code} is not configured for institute {$instituteId}.");
        }

        return $account;
    }

    private static function accountOrFail(?int $accountId): Account
    {
        if (!$accountId) {
            throw new InvalidArgumentException('Required accounting mapping is missing.');
        }

        $account = Account::find($accountId);
        if (!$account) {
            throw new InvalidArgumentException("Mapped account {$accountId} does not exist.");
        }

        return $account;
    }

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

    private static function resolveActorRole(): ?string
    {
        foreach (['staff', 'center', 'partner', 'web'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }

    private static function safely(callable $callback)
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
