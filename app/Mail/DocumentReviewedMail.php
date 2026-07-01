<?php

namespace App\Mail;

use App\Models\Review;
use App\Models\VendorDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to vendor users when a review decision is made on one of their
 * documents. MAIL_MAILER=log in demo mode so no real email is sent
 * (output goes to storage/logs/laravel.log instead).
 */
class DocumentReviewedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly VendorDocument $document,
        public readonly Review         $review,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->review->decision) {
            'approved'             => "Document approved: {$this->document->documentType->name}",
            'rejected'             => "Action required: {$this->document->documentType->name} rejected",
            'correction_requested' => "Correction needed: {$this->document->documentType->name}",
            'need_more_info'       => "More information needed: {$this->document->documentType->name}",
            'escalated'            => "Document under escalated review: {$this->document->documentType->name}",
            default                => "Document review update: {$this->document->documentType->name}",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.document-reviewed');
    }

    public function attachments(): array
    {
        return [];
    }
}
