<?php

namespace App\Jobs;

use App\Models\Notice;
use App\Models\StaffMember;
use App\Models\Student;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNoticeSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 1;

    public function __construct(private int $noticeId) {}

    public function handle(): void
    {
        $notice = Notice::find($this->noticeId);

        if (! $notice || ! $notice->sms_to) {
            return;
        }

        if (! SmsService::isInstituteConfigured($notice->institute_id)) {
            return;
        }

        $roles = array_map('trim', explode(',', $notice->sms_to));

        if (in_array('staff', $roles)) {
            StaffMember::where('institute_id', $notice->institute_id)
                ->where('status', true)
                ->whereNotNull('mobile')
                ->pluck('mobile')
                ->each(fn($mobile) => $this->safeSend($notice, $mobile));
        }

        if (in_array('students', $roles)) {
            $studentQuery = Student::where('institute_id', $notice->institute_id)->whereNotNull('mobile');
            $notice->applyTargetingToStudentQuery($studentQuery);
            $studentQuery->pluck('mobile')
                ->each(fn($mobile) => $this->safeSend($notice, $mobile));
        }
    }

    private function safeSend(Notice $notice, string $mobile): void
    {
        try {
            if ($notice->sms_template_id) {
                // Admin picked one of their registered DLT notice templates and filled its
                // variable values when creating this notice — use exactly that.
                SmsService::sendTemplatedById($notice->institute_id, $mobile, $notice->sms_template_id, $notice->sms_template_values ?? []);
            } else {
                // Ad-hoc notice with no matching registered template — best-effort free-text.
                SmsService::sendForInstitute($notice->institute_id, $mobile, $this->buildMessage($notice), 'notice');
            }
        } catch (\Throwable) {
            // fail gracefully — SMS failure shouldn't break other deliveries
        }
    }

    private function buildMessage(Notice $notice): string
    {
        $text = "[{$notice->notice_type_label}] {$notice->title}";

        if (strlen($text) > 140) {
            $text = substr($text, 0, 137) . '...';
        }

        return $text;
    }
}
