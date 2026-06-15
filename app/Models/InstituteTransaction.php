<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteTransaction extends Model
{
    protected $fillable = [
        'institute_id', 'academic_session_id',
        'des', 'credit', 'debit', 'type', 'date',
        'op_bal', 'cl_bal', 'fee_invoice_id', 'by_user_id',
        'source_type', 'source_id',
    ];

    protected $casts = ['date' => 'date'];

    public const DEBIT  = 1;
    public const CREDIT = 2;

    public function institute() { return $this->belongsTo(Institute::class); }
    public function session()   { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
    public function invoice()   { return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id'); }
}