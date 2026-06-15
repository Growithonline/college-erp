<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    const TYPE_OTP               = 'otp';
    const TYPE_NOTICE            = 'notice';
    const TYPE_DUE_REMINDER      = 'due_reminder';
    const TYPE_BROADCAST         = 'broadcast';
    const TYPE_WELCOME           = 'welcome';

    const STATUS_PENDING = 'pending';
    const STATUS_SENT    = 'sent';
    const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'institute_id',
        'type',
        'mobile',
        'message',
        'provider',
        'sender_id',
        'status',
        'provider_response',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }
}
