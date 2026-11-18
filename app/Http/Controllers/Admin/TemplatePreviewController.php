<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplatePreviewController extends Controller
{
    public function show(Request $request, string $id)
    {
        $user     = $request->user();
        $template = Template::with(['author'])
            ->forAccount($user->account_id)
            ->findOrFail($id);

        $documents = collect($template->documents ?? [])->map(fn ($doc) => [
            'uuid'         => $doc['uuid'],
            'filename'     => $doc['filename'] ?? '',
            'content_type' => $doc['content_type'] ?? 'application/pdf',
            'page_count'   => $doc['page_count'] ?? 1,
            'url'          => route('templates.documents.preview', [$template->id, $doc['uuid']]),
        ])->values()->all();

        return Inertia::render('Admin/Templates/Preview', [
            'template' => [
                'id'         => $template->id,
                'name'       => $template->name,
                'fields'     => $template->fields ?? [],
                'submitters' => $template->submitters ?? [],
                'documents'  => $documents,
            ],
        ]);
    }
}

