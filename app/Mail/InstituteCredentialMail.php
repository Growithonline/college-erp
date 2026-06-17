<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InstituteCredentialMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $ownerName,
        public string $instituteName,
        public string $instituteUid,
        public string $email,
        public string $password,
        public string $loginUrl,
        public string $logoUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Institute Login Credentials — College ERP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.institute-credentials',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
