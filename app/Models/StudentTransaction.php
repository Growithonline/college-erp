<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentTransaction extends Model
{
    protected $fillable = [
        'student_id', 'institute_id', 'academic_session_id',
        'des', 'credit', 'debit', 'type', 'date',
        'op_bal', 'cl_bal', 'fee_invoice_id', 'promotion_log_id',
        'transport_allocation_id', 'transport_payment_id', 'by_user_id',
    ];

    protected $casts = ['date' => 'date'];

    const DEBIT  = 1;
    const CREDIT = 2;

    public function student()  { return $this->belongsTo(Student::class); }
    public function session()  { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
    public function invoice()  { return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id'); }
    public function promotionLog() { return $this->belongsTo(PromotionLog::class); }
    public function transportAllocation() { return $this->belongsTo(TransportAllocation::class, 'transport_allocation_id'); }
    public function transportPayment() { return $this->belongsTo(TransportPayment::class, 'transport_payment_id'); }
}
