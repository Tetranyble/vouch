<?php

namespace App\Mail;

use App\Models\Submitter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmitterInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Submitter $submitter,
        public readonly string $senderName,
        public readonly string $templateName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Signature requested: {$this->templateName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.submitter-invitation',
            with: [
                'submitter' => $this->submitter,
                'senderName' => $this->senderName,
                'templateName' => $this->templateName,
                'formUrl' => url('/s/' . $this->submitter->slug),
            ],
        );
    }
}
