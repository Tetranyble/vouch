<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Submitter;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StartFormController extends Controller
{
    public function show(string $slug)
    {
        $template = Template::where('slug', $slug)->where('shared_link', true)->whereNull('archived_at')->firstOrFail();

        return Inertia::render('Public/StartForm/Show', [
            'template' => ['id' => $template->id, 'name' => $template->name, 'slug' => $template->slug],
            'submitters' => $template->submitters ?? [],
        ]);
    }

    public function update(Request $request, string $slug)
    {
        $template = Template::where('slug', $slug)
            ->where('shared_link', true)
            ->whereNull('archived_at')
            ->firstOrFail();

        $roles = $template->submitters ?? [];
        abort_if($roles === [], 422, 'This template has no signers.');

        $validated = $request->validate([
            'submitters' => ['sometimes', 'array'],
            'submitters.*.name' => ['nullable', 'string', 'max:255'],
            'submitters.*.email' => ['nullable', 'email', 'max:255'],
        ]);
        $input = $validated['submitters'] ?? [];

        $firstSubmitter = DB::transaction(function () use ($template, $roles, $input) {
            $submission = Submission::create([
                'template_id' => $template->id,
                'account_id' => $template->account_id,
                'created_by_user_id' => auth()->user() && (int) auth()->user()->account_id === (int) $template->account_id
                    ? auth()->id() : null,
                'source' => 'link',
                'template_fields' => $template->fields,
                'template_documents' => $template->documents,
                'template_schema' => $template->schema,
                'template_submitters' => $roles,
            ]);

            $firstSubmitter = null;
            foreach ($roles as $index => $role) {
                $contact = $input[$index] ?? [];
                $submitter = Submitter::create([
                    'submission_id' => $submission->id,
                    'uuid' => $role['uuid'],
                    'name' => $contact['name'] ?? null,
                    'email' => $contact['email'] ?? null,
                    'slug' => Str::random(14),
                    'account_id' => $template->account_id,
                    'order' => $index,
                ]);
                $firstSubmitter ??= $submitter;
            }

            return $firstSubmitter;
        });

        return redirect()->route('submit-form.show', $firstSubmitter->slug);
    }
}
