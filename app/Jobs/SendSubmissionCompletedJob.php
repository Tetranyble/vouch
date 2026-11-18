<?php

namespace App\Jobs;

use App\Mail\DocumentsCopyMail;
use App\Mail\SubmissionCompletedMail;
use App\Models\AccountConfig;
use App\Models\Submitter;
use App\Services\DynamicMailService;
use App\Support\SubmitterDownloadLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendSubmissionCompletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $submitterId,
    ) {}

    public function handle(): void
    {
        $submitter = Submitter::with('submission.submitters')->findOrFail($this->submitterId);
        $submission = $submitter->submission;

        if (! $submission) {
            return;
        }

        DynamicMailService::configureForAccount($submitter->account_id);

        $documentName = $submission->name ?? $submission->template?->name ?? 'Document';
        $downloadUrl = SubmitterDownloadLink::for($submitter);

        // Send documents copy to the submitter who just completed
        if ($submitter->email && $this->shouldSendDocumentsCopy($submitter->account_id)) {
            Mail::to($submitter->email, $submitter->name)
                ->queue(new DocumentsCopyMail($submitter, $documentName, $downloadUrl));
        }

        // Notify the creator/admin once all parties have completed
        $allCompleted = $submission->submitters->every(fn ($s) => $s->completed_at !== null);

        if ($allCompleted) {
            $creator = $submission->createdByUser;
            if ($creator?->email) {
                Mail::to($creator->email, $creator->full_name)
                    ->queue(new SubmissionCompletedMail($submission, $submitter));
            }
        }
    }

    private function shouldSendDocumentsCopy(int $accountId): bool
    {
        $config = AccountConfig::where('account_id', $accountId)
            ->where('key', 'send_documents_copy_email')
            ->first();

        // Default to sending unless explicitly disabled
        return $config === null || $config->value !== false;
    }
}
