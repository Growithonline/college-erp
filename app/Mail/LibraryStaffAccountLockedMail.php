<?php

namespace App\Mail;

use App\Models\LibraryStaff;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LibraryStaffAccountLockedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly LibraryStaff $libraryStaff,
        public readonly string $lockedUntil,
        public readonly string $triggerIp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Security Alert — Library Staff Account Locked');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.library-staff-account-locked');
    }
}
