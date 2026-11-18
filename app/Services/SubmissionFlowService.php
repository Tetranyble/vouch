<?php

namespace App\Services;

use App\Jobs\DispatchWebhookEventJob;
use App\Jobs\SendSubmissionCompletedJob;
use App\Jobs\SendSubmitterInvitationJob;
use App\Models\Submission;
use App\Models\SubmissionEvent;
use App\Models\Submitter;
use Illuminate\Support\Carbon;

class SubmissionFlowService
{
    public function start(Submission $submission, ?Submitter $submitter, array $payload): void
    {
        $this->recordEvent($submission, $submitter, 'start', $payload);
    }

    public function submit(Submission $submission, Submitter $submitter, array $payload): void
    {
        $this->recordEvent($submission, $submitter, 'submit', $payload);

        $isCompleted = ($payload['completed'] ?? false) || array_key_exists('completed_at', $payload);
        if (! $isCompleted) {
            return;
        }

        if (empty($submitter->completed_at)) {
            $submitter->completed_at = Carbon::now();
            $submitter->save();
        }

        $allCompleted = ! $submission->submitters()->whereNull('completed_at')->exists();
        if (! $allCompleted || $this->hasCompletionEvent($submission)) {
            return;
        }

        $this->recordEvent($submission, $submitter, 'submission_completed', []);
        SendSubmissionCompletedJob::dispatch($submitter->id);
        DispatchWebhookEventJob::dispatch('submission.completed', $submission->account_id, $submitter->id);
    }

    public function decline(Submission $submission, Submitter $submitter, array $payload): void
    {
        $this->recordEvent($submission, $submitter, 'decline', $payload);
    }

    public function inviteNext(Submission $submission, Submitter $submitter): void
    {
        $this->recordEvent($submission, $submitter, 'invite_next', []);
        if (! empty($submitter->email)) {
            $templateName = $submission->template?->name ?? 'Document';
            $senderName = optional($submission->createdByUser)->full_name ?? config('app.name', 'Vouch');
            SendSubmitterInvitationJob::dispatch($submitter->id, $senderName, $templateName);
        }
        DispatchWebhookEventJob::dispatch('form.started', $submission->account_id, $submitter->id);
    }

    public function sendEmail2fa(Submission $submission, Submitter $submitter, array $payload): void
    {
        $this->recordEvent($submission, $submitter, 'email_2fa_sent', $payload);
    }

    public function verifyEmail2fa(Submission $submission, Submitter $submitter, array $payload): void
    {
        $this->recordEvent($submission, $submitter, 'email_2fa_verified', $payload);
    }

    private function recordEvent(
        Submission $submission,
        ?Submitter $submitter,
        string $eventType,
        array $data
    ): void {
        SubmissionEvent::query()->create([
            'submission_id' => $submission->id,
            'submitter_id' => $submitter?->id,
            'account_id' => $submission->account_id,
            'data' => $data,
            'event_type' => $eventType,
            'event_timestamp' => Carbon::now(),
        ]);
    }

    private function hasCompletionEvent(Submission $submission): bool
    {
        return SubmissionEvent::query()
            ->where('submission_id', $submission->id)
            ->where('event_type', 'submission_completed')
            ->exists();
    }
}
