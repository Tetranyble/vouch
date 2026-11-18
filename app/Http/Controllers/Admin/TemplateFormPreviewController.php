<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplateFormPreviewController extends Controller
{
    public function show(Request $request, string $id)
    {
        $user     = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($id);

        $submitters   = $template->submitters ?? [];
        $targetUuid   = $request->get('uuid', $submitters[0]['uuid'] ?? null);
        $allFields    = $template->fields ?? [];
        $schema       = $template->schema ?? [];
        $documents    = $template->documents ?? [];

        $submitterFields = collect($allFields)
            ->filter(fn ($f) => ($f['submitter_uuid'] ?? null) === $targetUuid)
            ->values()
            ->all();

        $documentList = collect($documents)->map(fn ($doc) => [
            'uuid'         => $doc['uuid'],
            'filename'     => $doc['filename'] ?? '',
            'content_type' => $doc['content_type'] ?? 'application/pdf',
            'page_count'   => $doc['page_count'] ?? 1,
            'url'          => route('templates.documents.preview', [$template->id, $doc['uuid']]),
        ])->values()->all();

        $allSubmitters = collect($submitters)->map(fn ($s, $i) => [
            'uuid'       => $s['uuid'],
            'name'       => $s['name'] ?? "Signer " . ($i + 1),
            'email'      => null,
            'status'     => $s['uuid'] === $targetUuid ? 'pending' : 'awaiting',
            'order'      => $i,
            'is_current' => $s['uuid'] === $targetUuid,
        ])->values()->all();

        return Inertia::render('Public/SubmitForm/Show', [
            'submitter'     => ['id' => null, 'slug' => null, 'uuid' => $targetUuid, 'name' => null, 'email' => null],
            'fields'        => $submitterFields,
            'documents'     => $documentList,
            'schema'        => $schema,
            'template'      => ['id' => $template->id, 'name' => $template->name],
            'isCompleted'   => false,
            'isDeclined'    => false,
            'isAwaiting'    => false,
            'isPreview'     => true,
            'allSubmitters' => $allSubmitters,
            'previewRoles'  => collect($submitters)->map(fn ($s, $i) => [
                'uuid' => $s['uuid'],
                'name' => $s['name'] ?? "Signer " . ($i + 1),
            ])->values()->all(),
        ]);
    }
}
