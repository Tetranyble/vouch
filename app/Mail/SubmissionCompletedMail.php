<?php

namespace App\Mail;

use App\Models\Submission;
use App\Models\Submitter;
use App\Support\SubmitterDownloadLink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmissionCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Submission $submission,
        public readonly Submitter $submitter,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'All parties signed: '.($this->submission->name ?? $this->submission->template?->name ?? 'Document'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.submission-completed',
            text: 'mail.submission-completed-text',
            with: [
                'submission' => $this->submission,
                'submitter' => $this->submitter,
                'templateName' => $this->submission->name ?? $this->submission->template?->name ?? 'Document',
                'downloadUrl' => SubmitterDownloadLink::for($this->submitter),
            ],
        );
    }
}
