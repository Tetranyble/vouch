<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submitter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Facades\DocumentStorage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AttachmentsController extends Controller
{
    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];
    private const MAX_MB = 20;

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'submitter_slug' => 'required|string',
            'file'           => ['required', 'file', 'mimetypes:'.implode(',', self::ALLOWED_MIMES), 'max:'.(self::MAX_MB * 1024)],
            'type'           => 'sometimes|string|max:255',
            'field_uuid'     => 'sometimes|uuid',
        ]);

        $submitter = Submitter::where('slug', $request->input('submitter_slug'))->firstOrFail();

        // Ensure the authenticated API token belongs to the same account
        $accountId = $request->user()->account_id;
        if ($submitter->account_id !== $accountId) {
            abort(403, 'Forbidden.');
        }

        abort_if($submitter->completed_at || $submitter->declined_at, 422, 'Form is already closed.');

        $file    = $request->file('file');
        $uuid    = (string) Str::uuid();
        $path = DocumentStorage::upload($file, $submitter->account_id, 'signer-attachment', $request->user()->id, [
            'submitter_id' => $submitter->id, 'attachment_uuid' => $uuid,
        ]);
        try {
            DB::transaction(function () use ($submitter, $request, $uuid, $path): void {
                $locked = Submitter::whereKey($submitter->id)->lockForUpdate()->firstOrFail();
                abort_if($locked->completed_at || $locked->declined_at, 422, 'Form is already closed.');
                $metadata = $locked->metadata ?? [];
                $metadata['uploaded_attachments'][$uuid] = $path;
                // Commit media attachment and field value atomically without
                // dropping another concurrent attachment update.
                $changes = ['metadata' => $metadata];
                if ($fieldUuid = $request->input('field_uuid')) {
                    $values = $locked->values ?? [];
                    $values[$fieldUuid] = $path;
                    $changes['values'] = $values;
                }
                $locked->update($changes);
            }, 3);
        } catch (\Throwable $error) {
            DocumentStorage::delete($path, $submitter->account_id);
            throw $error;
        }

        return response()->json([
            'uuid'         => $uuid,
            'filename'     => $file->getClientOriginalName(),
            'content_type' => $file->getMimeType(),
            'url'          => DocumentStorage::url($path, $accountId),
        ], 201);
    }
}
