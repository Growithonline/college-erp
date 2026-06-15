<?php

namespace App\Mail;

use App\Models\LibraryStaff;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LibraryStaffOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly LibraryStaff $libraryStaff,
        public readonly string $otp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Library Staff Login OTP');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.library-staff-otp');
    }
}
