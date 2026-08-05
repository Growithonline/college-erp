<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class InstituteMasterOtp extends Model
{
    protected $fillable = [
        'institute_id', 'otp_encrypted', 'valid_month', 'generated_at', 'generated_by',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function isValidNow(): bool
    {
        return $this->valid_month === now()->format('Y-m');
    }

    public function decryptOtp(): string
    {
        return Crypt::decryptString($this->otp_encrypted);
    }
}
