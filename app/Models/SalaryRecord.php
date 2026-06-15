<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryRecord extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'institute_id',
        'academic_session_id',
        'staff_member_id',
        'expense_account_id',
        'payment_account_id',
        'bank_account_id',
        'journal_entry_id',
        'reversal_journal_entry_id',
        'salary_month',
        'salary_year',
        'basic_salary',
        'allowances',
        'hra', 'da', 'ta', 'medical', 'overtime_amount',
        'deductions',
        'absence_deduction', 'pf_employee', 'pf_employer',
        'esi_employee', 'esi_employer', 'tds', 'professional_tax', 'loan_deduction',
        'net_payable',
        'paid_amount',
        'payment_date',
        'payment_mode',
        'remarks',
        'status',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'created_by',
        'wallet_debited',
    ];

    protected $casts = [
        'basic_salary'      => 'decimal:2',
        'allowances'        => 'decimal:2',
        'hra'               => 'decimal:2',
        'da'                => 'decimal:2',
        'ta'                => 'decimal:2',
        'medical'           => 'decimal:2',
        'overtime_amount'   => 'decimal:2',
        'deductions'        => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'pf_employee'       => 'decimal:2',
        'pf_employer'       => 'decimal:2',
        'esi_employee'      => 'decimal:2',
        'esi_employer'      => 'decimal:2',
        'tds'               => 'decimal:2',
        'professional_tax'  => 'decimal:2',
        'loan_deduction'    => 'decimal:2',
        'net_payable'       => 'decimal:2',
        'paid_amount'       => 'decimal:2',
        'payment_date'   => 'date',
        'reversed_at'    => 'datetime',
        'wallet_debited' => 'boolean',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(InstituteBankAccount::class, 'bank_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }
}
