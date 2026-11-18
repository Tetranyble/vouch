<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplateBuilderController extends Controller
{
    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $template = Template::with(['author', 'folder'])
            ->forAccount($user->account_id)
            ->findOrFail($id);

        $documents = collect($template->documents ?? [])->map(fn ($doc) => [
            'uuid' => $doc['uuid'],
            'filename' => $doc['filename'] ?? '',
            'content_type' => $doc['content_type'] ?? 'application/pdf',
            'page_count' => $doc['page_count'] ?? 1,
            'url' => route('templates.documents.preview', [$template->id, $doc['uuid']]),
        ])->values()->all();

        return Inertia::render('Admin/Templates/Builder', [
            'template' => [
                'id' => $template->id,
                'slug' => $template->slug,
                'name' => $template->name,
                'fields' => $template->fields ?? [],
                'schema' => $template->schema ?? [],
                'submitters' => $template->submitters ?? [],
                'preferences' => $template->preferences ?? [],
                'documents' => $documents,
            ],
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($id);

        $template->update([
            'name' => $request->input('name', $template->name),
            'fields' => $request->input('fields', $template->fields),
            'schema' => $request->input('schema', $template->schema),
            'submitters' => $request->input('submitters', $template->submitters),
            'preferences' => $request->input('preferences', $template->preferences),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['saved' => true]);
        }

        return redirect()->route('templates.show', $template->id)
            ->with('success', 'Template saved successfully.');
    }
}
