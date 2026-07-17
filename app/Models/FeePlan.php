<?php

namespace App\Models;

use App\Services\WalletService;
use Illuminate\Database\Eloquent\Model;

class FeePlan extends Model
{
    protected $fillable = [
        'institute_id', 'course_id', 'name', 'installment_count', 'description', 'is_active',
    ];

    protected $casts = [
        'installment_count' => 'integer',
        'is_active'         => 'boolean',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function installments()
    {
        return $this->hasMany(FeePlanInstallment::class)->orderBy('installment_number');
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    // Given a total fee amount, return each installment's due amount
    public function installmentAmounts(float $totalFee): array
    {
        $amounts = [];
        $allocated = 0.0;

        $installments = $this->installments->values();
        foreach ($installments as $i => $inst) {
            if ($i === $installments->count() - 1) {
                // last installment gets the remainder to avoid rounding drift
                $amounts[$inst->installment_number] = round($totalFee - $allocated, 2);
            } else {
                $amt = round($totalFee * $inst->percentage / 100, 2);
                $amounts[$inst->installment_number] = $amt;
                $allocated += $amt;
            }
        }

        return $amounts;
    }

    // Sum of installments that are due right now (currently: only 'at_admission', which is
    // always due — 'semester_start'/'months_after' installments never apply at admission time)
    public function dueNowAmount(Student $student): float
    {
        $totalFee = WalletService::getOriginalFeeCharged($student->id, (int) $student->academic_session_id);
        if ($totalFee <= 0) {
            return 0.0;
        }

        $amounts = $this->installmentAmounts($totalFee);
        $due = 0.0;
        foreach ($this->installments as $installment) {
            if ($installment->due_trigger === 'at_admission') {
                $due += $amounts[$installment->installment_number] ?? 0;
            }
        }

        return round($due, 2);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeForInstitute($q, int $instituteId)
    {
        return $q->where('institute_id', $instituteId);
    }
}
