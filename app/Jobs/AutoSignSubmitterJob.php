<?php

namespace App\Jobs;

use App\Models\Submitter;
use App\Models\User;
use App\Models\UserConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoSignSubmitterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $submitterId) {}

    public function handle(): void
    {
        $submitter  = Submitter::with('submission')->find($this->submitterId);

        if (!$submitter) return;
        if ($submitter->completed_at || $submitter->declined_at) return;
        if (!($submitter->metadata['auto_sign'] ?? false)) return;
        if (!$submitter->email) return;

        $user = User::where('email', $submitter->email)
            ->where('account_id', $submitter->account_id)
            ->first();

        if (!$user) return;

        // Respect the user's prefill_signature preference (default: on)
        $configs = UserConfig::where('user_id', $user->id)
            ->whereIn('key', ['signature', 'initials', 'prefill_signature'])
            ->pluck('value', 'key');

        $prefillEnabled = $configs->has('prefill_signature')
            ? (bool) $configs->get('prefill_signature')
            : true;

        if (!$prefillEnabled) return;

        $savedSignature = $configs->get('signature');
        $savedInitials  = $configs->get('initials');

        // Resolve which fields belong to this submitter
        $submission = $submitter->submission;
        $allFields  = $submission->template_fields ?? [];

        $myFields = collect($allFields)
            ->filter(fn ($f) => ($f['submitter_uuid'] ?? null) === $submitter->uuid)
            ->values();

        $required = $myFields->filter(
            fn ($f) => ($f['required'] ?? false) && !in_array($f['type'], ['heading', 'formula'])
        );

        // Build values — abort if any required field cannot be auto-filled
        $values = [];
        foreach ($required as $field) {
            $type = $field['type'];
            if ($type === 'signature') {
                if (!$savedSignature) return;
                $values[$field['uuid']] = $savedSignature;
            } elseif ($type === 'initials') {
                if (!$savedInitials) return;
                $values[$field['uuid']] = $savedInitials;
            } elseif ($type === 'date') {
                $values[$field['uuid']] = now()->format('Y-m-d');
            } else {
                // Required field of unknown type — cannot auto-fill, bail out
                return;
            }
        }

        $submitter->update([
            'values'       => array_merge($submitter->values ?? [], $values),
            'completed_at' => now(),
        ]);

        GenerateSubmissionPdfJob::dispatch($submitter->id);
        GenerateAuditTrailJob::dispatch($submitter->id);
        GenerateCombinedPdfJob::dispatch($submitter->id)->delay(now()->addSeconds(10));
        SendSubmissionCompletedJob::dispatch($submitter->id);
        DispatchWebhookEventJob::dispatch('form.completed', $submitter->account_id, $submitter->id);
        ReindexSearchEntryJob::dispatch('Submitter', $submitter->id);
        ReindexSearchEntryJob::dispatch('Submission', $submitter->submission_id);

        // In sequential mode, invite the next group
        if (($submission->submitters_order ?? 'random') !== 'random') {
            $this->inviteNextGroup($submitter);
        }
    }

    private function inviteNextGroup(Submitter $justCompleted): void
    {
        $submission      = $justCompleted->submission;
        $freshSubmitters = Submitter::where('submission_id', $submission->id)->get();

        $currentGroupDone = $freshSubmitters
            ->where('order', $justCompleted->order)
            ->every(fn ($s) => $s->completed_at || $s->declined_at);

        if (!$currentGroupDone) return;

        $nextOrder = $freshSubmitters
            ->where('order', '>', $justCompleted->order)
            ->whereNull('sent_at')
            ->min('order');

        if ($nextOrder === null) return;

        $creator      = $submission->createdByUser;
        $templateName = $submission->template?->name ?? 'Document';
        $senderName   = $creator?->full_name ?? 'Vouch';

        $nextGroup = $freshSubmitters->where('order', $nextOrder)->whereNull('sent_at');

        foreach ($nextGroup as $next) {
            $next->update(['sent_at' => now()]);
            if ($next->email || $next->phone) {
                SendSubmitterInvitationJob::dispatch($next->id, $senderName, $templateName);
            }
        }
    }
}
