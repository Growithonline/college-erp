<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    // approval_status values
    public const STATUS_AUTO_APPROVED = 'auto_approved';
    public const STATUS_PENDING       = 'pending';
    public const STATUS_APPROVED      = 'approved';
    public const STATUS_REJECTED      = 'rejected';

    protected $fillable = [
        'institute_id',
        'academic_session_id',
        'expense_account_id',
        'payment_account_id',
        'bank_account_id',
        'journal_entry_id',
        'is_reversed',
        'reversal_journal_entry_id',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'expense_date',
        'amount',
        'payment_mode',
        'vendor_name',
        'bill_no',
        'description',
        'attachment_path',
        'approved_by',
        'created_by',
        // Phase 4-6 wallet fields
        'expense_category_l1_id',
        'expense_category_l2_id',
        'expense_vendor_id',
        'approval_status',
        'approved_by_staff_id',
        'approved_at',
        'approval_rejection_reason',
        'wallet_debited',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount'       => 'decimal:2',
        'is_reversed'  => 'boolean',
        'reversed_at'  => 'datetime',
        'approved_at'  => 'datetime',
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

    public function categoryL1(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategoryL1::class, 'expense_category_l1_id');
    }

    public function categoryL2(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategoryL2::class, 'expense_category_l2_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(ExpenseVendor::class, 'expense_vendor_id');
    }

    public function approverStaff(): BelongsTo
    {
        return $this->belongsTo(\App\Models\StaffMember::class, 'approved_by_staff_id');
    }

    public function isPending(): bool
    {
        return $this->approval_status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return in_array($this->approval_status, [self::STATUS_AUTO_APPROVED, self::STATUS_APPROVED], true);
    }
}
