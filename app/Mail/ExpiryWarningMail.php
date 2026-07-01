<?php

namespace App\Mail;

use App\Models\VendorDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpiryWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly VendorDocument $document,
        public readonly int            $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->daysLeft <= 0
            ? "Urgent: {$this->document->documentType->name} has expired"
            : "Reminder: {$this->document->documentType->name} expiring in {$this->daysLeft} day(s)";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.expiry-warning');
    }

    public function attachments(): array
    {
        return [];
    }
}
