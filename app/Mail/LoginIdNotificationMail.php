<?php

namespace App\Mail;

use App\Models\Institute;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginIdNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?Institute $institute,
        public string $recipientName,
        public string $portalLabel,
        public string $loginIdLabel,
        public string $loginId,
        public string $loginUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your New {$this->loginIdLabel} — {$this->portalLabel}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-id-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
