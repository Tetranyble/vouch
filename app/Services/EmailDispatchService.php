<?php

namespace App\Services;

use App\Mail\SubmissionStatusMail;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailDispatchService
{
    public function sendSubmissionInvite(string $submissionId, string $email): void
    {
        $this->sendSubmissionEmail(
            $submissionId,
            $email,
            'submission_invite',
            'Please review and sign your document.'
        );
    }

    public function sendSubmissionCompleted(string $submissionId, string $email): void
    {
        $this->sendSubmissionEmail(
            $submissionId,
            $email,
            'submission_completed',
            'Your document has been completed.'
        );
    }

    private function sendSubmissionEmail(
        string $submissionId,
        string $email,
        string $tag,
        string $body
    ): void {
        $submission = Submission::query()->findOrFail($submissionId);
        $authorId = $submission->created_by_user_id;

        if (! $authorId) {
            $authorId = User::query()
                ->where('account_id', $submission->account_id)
                ->value('id');
        }

        $mailable = new SubmissionStatusMail($submission, $tag, $body);
        $subject = $mailable->envelope()->subject;

        Mail::to($email)->send($mailable);

        if (! $authorId) {
            return;
        }

        $message = EmailMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'author_id' => $authorId,
            'account_id' => $submission->account_id,
            'subject' => $subject,
            'body' => $body,
            'sha1' => sha1($tag.$body),
        ]);

        EmailEvent::query()->create([
            'account_id' => $submission->account_id,
            'emailable_type' => Submission::class,
            'emailable_id' => $submission->id,
            'message_id' => $message->uuid,
            'tag' => $tag,
            'event_type' => 'sent',
            'email' => $email,
            'data' => ['subject' => $subject],
            'event_datetime' => Carbon::now(),
        ]);
    }
}
