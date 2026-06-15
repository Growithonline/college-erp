<?php

namespace App\Models\Library;

use App\Models\AcademicSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class LibraryTransaction extends Model
{
    protected $fillable = [
        'institute_id',
        'library_member_id',
        'library_book_copy_id',
        'academic_session_id',
        'txn_type',
        'current_status',
        'renew_count',
        'issued_on',
        'due_on',
        'returned_on',
        'loan_days_snapshot',
        'fine_per_day_snapshot',
        'grace_days_snapshot',
        'max_renewals_snapshot',
        'rule_name_snapshot',
        'fine_amount',
        'fine_paid',
        'remarks',
        'issued_by',
        'returned_by',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'due_on' => 'date',
        'returned_on' => 'date',
        'loan_days_snapshot' => 'integer',
        'fine_per_day_snapshot' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'fine_paid' => 'decimal:2',
    ];

    public function scopeForInstitute($query, int $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }

    public function member()
    {
        return $this->belongsTo(LibraryMember::class, 'library_member_id');
    }

    public function copy()
    {
        return $this->belongsTo(LibraryBookCopy::class, 'library_book_copy_id');
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function finePayments()
    {
        return $this->hasMany(LibraryFinePayment::class, 'library_transaction_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->current_status !== 'issued' || !$this->due_on) {
            return false;
        }

        return Carbon::today()->gt($this->due_on);
    }

    public function getOutstandingFineAttribute(): float
    {
        return max(0, (float) $this->fine_amount - (float) $this->fine_paid);
    }
}
