<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationDocumentsLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public string $url,
        public bool $isWaitlisted = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isWaitlisted ? 'Your Application is on the Waitlist' : 'Next Steps for Your Admission',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-documents-link',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
