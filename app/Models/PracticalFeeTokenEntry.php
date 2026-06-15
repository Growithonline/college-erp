<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticalFeeTokenEntry extends Model
{
    protected $fillable = [
        'batch_id',
        'student_id',
        'fee_invoice_id',
        'amount',
        'fine',
        'discount',
        'status',
        'entered_by_type',
        'entered_by_id',
        'posted_at',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'fine'      => 'decimal:2',
        'discount'  => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(PracticalFeeTokenBatch::class, 'batch_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice()
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    // entered_by_type: 'staff' => StaffMember, 'admin' => User
    public function getEnteredByNameAttribute(): string
    {
        if (!$this->entered_by_id) return '—';
        if ($this->entered_by_type === 'staff') {
            return StaffMember::find($this->entered_by_id)?->name ?? '—';
        }
        return User::find($this->entered_by_id)?->name ?? '—';
    }
}
