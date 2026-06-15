<?php

namespace App\Notifications;

use App\Models\AdmissionDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(private AdmissionDocument $document) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $docName    = $this->document->documentType->name ?? 'Document';
        $reason     = $this->document->rejection_reason;
        $studentName = $this->document->student->name ?? '';
        $institute  = $this->document->institute->name ?? '';

        return (new MailMessage)
            ->subject("Document Rejected: {$docName} — {$institute}")
            ->greeting("Namaste {$studentName},")
            ->line("Your document **{$docName}** has been rejected.")
            ->line("**Reason:** {$reason}")
            ->line('While uploading, ensure that the document is clear and all details are visible. If the issue persists, please try uploading a different copy of the document.')
            ->line('If any issues persist, please contact institute for further assistance.')
            ->salutation("Thank You,\n{$institute}");
    }
}
