<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Submissions\CreateSubmissionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateSubmissionRequest;
use App\Jobs\DispatchWebhookEventJob;
use App\Models\Submission;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubmissionsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Submission::with(['submitters', 'template', 'createdByUser'])
            ->forAccount($user->account_id)
            ->active()
            ->latest();

        if ($status = $request->get('status')) {
            match ($status) {
                'pending' => $query->pending(),
                'completed' => $query->completed(),
                'declined' => $query->declined(),
                default => null,
            };
        }

        if ($q = $request->get('q')) {
            $query->search($q);
        }

        $submissions = $query->paginate(20)->through(fn ($s) => $this->serializeSubmission($s));

        return Inertia::render('Admin/Submissions/Index', [
            'submissions' => $submissions,
            'counts' => [
                'all' => Submission::forAccount($user->account_id)->active()->count(),
                'pending' => Submission::forAccount($user->account_id)->active()->pending()->count(),
                'completed' => Submission::forAccount($user->account_id)->active()->completed()->count(),
                'declined' => Submission::forAccount($user->account_id)->active()->declined()->count(),
            ],
            'filters' => [
                'q' => $request->get('q', ''),
                'status' => $request->get('status', ''),
            ],
        ]);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $submission = Submission::with(['submitters', 'template.author', 'createdByUser'])
            ->forAccount($user->account_id)
            ->findOrFail($id);

        $events = \App\Models\SubmissionEvent::where('submission_id', $submission->id)
            ->with('submitter')
            ->orderBy('event_timestamp')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'event_type' => $e->event_type,
                'event_timestamp' => $e->event_timestamp,
                'data' => $e->data ?? [],
                'submitter_email' => $e->submitter?->email,
                'submitter_name' => $e->submitter?->name,
            ]);

        return Inertia::render('Admin/Submissions/Show', [
            'submission' => $this->serializeSubmission($submission, detailed: true),
            'events' => $events,
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $submission = Submission::forAccount($user->account_id)->findOrFail($id);

        if ($request->boolean('permanently')) {
            $submission->delete();
            $message = 'Submission permanently deleted.';
        } else {
            $submission->update(['archived_at' => now()]);
            DispatchWebhookEventJob::dispatch('submission.archived', $user->account_id, null, $submission->id);
            $message = 'Submission archived.';
        }

        $fallback = $submission->template_id
            ? route('templates.show', $submission->template_id)
            : route('submissions.index');

        return redirect($fallback)->with('success', $message);
    }

    public function restore(Request $request, string $id)
    {
        $user = $request->user();
        $submission = Submission::forAccount($user->account_id)->archived()->findOrFail($id);
        $submission->update(['archived_at' => null]);

        return redirect()->back()->with('success', 'Submission restored.');
    }

    public function archived(Request $request)
    {
        $user = $request->user();
        $submissions = Submission::with(['submitters', 'template'])
            ->forAccount($user->account_id)
            ->archived()
            ->latest()
            ->paginate(20)
            ->through(fn ($s) => $this->serializeSubmission($s));

        return Inertia::render('Admin/Submissions/Archived', [
            'submissions' => $submissions,
        ]);
    }

    public function store(CreateSubmissionRequest $request, string $templateId, CreateSubmissionAction $action)
    {
        $template = Template::forAccount($request->user()->account_id)->findOrFail($templateId);

        $action->execute(
            template: $template,
            creator: $request->user(),
            submitterInputs: $request->allSubmitterInputs(),
            source: 'invite',
            expireAt: $request->input('expire_at'),
            submittersOrder: $request->boolean('preserve_order') ? 'preserved' : 'random',
        );

        return redirect()->route('templates.show', $templateId)
            ->with('success', 'Invitation sent successfully.');
    }

    private function serializeSubmission(Submission $submission, bool $detailed = false): array
    {
        $data = [
            'id' => $submission->id,
            'slug' => $submission->slug,
            'name' => $submission->name,
            'source' => $submission->source,
            'status' => $submission->status,
            'archived_at' => $submission->archived_at,
            'expire_at' => $submission->expire_at,
            'is_expired' => $submission->is_expired,
            'created_at' => $submission->created_at,
            'template_id' => $submission->template_id,
            'template_name' => $submission->template?->name,
            'submitters' => $submission->submitters->map(function ($s) use ($submission) {
                $roleNames = collect($submission->template_submitters ?? [])->keyBy('uuid');

                return [
                    'id' => $s->id,
                    'slug' => $s->slug,
                    'name' => $s->name,
                    'email' => $s->email,
                    'phone' => $s->phone,
                    'status' => $s->status,
                    'completed_at' => $s->completed_at,
                    'declined_at' => $s->declined_at,
                    'sent_at' => $s->sent_at,
                    'opened_at' => $s->opened_at,
                    'display_name' => $s->display_name,
                    'role_name' => $roleNames[$s->uuid]['name'] ?? null,
                ];
            })->toArray(),
            'created_by_user' => $submission->createdByUser ? [
                'name' => $submission->createdByUser->full_name ?: $submission->createdByUser->email,
                'email' => $submission->createdByUser->email,
            ] : null,
        ];

        if ($detailed) {
            $data['template'] = $submission->template ? [
                'id' => $submission->template->id,
                'name' => $submission->template->name,
                'slug' => $submission->template->slug,
                'fields' => $submission->template->fields,
                'submitters' => $submission->template->submitters,
            ] : null;
        }

        return $data;
    }
}
