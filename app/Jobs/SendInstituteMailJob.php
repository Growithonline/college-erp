<?php

namespace App\Jobs;

use App\Models\Institute;
use App\Services\InstituteMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInstituteMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly int      $instituteId,
        public readonly string   $to,
        public readonly Mailable $mailable,
    ) {}

    public function handle(): void
    {
        $institute = Institute::find($this->instituteId);

        if ($institute?->hasSmtp()) {
            $originalFrom = config('mail.from');
            try {
                InstituteMailer::applyConfig($institute);
                Mail::mailer('inst_smtp_' . $this->instituteId)
                    ->to($this->to)
                    ->send($this->mailable);
            } finally {
                config(['mail.from' => $originalFrom]);
            }
        } else {
            Mail::to($this->to)->send($this->mailable);
        }
    }
}
