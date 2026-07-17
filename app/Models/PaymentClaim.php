<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentClaim extends Model
{
    protected $fillable = [
        'institute_id', 'student_id', 'amount_due', 'amount_claimed',
        'payment_mode', 'transaction_ref', 'screenshot_path', 'bank_account_id',
        'verification_status', 'verified_by', 'verified_at', 'rejection_reason',
        'fee_invoice_id', 'recorded_by_staff_id',
    ];

    protected $casts = [
        'amount_due'     => 'decimal:2',
        'amount_claimed' => 'decimal:2',
        'verified_at'    => 'datetime',
    ];

    public function institute()    { return $this->belongsTo(Institute::class); }
    public function student()      { return $this->belongsTo(Student::class); }
    public function bankAccount()  { return $this->belongsTo(InstituteBankAccount::class, 'bank_account_id'); }
    public function feeInvoice()   { return $this->belongsTo(FeeInvoice::class); }
    public function verifiedBy()   { return $this->belongsTo(StaffMember::class, 'verified_by'); }
    public function recordedBy()   { return $this->belongsTo(StaffMember::class, 'recorded_by_staff_id'); }

    public function isPending(): bool  { return $this->verification_status === 'pending'; }
    public function isApproved(): bool { return $this->verification_status === 'approved'; }
    public function isRejected(): bool { return $this->verification_status === 'rejected'; }

    public function scopePending($q)  { return $q->where('verification_status', 'pending'); }
    public function scopeApproved($q) { return $q->where('verification_status', 'approved'); }
    public function scopeRejected($q) { return $q->where('verification_status', 'rejected'); }
}
