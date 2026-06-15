<?php

namespace App\Mail;

use App\Models\Center;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CenterOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Center $center,
        public string $otp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Center Login OTP');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.center-otp');
    }

    public function attachments(): array
    {
        return [];
    }
}
