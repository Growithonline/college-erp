<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SmtpTestMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $fromName,
        public string $fromEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($this->fromEmail, $this->fromName),
            subject: 'SMTP Test — College ERP',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.smtp-test');
    }

    public function attachments(): array
    {
        return [];
    }
}
