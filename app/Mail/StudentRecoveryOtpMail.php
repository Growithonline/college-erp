<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentRecoveryOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public string $otp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Account Recovery OTP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-recovery-otp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
