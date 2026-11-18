<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchWebhookEventJob;
use App\Jobs\SendSubmitterInvitationJob;
use App\Mail\DeclinedSubmissionMail;
use App\Models\SubmissionEvent;
use App\Models\Submitter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class SubmitFormDeclineController extends Controller
{
    public function store(Request $request, string $slug)
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $reason = $validated['reason'] ?? '';

        // A decline must never race a successful signature or create duplicate audit events.
        $submitter = DB::transaction(function () use ($slug, $reason, $request): ?Submitter {
            $submitter = Submitter::where('slug', $slug)->lockForUpdate()->firstOrFail();
            $submission = $submitter->submission;
            abort_unless($submission && (int) $submission->account_id === (int) $submitter->account_id, 404);
            abort_if($submission->archived_at || $submission->is_expired, 410);
            if ($submitter->completed_at || $submitter->declined_at) {
                return null;
            }

            $submitter->update([
                'declined_at' => now(),
                'metadata' => array_merge($submitter->metadata ?? [], ['decline_reason' => $reason]),
            ]);
            SubmissionEvent::create([
                'submission_id' => $submitter->submission_id,
                'submitter_id' => $submitter->id,
                'account_id' => $submitter->account_id,
                'event_type' => 'form.declined',
                'event_timestamp' => now(),
                'data' => ['reason' => $reason, 'ip' => $request->ip()],
            ]);
            return $submitter;
        }, 3);

        if ($submitter === null) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Already processed'], 422);
            }
            return redirect()->route('submit-form.show', $slug);
        }
        $submitter->load(['submission.template', 'submission.createdByUser']);

        DispatchWebhookEventJob::dispatch('form.declined', $submitter->account_id, $submitter->id);

        // Notify the submission owner / template author
        $submission    = $submitter->submission;
        $notifyUser    = $submission->createdByUser ?? $submission->template?->author;
        $documentName  = $submission->name ?? $submission->template?->name ?? 'Document';

        if ($notifyUser?->email) {
            Mail::to($notifyUser->email)->queue(
                new DeclinedSubmissionMail(
                    submitter: $submitter,
                    documentName: $documentName,
                    declineReason: $reason,
                    submissionUrl: route('submissions.show', $submission->id),
                )
            );
        }

        // In sequential mode, treat decline like completion — invite next group
        $isSequential = ($submission->submitters_order ?? 'random') !== 'random';

        if ($isSequential) {
            $allSubmitters = Submitter::where('submission_id', $submission->id)->get();
            $currentGroupDone = $allSubmitters
                ->where('order', $submitter->order)
                ->every(fn ($s) => $s->completed_at || $s->declined_at);

            if ($currentGroupDone) {
                $nextOrder = $allSubmitters
                    ->where('order', '>', $submitter->order)
                    ->whereNull('sent_at')
                    ->min('order');

                if ($nextOrder !== null) {
                    $templateName = $submission->template?->name ?? 'Document';
                    $senderName   = $submission->createdByUser?->full_name ?? config('app.name');

                    foreach ($allSubmitters->where('order', $nextOrder)->whereNull('sent_at') as $next) {
                        $marked = Submitter::whereKey($next->id)->whereNull('sent_at')->update(['sent_at' => now()]);
                        if ($marked && ($next->email || $next->phone)) {
                            SendSubmitterInvitationJob::dispatch($next->id, $senderName, $templateName);
                        }
                    }
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['slug' => $slug, 'status' => 'declined']);
        }
        return redirect()->route('submit-form.show', $slug);
    }
}
