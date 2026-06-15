<?php

namespace App\Mail;

use App\Models\LibraryStaff;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LibraryStaffWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly LibraryStaff $libraryStaff,
        public readonly string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to Library Staff Portal — Your Access is Ready');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.library-staff-welcome');
    }
}
