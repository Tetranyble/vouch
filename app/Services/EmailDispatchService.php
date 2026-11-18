<?php

namespace App\Services;

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
            'You have a document to sign.',
            'Please review and sign your document.'
        );
    }

    public function sendSubmissionCompleted(string $submissionId, string $email): void
    {
        $this->sendSubmissionEmail(
            $submissionId,
            $email,
            'submission_completed',
            'Your submission is complete.',
            'Your document has been completed.'
        );
    }

    private function sendSubmissionEmail(
        string $submissionId,
        string $email,
        string $tag,
        string $subject,
        string $body
    ): void {
        $submission = Submission::query()->findOrFail($submissionId);
        $authorId = $submission->created_by_user_id;

        if (! $authorId) {
            $authorId = User::query()
                ->where('account_id', $submission->account_id)
                ->value('id');
        }

        Mail::raw($body, function ($mail) use ($email, $subject) {
            $mail->to($email)->subject($subject);
        });

        if (! $authorId) {
            return;
        }

        $message = EmailMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'author_id' => $authorId,
            'account_id' => $submission->account_id,
            'subject' => $subject,
            'body' => $body,
            'sha1' => sha1($subject.$body),
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
