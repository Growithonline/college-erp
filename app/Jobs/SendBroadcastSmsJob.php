<?php

namespace App\Jobs;

use App\Models\Institute;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBroadcastSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 2;
    public int $timeout = 30;

    public function __construct(
        private int    $instituteId,
        private string $messageTemplate,
        private string $type = SmsLog::TYPE_BROADCAST,
    ) {}

    public function handle(): void
    {
        $institute = Institute::find($this->instituteId);
        if (! $institute) return;

        $mobile = $institute->owner_mobile;
        if (empty($mobile)) return;

        $message = strtr($this->messageTemplate, [
            '{institute_name}'  => $institute->name,
            '{owner_name}'      => $institute->owner_name ?? '',
            '{institute_id}'    => $institute->institute_uid,
            '{subscription_end}' => $institute->subscription_end
                ? $institute->subscription_end->format('d M Y')
                : 'N/A',
        ]);

        SmsService::sendFromPlatform($mobile, $message, $this->type, $this->instituteId);
    }
}
