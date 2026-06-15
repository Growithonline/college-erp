<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteBankAccount extends Model
{
    protected $fillable = [
        'institute_id', 'bank_name', 'account_name', 'account_no',
        'ifsc_code', 'branch', 'upi_id', 'display_label',
        'allowed_payment_modes', 'is_active', 'sort_order', 'gl_account_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function glAccount()
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    // Display name for dropdown
    public function getDisplayNameAttribute(): string
    {
        $label = $this->display_label ?: $this->bank_name;
        return "{$label} — {$this->account_no}";
    }
}
