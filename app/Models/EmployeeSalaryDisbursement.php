<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryDisbursement extends Model
{
    protected $fillable = [
        'institute_id', 'employee_id', 'month', 'year',
        'basic_paid', 'total_allowances', 'gross_salary', 'deductions', 'net_salary',
        'payment_date', 'payment_mode', 'status', 'remarks',
        'expense_account_id', 'payment_account_id', 'bank_account_id',
        'journal_entry_id', 'wallet_debited', 'components_snapshot',
        'reversed_at', 'reversal_reason', 'reversal_journal_entry_id',
    ];

    protected $casts = [
        'basic_paid'          => 'decimal:2',
        'total_allowances'    => 'decimal:2',
        'gross_salary'        => 'decimal:2',
        'deductions'          => 'decimal:2',
        'net_salary'          => 'decimal:2',
        'payment_date'        => 'date',
        'wallet_debited'      => 'boolean',
        'components_snapshot' => 'array',
        'reversed_at'         => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function paymentAccount()
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(InstituteBankAccount::class, 'bank_account_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalJournalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }

    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }
}
