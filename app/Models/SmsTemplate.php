<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsTemplate extends Model
{
    const TYPE_OTP               = 'otp';
    const TYPE_FEE_DUE_REMINDER  = 'fee_due_reminder';
    const TYPE_FEE_TXN_ALERT     = 'fee_transaction_alert';
    const TYPE_ADMISSION_ALERT   = 'admission_alert';
    const TYPE_NOTICE            = 'notice';
    const TYPE_EXAM_INFO         = 'exam_info';
    const TYPE_ADMIT_CARD        = 'admit_card';
    const TYPE_PROMOTION         = 'promotion';

    const CATEGORY_TRANSACTIONAL = 'transactional';
    const CATEGORY_PROMOTIONAL   = 'promotional';

    protected $fillable = [
        'institute_id',
        'type',
        'category',
        'dlt_template_id',
        'content',
        'variable_names',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function getVariableNamesArrayAttribute(): array
    {
        if (empty($this->variable_names)) return [];
        $decoded = json_decode($this->variable_names, true);
        return is_array($decoded) ? $decoded : [];
    }
}
