<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InstituteOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $otp,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Institute Login OTP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.institute-otp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
