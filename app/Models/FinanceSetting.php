<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceSetting extends Model
{
    protected $fillable = [
        'institute_id',
        'fees_receivable_account_id',
        'student_advance_account_id',
        'discount_allowed_account_id',
        'cash_account_id',
        'fine_income_account_id',
        'rounding_adjustment_account_id',
        'wallet_low_balance_threshold',
        'locked_before_date',
    ];

    protected $casts = [
        'wallet_low_balance_threshold' => 'decimal:2',
        'locked_before_date' => 'date',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    /**
     * Whether $date falls on/before this institute's period-lock cutoff
     * (i.e. transactions on that date can no longer be reversed).
     */
    public static function isDateLocked(int $instituteId, $date): bool
    {
        if (!$date) {
            return false;
        }

        $lockDate = self::where('institute_id', $instituteId)->value('locked_before_date');

        if (!$lockDate) {
            return false;
        }

        return \Carbon\Carbon::parse($date)->lte(\Carbon\Carbon::parse($lockDate));
    }

    public function feesReceivableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'fees_receivable_account_id');
    }

    public function studentAdvanceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'student_advance_account_id');
    }

    public function discountAllowedAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'discount_allowed_account_id');
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }

    public function fineIncomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'fine_income_account_id');
    }

    public function roundingAdjustmentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'rounding_adjustment_account_id');
    }
}
