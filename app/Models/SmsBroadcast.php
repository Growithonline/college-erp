<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsBroadcast extends Model
{
    const AUDIENCE_STAFF   = 'staff';
    const AUDIENCE_STUDENT = 'student';

    const RECIPIENT_MODE_ALL      = 'all';
    const RECIPIENT_MODE_SPECIFIC = 'specific';

    const STATUS_DRAFT   = 'draft';
    const STATUS_QUEUED  = 'queued';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT    = 'sent';
    const STATUS_FAILED  = 'failed';
    const STATUS_PARTIAL = 'partial';

    protected $fillable = [
        'institute_id',
        'sms_template_id',
        'template_values',
        'audience_type',
        'target_course_ids',
        'target_stream_ids',
        'target_semesters',
        'target_staff_role_ids',
        'recipient_mode',
        'specific_recipient_ids',
        'notice_title',
        'notice_body',
        'linked_notice_id',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'created_by_user_id',
        'sent_at',
    ];

    protected $casts = [
        'template_values'         => 'array',
        'target_course_ids'       => 'array',
        'target_stream_ids'       => 'array',
        'target_semesters'        => 'array',
        'target_staff_role_ids'   => 'array',
        'specific_recipient_ids'  => 'array',
        'sent_at'                 => 'datetime',
    ];

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function smsTemplate(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class);
    }

    public function linkedNotice(): BelongsTo
    {
        return $this->belongsTo(Notice::class, 'linked_notice_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'sms_broadcast_id');
    }

    // The actual recipient pool for this (already persisted) broadcast — same resolver the
    // compose-page live count preview uses, so what the admin saw before sending is what sends.
    public function resolveRecipientQuery()
    {
        $query = \App\Services\SmsBroadcastTargeting::baseQuery($this->institute_id, $this->audience_type);

        return \App\Services\SmsBroadcastTargeting::applyFilters($query, $this->audience_type, [
            'target_course_ids'      => $this->target_course_ids,
            'target_stream_ids'      => $this->target_stream_ids,
            'target_semesters'       => $this->target_semesters,
            'target_staff_role_ids'  => $this->target_staff_role_ids,
            'recipient_mode'         => $this->recipient_mode,
            'specific_recipient_ids' => $this->specific_recipient_ids,
        ]);
    }
}
