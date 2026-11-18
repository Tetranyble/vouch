<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchWebhookEventJob;
use App\Jobs\GenerateAuditTrailJob;
use App\Jobs\GenerateCombinedPdfJob;
use App\Jobs\GenerateSubmissionPdfJob;
use App\Jobs\ReindexSearchEntryJob;
use App\Jobs\SendSubmissionCompletedJob;
use App\Jobs\SendSubmitterInvitationJob;
use App\Models\SubmissionEvent;
use App\Models\Submitter;
use App\Models\User;
use App\Models\UserConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use App\Facades\DocumentStorage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Inertia\Inertia;

class SubmitFormController extends Controller
{
    public function show(string $slug)
    {
        $submitter = Submitter::where('slug', $slug)
            ->with(['submission.submitters', 'submission.template'])
            ->firstOrFail();

        $submission = $submitter->submission;
        $template   = $submission->template;
        $allFields  = $submission->template_fields ?? $template?->fields ?? [];
        $schema     = $submission->template_schema ?? $template?->schema ?? [];
        $documents  = $submission->template_documents ?? $template?->documents ?? [];

        // Only the fields that belong to this submitter role
        $submitterFields = collect($allFields)
            ->filter(fn ($f) => ($f['submitter_uuid'] ?? null) === $submitter->uuid)
            ->values()
            ->all();

        // Build document list with public preview URLs
        $documentList = collect($documents)->map(fn ($doc) => [
            'uuid'         => $doc['uuid'],
            'filename'     => $doc['filename'] ?? '',
            'content_type' => $doc['content_type'] ?? 'application/pdf',
            'page_count'   => $doc['page_count'] ?? 1,
            'url'          => route('submit-form.document', [$slug, $doc['uuid']]),
        ])->values()->all();

        // Build all-submitters list for multi-party UI (role names from template_submitters)
        $templateSubmitterNames = collect($submission->template_submitters ?? [])
            ->keyBy('uuid')
            ->map(fn ($ts) => $ts['name'] ?? null);

        $allSubmitters = $submission->submitters
            ->sortBy('order')
            ->map(fn ($s) => [
                'uuid'       => $s->uuid,
                'name'       => $s->name ?: ($templateSubmitterNames[$s->uuid] ?? null),
                'email'      => $s->email,
                'status'     => $s->status,
                'order'      => $s->order,
                'is_current' => $s->id === $submitter->id,
            ])
            ->values()
            ->all();

        // Determine if this submitter is awaiting their turn.
        // Sequential mode: a submitter is awaiting if they haven't been sent yet
        // AND there are uncompleted/undeclined submitters with a lower order.
        $isSequential = ($submission->submitters_order ?? 'random') !== 'random';
        $isAwaiting   = false;

        if (!$submitter->completed_at && !$submitter->declined_at) {
            if ($isSequential || !$submitter->sent_at) {
                $priorPending = $submission->submitters
                    ->where('order', '<', $submitter->order)
                    ->first(fn ($s) => !$s->completed_at && !$s->declined_at);

                $isAwaiting = (bool) $priorPending;
            }
        }

        // Only record opened/started events when it's actually their turn
        if (!$isAwaiting && !$submitter->completed_at && !$submitter->declined_at) {
            if (!$submitter->opened_at) {
                $submitter->update(['opened_at' => now()]);
                SubmissionEvent::create([
                    'submission_id'   => $submitter->submission_id,
                    'submitter_id'    => $submitter->id,
                    'account_id'      => $submitter->account_id,
                    'event_type'      => 'form.viewed',
                    'event_timestamp' => now(),
                    'data'            => ['ip' => request()->ip(), 'ua' => request()->userAgent()],
                ]);
                DispatchWebhookEventJob::dispatch('form.viewed', $submitter->account_id, $submitter->id);
            }

            $hasStarted = SubmissionEvent::where('submitter_id', $submitter->id)
                ->where('event_type', 'form.started')->exists();
            if (!$hasStarted) {
                SubmissionEvent::create([
                    'submission_id'   => $submitter->submission_id,
                    'submitter_id'    => $submitter->id,
                    'account_id'      => $submitter->account_id,
                    'event_type'      => 'form.started',
                    'event_timestamp' => $submitter->opened_at ?? now(),
                ]);
                DispatchWebhookEventJob::dispatch('form.started', $submitter->account_id, $submitter->id);
            }
        }

        // Pre-fill saved signature/initials: match by authenticated session first,
        // then fall back to a user whose email matches the submitter's.
        // Never expose a logged-in person's saved signature to a different signer.
        // This page is accessed through a signing link, which is not identity proof.
        $authenticatedUser = auth()->user();
        $signingUser = $submitter->email
            && $authenticatedUser
            && $authenticatedUser->account_id === $submitter->account_id
            && strcasecmp($authenticatedUser->email, $submitter->email) === 0
                ? $authenticatedUser
                : null;

        $previousSignature = null;
        $previousInitials  = null;

        if ($signingUser) {
            $configs = UserConfig::where('user_id', $signingUser->id)
                ->whereIn('key', ['signature', 'initials', 'prefill_signature'])
                ->pluck('value', 'key');

            // Default prefill to true when the user hasn't explicitly disabled it
            $prefillEnabled = $configs->has('prefill_signature')
                ? (bool) $configs->get('prefill_signature')
                : true;

            if ($prefillEnabled) {
                $previousSignature = $configs->get('signature');
                $previousInitials  = $configs->get('initials');
            }
        }

        return Inertia::render('Public/SubmitForm/Show', [
            'submitter' => [
                'id'    => $submitter->id,
                'slug'  => $submitter->slug,
                'uuid'  => $submitter->uuid,
                'name'  => $submitter->name,
                'email' => $submitter->email,
            ],
            'fields'            => $submitterFields,
            'initialValues'     => $submitter->values ?? [],
            'documents'         => $documentList,
            'schema'            => $schema,
            'template'          => $template ? ['id' => $template->id, 'name' => $template->name] : null,
            'isCompleted'       => (bool) $submitter->completed_at,
            'isDeclined'        => (bool) $submitter->declined_at,
            'isAwaiting'        => $isAwaiting,
            'declineReason'     => $submitter->metadata['decline_reason'] ?? null,
            'allSubmitters'     => $allSubmitters,
            'previousSignature' => $previousSignature,
            'previousInitials'  => $previousInitials,
        ]);
    }

