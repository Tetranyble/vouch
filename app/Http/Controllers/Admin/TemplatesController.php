<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Templates\CloneTemplateAction;
use App\Actions\Templates\CreateTemplateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateTemplateRequest;
use App\Jobs\DispatchWebhookEventJob;
use App\Jobs\ReindexSearchEntryJob;
use App\Models\Template;
use App\Models\TemplateFolder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplatesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Template::with(['author', 'folder'])
            ->forAccount($user->account_id)
            ->active()
            ->latest();

        if ($search = $request->get('q')) {
            $query->search($search);
        }

        $templates = $query->paginate(30)->through(fn ($t) => $this->serializeTemplate($t));
        $folders = TemplateFolder::where('account_id', $user->account_id)->get();

        return Inertia::render('Admin/Templates/Index', [
            'templates' => $templates,
            'folders' => $folders,
            'filters' => ['q' => $request->get('q', '')],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Templates/Create');
    }

    public function store(CreateTemplateRequest $request, CreateTemplateAction $action)
    {
        $template = $action->execute($request->user(), $request->validated('name'));

        return redirect()->route('templates.builder.show', $template->id);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $template = Template::with(['author', 'folder'])
            ->forAccount($user->account_id)
            ->findOrFail($id);

        $submissionsQuery = $template->submissions()
            ->with(['submitters', 'createdByUser'])
            ->active()
            ->latest();

        if (($status = $request->get('status')) && in_array($status, ['pending', 'completed', 'declined', 'expired'], true)) {
            $submissionsQuery->{$status}();
        }

        if ($q = $request->get('q')) {
            $submissionsQuery->search($q);
        }

        $submissions = $submissionsQuery->paginate(20)->through(fn ($s) => $this->serializeSubmission($s));

        return Inertia::render('Admin/Templates/Show', [
            'template' => $this->serializeTemplate($template),
            'submissions' => $submissions,
            'counts' => [
                'all' => $template->submissions()->active()->count(),
                'pending' => $template->submissions()->active()->pending()->count(),
                'completed' => $template->submissions()->active()->completed()->count(),
            ],
            'filters' => [
                'q' => $request->get('q', ''),
                'status' => $request->get('status', ''),
            ],
        ]);
    }

    public function edit(Request $request, string $id)
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($id);

        return Inertia::render('Admin/Templates/Edit', [
            'template' => $this->serializeTemplate($template),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($id);

        $request->validate(['name' => 'sometimes|required|string|max:255']);

        $template->update($request->only(['name', 'external_id', 'preferences']));
        DispatchWebhookEventJob::dispatch('template.updated', $user->account_id);
        ReindexSearchEntryJob::dispatch('Template', $template->id);

        return redirect()->route('templates.show', $id)
            ->with('success', 'Template updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($id);

        if ($request->boolean('permanently')) {
            $template->delete();
            $message = 'Template permanently deleted.';
        } else {
            $template->update(['archived_at' => now()]);
            DispatchWebhookEventJob::dispatch('template.archived', $user->account_id);
            $message = 'Template archived.';
        }

        return redirect()->route('templates.index')->with('success', $message);
    }

    public function restore(Request $request, string $id)
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->archived()->findOrFail($id);
        $template->update(['archived_at' => null]);

        return redirect()->back()->with('success', 'Template restored.');
    }

    public function archived(Request $request)
    {
        $user = $request->user();
        $templates = Template::with(['author', 'folder'])
            ->forAccount($user->account_id)
            ->archived()
            ->latest()
            ->paginate(30)
            ->through(fn ($t) => $this->serializeTemplate($t));

        return Inertia::render('Admin/Templates/Archived', [
            'templates' => $templates,
        ]);
    }

    private function serializeTemplate(Template $template): array
    {
        $firstDoc = collect($template->documents ?? [])->first();
        $thumbnailUrl = null;
        if ($firstDoc && isset($firstDoc['preview_path'])) {
            $thumbnailUrl = route('templates.documents.thumbnail', [$template->id, $firstDoc['uuid']]);
        }

        return [
            'id' => $template->id,
            'slug' => $template->slug,
            'name' => $template->name,
            'source' => $template->source,
            'archived_at' => $template->archived_at,
            'created_at' => $template->created_at,
            'updated_at' => $template->updated_at,
            'folder_name' => $template->folder?->name ?? 'Default',
            'thumbnail_url' => $thumbnailUrl,
            'submitters' => $template->submitters ?? [],
            'preferences' => $template->preferences ?? [],
            'prefillable_fields' => collect($template->fields ?? [])
                ->filter(fn ($f) => !empty($f['prefillable']))
                ->values()
                ->toArray(),
            'author' => [
                'id' => $template->author?->id,
                'name' => $template->author?->full_name ?: $template->author?->email,
                'email' => $template->author?->email,
            ],
        ];
    }

    private function serializeSubmission(\App\Models\Submission $submission): array
    {
        return [
            'id' => $submission->id,
            'slug' => $submission->slug,
            'name' => $submission->name,
            'source' => $submission->source,
            'status' => $submission->status,
            'archived_at' => $submission->archived_at,
            'expire_at' => $submission->expire_at,
            'created_at' => $submission->created_at,
            'submitters' => $submission->submitters->map(fn ($s) => [
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
            ])->toArray(),
            'created_by_user' => $submission->createdByUser ? [
                'name' => $submission->createdByUser->full_name ?: $submission->createdByUser->email,
                'email' => $submission->createdByUser->email,
            ] : null,
        ];
    }
}
