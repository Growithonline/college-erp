<?php

namespace App\Mail;

use App\Models\StaffMember;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public StaffMember $staffMember,
        public string $plainPassword,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Staff Portal Login Credentials',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.staff-credentials',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
