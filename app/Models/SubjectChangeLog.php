<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectChangeLog extends Model
{
    protected $fillable = [
        'student_id',
        'institute_id',
        'academic_session_id',
        'year_number',
        'semester',
        'subject_id',
        'subject_name',
        'subject_code',
        'action',
        'previous_role',
        'new_role',
        'subject_fee',
        'practical_fee',
        'total_fee_impact',
        'paid_portion',
        'unpaid_portion',
        'adjustment_type',
        'transaction_id',
        'by_user_id',
        'actor_type',
        'actor_name',
        'notes',
    ];

    protected $casts = [
        'subject_fee'      => 'float',
        'practical_fee'    => 'float',
        'total_fee_impact' => 'float',
        'paid_portion'     => 'float',
        'unpaid_portion'   => 'float',
        'year_number'      => 'integer',
        'semester'         => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(StudentTransaction::class, 'transaction_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function getActionLabelAttribute(): string
    {
        return $this->action === 'added' ? 'Subject Added' : 'Subject Removed';
    }

    public function getAdjustmentLabelAttribute(): string
    {
        return match ($this->adjustment_type) {
            'debit'         => 'Fee Charged',
            'credit_cancel' => 'Fee Cancelled (Unpaid)',
            'credit_note'   => 'Credit Note (Paid)',
            default         => '—',
        };
    }

    public function getFeeImpactSignedAttribute(): float
    {
        return (float) $this->total_fee_impact;
    }
}
