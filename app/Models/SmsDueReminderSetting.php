<?php

namespace App\Models;

use App\Services\SmsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsDueReminderSetting extends Model
{
    protected $fillable = [
        'institute_id',
        'is_enabled',
        'trigger_days',
        'message_template',
        'send_time',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public static function defaultTemplate(): string
    {
        return 'Dear {name}, your fee of Rs.{amount} is pending. Please pay at the earliest. -{institute_name}';
    }

    public function getTriggerDaysArrayAttribute(): array
    {
        return array_map('intval', explode(',', $this->trigger_days ?? '0,3,7'));
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    // Shared by the scheduled cron (SendDueReminders) and any manual "send reminder now"
    // trigger — prefers the new sms_templates row (DLT-compliant on Fast2SMS) when the
    // institute has configured one, otherwise falls back to this table's free-text template.
    public static function sendReminder(Student $student, float $amount, \Carbon\Carbon $dueDate): bool
    {
        if (! $student->mobile) {
            return false;
        }

        $vars = [
            'name'           => $student->name,
            'amount'         => number_format($amount, 0),
            'due_date'       => $dueDate->format('d M Y'),
            'institute_name' => $student->institute?->name ?? '',
            'course'         => $student->stream?->course?->name ?? '',
        ];

        $hasNewTemplate = SmsTemplate::where('institute_id', $student->institute_id)
            ->where('type', SmsTemplate::TYPE_FEE_DUE_REMINDER)
            ->where('is_active', true)
            ->exists();

        if ($hasNewTemplate) {
            return SmsService::sendTemplated($student->institute_id, $student->mobile, SmsTemplate::TYPE_FEE_DUE_REMINDER, $vars);
        }

        $setting  = self::where('institute_id', $student->institute_id)->first();
        $template = $setting?->message_template ?? self::defaultTemplate();
        $message  = strtr($template, [
            '{name}'           => $vars['name'],
            '{amount}'         => $vars['amount'],
            '{due_date}'       => $vars['due_date'],
            '{institute_name}' => $vars['institute_name'],
            '{course}'         => $vars['course'],
        ]);

        return SmsService::sendForInstitute($student->institute_id, $student->mobile, $message, 'due_reminder');
    }
}
