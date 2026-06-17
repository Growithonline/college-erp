<?php

namespace App\Services;

use App\Models\Institute;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class InstituteMailer
{
    /**
     * Send synchronously via institute SMTP if verified, else platform SMTP.
     */
    public static function send(int $instituteId, string $to, Mailable $mailable): void
    {
        $institute = Institute::find($instituteId);

        if ($institute?->hasSmtp()) {
            $originalFrom = config('mail.from');
            try {
                static::applyConfig($institute);
                Mail::mailer('inst_smtp_' . $instituteId)->to($to)->send($mailable);
            } finally {
                config(['mail.from' => $originalFrom]);
            }
        } else {
            Mail::to($to)->send($mailable);
        }
    }

    /**
     * Queue via a job that fetches fresh SMTP config at execution time.
     */
    public static function queue(int $instituteId, string $to, Mailable $mailable): void
    {
        dispatch(new \App\Jobs\SendInstituteMailJob($instituteId, $to, $mailable));
    }

    /**
     * Configure runtime mailer key + mail.from for this institute.
     * Called both in sync sends and inside the queue job.
     */
    public static function applyConfig(Institute $institute): void
    {
        $key = 'inst_smtp_' . $institute->id;

        config([
            'mail.mailers.' . $key => [
                'transport'  => 'smtp',
                'host'       => $institute->smtp_host,
                'port'       => (int) $institute->smtp_port,
                'encryption' => $institute->smtp_encryption === 'none' ? null : $institute->smtp_encryption,
                'username'   => $institute->smtp_username,
                'password'   => $institute->smtp_password,
                'timeout'    => 30,
            ],
            'mail.from' => [
                'address' => $institute->smtp_from_email,
                'name'    => $institute->smtp_from_name,
            ],
        ]);
    }
}
