<?php

namespace App\Jobs;

use App\Models\SmsBroadcast;
use App\Services\SmsBroadcastTargeting;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries   = 1;

    public function __construct(private int $broadcastId) {}

    public function handle(): void
    {
        $broadcast = SmsBroadcast::find($this->broadcastId);

        // Only run once, and only for a broadcast that's actually been confirmed — guards
        // against a stray re-dispatch double-sending everyone.
        if (! $broadcast || $broadcast->status !== SmsBroadcast::STATUS_QUEUED) {
            return;
        }

        $broadcast->update(['status' => SmsBroadcast::STATUS_SENDING]);

        $recipients = $broadcast->resolveRecipientQuery()->get();
        $sent   = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            // {name}/{course}/etc. come from this recipient's own record — never the same
            // literal value broadcast to everyone (see SmsBroadcastTargeting::recipientAutoVarNames).
            $vars = array_merge(
                $broadcast->template_values ?? [],
                SmsBroadcastTargeting::buildRecipientVars($recipient, $broadcast->audience_type)
            );

            $ok = false;
            try {
                $ok = SmsService::sendTemplatedById(
                    $broadcast->institute_id,
                    $recipient->mobile,
                    $broadcast->sms_template_id,
                    $vars,
                    $broadcast->id
                );
            } catch (\Throwable) {
                $ok = false;
            }

            $ok ? $sent++ : $failed++;
        }

        $status = match (true) {
            $sent === 0 && $failed > 0 => SmsBroadcast::STATUS_FAILED,
            $failed > 0                => SmsBroadcast::STATUS_PARTIAL,
            default                    => SmsBroadcast::STATUS_SENT,
        };

        $broadcast->update([
            'status'       => $status,
            'sent_count'   => $sent,
            'failed_count' => $failed,
            'sent_at'      => now(),
        ]);
    }
}
