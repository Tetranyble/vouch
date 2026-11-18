<?php

namespace App\Actions\Submissions;

use App\Actions\Submitters\InviteSubmitterAction;
use App\Jobs\AutoSignSubmitterJob;
use App\Jobs\DispatchWebhookEventJob;
use App\Jobs\ReindexSearchEntryJob;
use App\Models\Submission;
use App\Models\Submitter;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSubmissionAction
{
    public function __construct(
        private readonly InviteSubmitterAction $inviteSubmitter,
    ) {}


    /**
     * Create a submission from a template, seed its submitters, and dispatch
     * invitation / webhook / search-index side-effects.
     *
     * @param  array<int, array{name?:string, email?:string, phone?:string, external_id?:string}>  $submitterInputs
     *                                                                                                               Keyed by submitter position (0-based). Only position 0 is used for the first-party.
     *                                                                                                               For bulk creation, pass a single-element array with the recipient's details.
     */
    public function execute(
        Template $template,
        User $creator,
        array $submitterInputs = [],
        string $source = 'invite',
        ?string $expireAt = null,
        string $submittersOrder = 'random',
    ): Submission {
        abort_unless($template->account_id === $creator->account_id, 403, 'Template belongs to another account.');

        // Persist the submission and all signers atomically before dispatching side effects.
        [$submission, $submitterList, $isParallel] = DB::transaction(function () use (
            $template, $creator, $submitterInputs, $source, $expireAt, $submittersOrder
        ): array {
            $submission = Submission::create([
                'template_id' => $template->id,
                'account_id' => $creator->account_id,
                'created_by_user_id' => $creator->id,
                'source' => $source,
                'template_fields' => $template->fields,
                'template_documents' => $template->documents,
                'template_schema' => $template->schema,
                'template_submitters' => $template->submitters,
                'submitters_order' => $submittersOrder,
                'expire_at' => $expireAt,
            ]);

            $isParallel = ($submission->submitters_order ?? 'random') === 'random';
            $submitterList = [];

            foreach ($template->submitters ?? [] as $index => $tSubmitter) {
                $input = $submitterInputs[$index] ?? [];
                $hasContact = ! empty($input['email'] ?? $input['phone'] ?? null);

                // Sequential: only order-0 gets sent_at now; others wait for their turn.
                // Parallel:   everyone with contact info gets sent_at immediately.
                $sentAt = ($isParallel ? $hasContact : ($index === 0 && $hasContact)) ? now() : null;

                // auto_sign: from per-send input first, fall back to template role default
                $autoSign = isset($input['auto_sign'])
                    ? (bool) $input['auto_sign']
                    : (bool) ($tSubmitter['auto_sign'] ?? false);

                $submitter = Submitter::create([
                    'submission_id' => $submission->id,
                    'uuid' => $tSubmitter['uuid'],
                    'name' => $input['name'] ?? null,
                    'email' => $input['email'] ?? null,
                    'phone' => $input['phone'] ?? null,
                    'external_id' => $input['external_id'] ?? null,
                    'values' => ! empty($input['values']) ? $input['values'] : null,
                    'metadata' => $autoSign ? ['auto_sign' => true] : null,
                    'slug' => Str::random(14),
                    'account_id' => $creator->account_id,
                    'order' => $index,
                    'sent_at' => $sentAt,
                ]);

                $submitterList[] = $submitter;
            }

            return [$submission, $submitterList, $isParallel];
        });

        DispatchWebhookEventJob::dispatch('submission.created', $creator->account_id, null, $submission->id);
        ReindexSearchEntryJob::dispatch('Submission', $submission->id);

        // Dispatch auto-sign job for any submitter that has the flag set and an email
        foreach ($submitterList as $submitter) {
            if (($submitter->metadata['auto_sign'] ?? false) && $submitter->email) {
                AutoSignSubmitterJob::dispatch($submitter->id)->delay(now()->addSeconds(3));
            }
        }

        // Exclude auto-sign submitters from the regular invitation flow
        $toInvite = collect($isParallel ? $submitterList : array_slice($submitterList, 0, 1))
            ->reject(fn ($s) => $s->metadata['auto_sign'] ?? false)
            ->all();

        foreach ($toInvite as $submitter) {
            $this->inviteSubmitter->execute(
                $submitter,
                $creator->full_name ?: $creator->email,
                $template->name,
            );
        }

        return $submission->load('submitters');
    }
}
