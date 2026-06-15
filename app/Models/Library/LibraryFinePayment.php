<?php

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

class LibraryFinePayment extends Model
{
    protected $fillable = [
        'institute_id',
        'library_member_id',
        'library_transaction_id',
        'amount',
        'payment_mode',
        'bank_account_id',
        'transaction_ref',
        'bank_name',
        'payment_date',
        'payment_datetime',
        'receipt_no',
        'remarks',
        'collected_by',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'payment_date'     => 'date',
        'payment_datetime' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(LibraryMember::class, 'library_member_id');
    }

    public function transaction()
    {
        return $this->belongsTo(LibraryTransaction::class, 'library_transaction_id');
    }
}
