<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportPayment extends Model
{
    protected $fillable = [
        'transport_allocation_id',
        'student_id',
        'institute_id',
        'academic_session_id',
        'amount',
        'payment_date',
        'payment_mode',
        'reference_no',
        'note',
        'by_user_id',
        'student_transaction_id',
        'fee_invoice_id',
        'is_reversed',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function allocation()
    {
        return $this->belongsTo(TransportAllocation::class, 'transport_allocation_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function transaction()
    {
        return $this->belongsTo(StudentTransaction::class, 'student_transaction_id');
    }
}
