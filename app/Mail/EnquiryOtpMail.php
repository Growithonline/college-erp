<?php

namespace App\Mail;

use App\Models\Institute;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnquiryOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Institute $institute,
        public string $otp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Admission Enquiry OTP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.enquiry-otp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
