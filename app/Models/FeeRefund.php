<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeRefund extends Model
{
    public const PENDING   = 'pending';
    public const APPROVED  = 'approved';
    public const PAID      = 'paid';
    public const REJECTED  = 'rejected';
    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'institute_id', 'student_id', 'academic_session_id', 'promotion_log_id', 'amount', 'status',
        'reason', 'approved_by', 'approved_at', 'payment_mode', 'bank_account_id', 'reference_no',
        'voucher_no', 'paid_on', 'paid_by', 'journal_entry_id', 'remarks',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_on'     => 'date',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function session() { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
    public function bankAccount() { return $this->belongsTo(InstituteBankAccount::class, 'bank_account_id'); }
}