    public function document(string $slug, string $attachmentUuid)
    {
        $submitter = Submitter::where('slug', $slug)
            ->with('submission.template')
            ->firstOrFail();

        $template = $submitter->submission->template;
        $doc = collect($submitter->submission->template_documents ?? $template?->documents ?? [])
            ->firstWhere('uuid', $attachmentUuid);

        if (!$doc || !DocumentStorage::exists($doc['storage_path'], $submitter->account_id)) {
            abort(404);
        }

        return response(DocumentStorage::get($doc['storage_path'], $submitter->account_id), 200, [
            'Content-Type'        => $doc['content_type'] ?? 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                basename(str_replace('\\', '/', $doc['filename'] ?? 'document'))
            ),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function update(Request $request, string $slug)
    {
        $validated = $request->validate(['values' => ['sometimes', 'array']]);

        // Serialize repeated clicks and competing requests. The transition and its
        // audit event must commit together; no jobs are dispatched on a retry.
        $completed = DB::transaction(function () use ($slug, $validated): ?Submitter {
            $submitter = Submitter::query()->where('slug', $slug)->lockForUpdate()->firstOrFail();
            $submission = $submitter->submission;
            abort_unless($submission && (int) $submission->account_id === (int) $submitter->account_id, 404);
            abort_if($submission->archived_at || $submission->is_expired, 410, 'Submission is no longer active.');

            if ($submitter->completed_at || $submitter->declined_at) {
                return null;
            }

            if (($submission->submitters_order ?? 'random') !== 'random') {
                $priorPending = $submission->submitters()
                    ->where('order', '<', $submitter->order)
                    ->whereNull('completed_at')
                    ->whereNull('declined_at')
                    ->exists();
                if ($priorPending) {
                    return null;
                }
            }

            // Do not permit one signer to overwrite another signer's PDF fields.
            $incoming = $validated['values'] ?? [];
            $fields = $submission->template_fields ?? $submission->template?->fields ?? [];
            $owned = collect($fields)
                ->filter(fn ($field) => is_array($field) && ($field['submitter_uuid'] ?? null) === $submitter->uuid);
            $allowed = $owned->pluck('uuid')->filter(fn ($uuid) => is_string($uuid))->all();
            abort_if(array_diff(array_keys($incoming), $allowed) !== [], 422, 'Unrecognized signer field.');

            // Preserve media/payment references written by verified server flows.
            // A browser must not be able to claim a payment by submitting an ID.
            $previous = $submitter->values ?? [];
            foreach ($owned as $field) {
                $uuid = $field['uuid'] ?? null;
                if (is_string($uuid) && ($field['type'] ?? null) === 'payment') {
                    abort_if(array_key_exists($uuid, $incoming)
                        && ($incoming[$uuid] !== ($previous[$uuid] ?? null)), 422, 'Unverified payment field.');
                    unset($incoming[$uuid]);
                }
            }
            $values = array_merge($previous, $incoming);
            foreach ($owned as $field) {
                if (! ($field['required'] ?? false) || ! empty($field['conditions'])
                    || in_array($field['type'] ?? '', ['heading', 'formula'], true)) {
                    continue;
                }
                $value = $values[$field['uuid'] ?? ''] ?? null;
                abort_if($value === null || $value === '' || $value === false || $value === [],
                    422, 'A required signer field is missing.');
            }

            $submitter->update(['values' => $values, 'completed_at' => now()]);
            SubmissionEvent::create([
                'submission_id' => $submitter->submission_id,
                'submitter_id' => $submitter->id,
                'account_id' => $submitter->account_id,
                'event_type' => 'form.completed',
                'event_timestamp' => now(),
                'data' => ['ip' => request()->ip(), 'ua' => request()->userAgent()],
            ]);

            return $submitter;
        }, 3);

        if ($completed === null) {
            return redirect()->route('submit-form.show', $slug);
        }

        // The completion email must not race PDF generation. In a chain, the
        // next job starts only after the previous job succeeds.
        Bus::chain([
            new GenerateSubmissionPdfJob($completed->id),
            new GenerateAuditTrailJob($completed->id),
            new GenerateCombinedPdfJob($completed->id),
            new SendSubmissionCompletedJob($completed->id),
        ])->dispatch();

        DispatchWebhookEventJob::dispatch('form.completed', $completed->account_id, $completed->id);
        ReindexSearchEntryJob::dispatch('Submitter', $completed->id);
        ReindexSearchEntryJob::dispatch('Submission', $completed->submission_id);

        if (($completed->submission->submitters_order ?? 'random') !== 'random') {
            $freshSubmitters = Submitter::where('submission_id', $completed->submission_id)->get();
            $this->inviteNextSubmitters($completed, $freshSubmitters);
        }

        return redirect()->route('submit-form.show', $slug);
    }

    private function inviteNextSubmitters(Submitter $justCompleted, \Illuminate\Database\Eloquent\Collection $allSubmitters): void
    {

        // All submitters at the same order as the one who just completed
        $currentGroupDone = $allSubmitters
            ->where('order', $justCompleted->order)
            ->every(fn ($s) => $s->completed_at || $s->declined_at);

        if (!$currentGroupDone) {
            return;
        }

        // Find the next order group that hasn't been sent yet
        $nextOrder = $allSubmitters
            ->where('order', '>', $justCompleted->order)
            ->whereNull('sent_at')
            ->min('order');

        if ($nextOrder === null) {
            return;
        }

        $submission   = $justCompleted->submission()->with(['template', 'createdByUser'])->first();
        $templateName = $submission->template?->name ?? 'Document';
        $senderName   = $submission->createdByUser?->full_name ?? config('app.name', 'Vouch');

        $nextGroup = $allSubmitters->where('order', $nextOrder)->whereNull('sent_at');

        foreach ($nextGroup as $next) {
            $marked = Submitter::whereKey($next->id)->whereNull('sent_at')->update(['sent_at' => now()]);
            if ($marked && ($next->email || $next->phone)) {
                SendSubmitterInvitationJob::dispatch($next->id, $senderName, $templateName);
            }
        }
    }
}
