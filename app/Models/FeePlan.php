<?php

namespace App\Models;

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

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeForInstitute($q, int $instituteId)
    {
        return $q->where('institute_id', $instituteId);
    }
}
