<?php

namespace App\Mail;

use App\Models\StaffMember;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public StaffMember $staffMember,
        public string $otp,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Staff Login OTP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.staff-otp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
