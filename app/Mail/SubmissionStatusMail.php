<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmissionStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Submission $submission,
        public readonly string $type,
        public readonly string $message,
    ) {}

    public function envelope(): Envelope
    {
        $documentName = $this->submission->name ?? $this->submission->template?->name ?? 'Document';

        return new Envelope(
            subject: $this->type === 'submission_completed'
                ? "Signing complete: {$documentName}"
                : "Document review requested: {$documentName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.submission-status',
            text: 'mail.submission-status-text',
            with: [
                'documentName' => $this->submission->name ?? $this->submission->template?->name ?? 'Document',
                'type' => $this->type,
                'message' => $this->message,
            ],
        );
    }
}
