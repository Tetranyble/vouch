<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Submitter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Facades\DocumentStorage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SubmitFormAttachmentsController extends Controller
{
    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];

    private const MAX_MB = 20;

    public function store(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:'.implode(',', self::ALLOWED_MIMES), 'max:'.(self::MAX_MB * 1024)],
        ]);

        $submitter = Submitter::where('slug', $slug)->firstOrFail();

        if ($submitter->completed_at || $submitter->declined_at) {
            return response()->json(['error' => 'Form is already closed'], 422);
        }

        $file = $request->file('file');
        $uuid = (string) Str::uuid();
        $path = DocumentStorage::upload($file, $submitter->account_id, 'signer-attachment', null, [
            'submitter_id' => $submitter->id, 'attachment_uuid' => $uuid,
        ]);
        try {
            DB::transaction(function () use ($submitter, $uuid, $path): void {
                $locked = Submitter::whereKey($submitter->id)->lockForUpdate()->firstOrFail();
                abort_if($locked->completed_at || $locked->declined_at, 422, 'Form is already closed.');
                $metadata = $locked->metadata ?? [];
                $metadata['uploaded_attachments'][$uuid] = $path;
                $locked->update(['metadata' => $metadata]);
            }, 3);
        } catch (\Throwable $error) {
            DocumentStorage::delete($path, $submitter->account_id);
            throw $error;
        }

        return response()->json([
            'uuid'         => $uuid,
            'filename'     => $file->getClientOriginalName(),
            'content_type' => $file->getMimeType(),
            'url'          => route('submit-form.attachments.show', [$slug, $uuid]),
        ], 201);
    }

    public function show(string $slug, string $uuid): mixed
    {
        abort_unless(Str::isUuid($uuid), 404);
        $submitter = Submitter::where('slug', $slug)->firstOrFail();

        $path = ($submitter->metadata ?? [])['uploaded_attachments'][$uuid] ?? null;
        if (! is_string($path) || ! DocumentStorage::isMediaReference($path)) {
            abort(404);
        }

        if (!$path || !DocumentStorage::exists($path, $submitter->account_id)) {
            abort(404);
        }

        abort_unless(DocumentStorage::findMedia($path, $submitter->account_id), 404);
        $mimeType = DocumentStorage::mimeType($path, $submitter->account_id) ?: 'application/octet-stream';

        return response(DocumentStorage::get($path, $submitter->account_id), 200, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'attachment',
            'Cache-Control'       => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
