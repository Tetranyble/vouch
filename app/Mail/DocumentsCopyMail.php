<?php

namespace App\Mail;

use App\Facades\DocumentStorage;
use App\Models\CompletedDocument;
use App\Models\Submitter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentsCopyMail extends Mailable
{
    use Queueable, SerializesModels;

    private const MAX_ATTACHMENT_BYTES = 10 * 1024 * 1024; // 10 MB

    public function __construct(
        public readonly Submitter $submitter,
        public readonly string $documentName,
        public readonly string $downloadUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your signed copy: {$this->documentName}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.documents-copy',
            text: 'mail.documents-copy-text',
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        $totalSize = 0;

        $docs = CompletedDocument::where('submitter_id', $this->submitter->id)
            ->whereNotNull('storage_path')
            ->whereNotIn('filename', ['audit_trail.pdf', 'combined.pdf'])
            ->get();

        // If only a pre-generated combined PDF survived, it is preferable
        // to sending an email with no attachment at all.
        if ($docs->isEmpty()) {
            $docs = CompletedDocument::query()
                ->where('submitter_id', $this->submitter->id)
                ->where('filename', 'combined.pdf')
                ->whereNotNull('storage_path')
                ->get();
        }

        foreach ($docs as $doc) {
            if (! DocumentStorage::exists($doc->storage_path, $this->submitter->account_id)) {
                continue;
            }

            $size = DocumentStorage::size($doc->storage_path, $this->submitter->account_id);
            if ($totalSize + $size > self::MAX_ATTACHMENT_BYTES) {
                break;
            }

            $totalSize += $size;
            $attachments[] = Attachment::fromData(
                fn () => DocumentStorage::get($doc->storage_path, $this->submitter->account_id),
                $doc->filename ?? 'document.pdf',
            )->withMime('application/pdf');
        }

        return $attachments;
    }
}
