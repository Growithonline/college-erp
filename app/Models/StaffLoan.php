<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffLoan extends Model
{
    protected $table = 'staff_loans';

    protected $fillable = [
        'institute_id', 'staff_member_id', 'loan_type',
        'principal_amount', 'outstanding_amount', 'monthly_deduction',
        'start_month', 'start_year', 'status', 'purpose',
        'approved_by', 'created_by',
    ];

    protected $casts = [
        'principal_amount'  => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'monthly_deduction' => 'decimal:2',
        'start_month'       => 'integer',
        'start_year'        => 'integer',
    ];

    public const TYPE_ADVANCE = 'advance';
    public const TYPE_LOAN    = 'loan';

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Deduct one EMI — call when salary is paid
    public function deductEmi(): void
    {
        $remaining = max(0, (float) $this->outstanding_amount - (float) $this->monthly_deduction);
        $this->outstanding_amount = $remaining;
        if ($remaining <= 0) {
            $this->status = self::STATUS_COMPLETED;
        }
        $this->save();
    }

    public static function getActiveLoansForStaff(int $staffId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('staff_member_id', $staffId)
            ->where('status', self::STATUS_ACTIVE)
            ->get();
    }
}
