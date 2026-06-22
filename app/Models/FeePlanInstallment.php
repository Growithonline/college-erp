<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeePlanInstallment extends Model
{
    protected $fillable = [
        'fee_plan_id', 'installment_number', 'label', 'percentage',
        'due_trigger', 'due_semester', 'due_months_after',
    ];

    protected $casts = [
        'installment_number' => 'integer',
        'percentage'         => 'decimal:2',
        'due_semester'       => 'integer',
        'due_months_after'   => 'integer',
    ];

    public function feePlan()
    {
        return $this->belongsTo(FeePlan::class);
    }

    public function dueTriggerLabel(): string
    {
        return match ($this->due_trigger) {
            'at_admission'   => 'At Admission',
            'semester_start' => 'Sem ' . $this->due_semester . ' Start',
            'months_after'   => 'After ' . $this->due_months_after . ' Month(s)',
            default          => ucfirst($this->due_trigger),
        };
    }
}
