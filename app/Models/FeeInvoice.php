<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeInvoice extends Model
{
    protected $fillable = [
        'institute_id', 'student_id', 'academic_session_id', 'semester',
        'invoice_no', 'total_amount', 'discount', 'paid_amount',
        'payment_mode', 'bank_account_id', 'transaction_ref', 'bank_name',
        'payment_date', 'payment_datetime', 'remarks',
        'collected_by', 'collected_by_staff_id', 'collected_by_center_id', 'collected_by_partner_id',
        'is_cancelled', 'cancel_reason', 'cancelled_at', 'cancelled_by',
        'remaining_due',
    ];

    protected $casts = [
        'total_amount'  => 'decimal:2',
        'discount'      => 'decimal:2',
        'paid_amount'   => 'decimal:2',
        'remaining_due' => 'decimal:2',
        'payment_date'     => 'date',
        'payment_datetime' => 'datetime',
        'is_cancelled'   => 'boolean',
        'cancelled_at'   => 'datetime',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(\App\Models\InstituteBankAccount::class, 'bank_account_id');
    }

    public function collectedByStaff()
    {
        return $this->belongsTo(StaffMember::class, 'collected_by_staff_id');
    }

    public function collectedByCenter()
    {
        return $this->belongsTo(Center::class, 'collected_by_center_id');
    }

    public function collectedByPartner()
    {
        return $this->belongsTo(ChannelPartner::class, 'collected_by_partner_id');
    }

    public function commissionEntry()
    {
        return $this->hasOne(PartnerCommissionEntry::class, 'fee_invoice_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_cancelled', false);
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function items()
    {
        return $this->hasMany(FeeInvoiceItem::class);
    }
}
