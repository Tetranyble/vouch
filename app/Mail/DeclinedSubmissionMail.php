<?php

namespace App\Mail;

use App\Models\Submitter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeclinedSubmissionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Submitter $submitter,
        public readonly string $documentName,
        public readonly string $declineReason,
        public readonly string $submissionUrl,
    ) {}

    public function envelope(): Envelope
    {
        $submitterLabel = $this->submitter->name ?: $this->submitter->email ?: $this->submitter->phone ?? 'Submitter';
        $shortName = mb_strimwidth($this->documentName, 0, 20, '…');

        return new Envelope(
            subject: "Signature declined: {$shortName}",
            replyTo: array_filter([$this->submitter->email]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.declined-submission');
    }
}
