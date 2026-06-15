<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContraEntry extends Model
{
    protected $fillable = [
        'institute_id', 'academic_session_id', 'entry_date',
        'amount', 'to_bank_account_id', 'slip_no', 'description', 'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount'     => 'decimal:2',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(InstituteBankAccount::class, 'to_bank_account_id');
    }
}
