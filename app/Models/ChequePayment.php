<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChequePayment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CLEARED = 'cleared';
    public const STATUS_BOUNCED = 'bounced';

    protected $fillable = [
        'institute_id', 'academic_session_id', 'fee_invoice_id',
        'cheque_no', 'drawee_bank', 'cheque_date', 'amount',
        'status', 'clearance_date', 'bounce_reason', 'remarks',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'cheque_date'    => 'date',
        'clearance_date' => 'date',
        'amount'         => 'decimal:2',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    public function isPending(): bool { return $this->status === self::STATUS_PENDING; }
    public function isCleared(): bool { return $this->status === self::STATUS_CLEARED; }
    public function isBounced(): bool { return $this->status === self::STATUS_BOUNCED; }

    public function scopePending($q)  { return $q->where('status', self::STATUS_PENDING); }
    public function scopeCleared($q)  { return $q->where('status', self::STATUS_CLEARED); }
    public function scopeBounced($q)  { return $q->where('status', self::STATUS_BOUNCED); }
}
