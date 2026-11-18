<?php

namespace App\Http\Controllers\Admin;

use App\Facades\DocumentStorage;
use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class TemplateDocumentsController extends Controller
{
    private const ALLOWED_MIMES = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const MAX_SIZE_MB = 20;

    public function store(Request $request, string $templateId)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimetypes:application/pdf,image/jpeg,image/png,image/gif,image/webp',
                'max:'.(self::MAX_SIZE_MB * 1024),
            ],
        ]);

        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($templateId);

        $file = $request->file('file');
        $attachmentUuid = (string) Str::uuid();
        $storagePath = DocumentStorage::upload($file, $user->account_id, 'template-document', $user->id, ['template_id' => $template->id, 'attachment_uuid' => $attachmentUuid]);

        $isPdf = $file->getMimeType() === 'application/pdf';
        $pageCount = $isPdf ? $this->getPdfPageCount($storagePath, $user->account_id) : 1;

        $document = [
            'uuid' => $attachmentUuid,
            'filename' => $file->getClientOriginalName(),
            'storage_path' => $storagePath,
            'content_type' => $file->getMimeType(),
            'filesize' => $file->getSize(),
            'page_count' => $pageCount,
        ];

        $documents = $template->documents ?? [];
        $documents[] = $document;

        $schemaEntry = [
            'attachment_uuid' => $attachmentUuid,
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
        ];

        $schema = $template->schema ?? [];
        $schema[] = $schemaEntry;

        try {
            $template->update([
                'documents' => $documents,
                'schema' => $schema,
            ]);
        } catch (\Throwable $error) {
            DocumentStorage::delete($storagePath, $user->account_id);
            throw $error;
        }

        return response()->json([
            'uuid' => $attachmentUuid,
            'filename' => $file->getClientOriginalName(),
            'page_count' => $pageCount,
            'content_type' => $file->getMimeType(),
            'url' => route('templates.documents.preview', [$templateId, $attachmentUuid]),
            'schema' => $schema,
        ]);
    }

    public function preview(Request $request, string $templateId, string $attachmentUuid)
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($templateId);

        $doc = collect($template->documents ?? [])->firstWhere('uuid', $attachmentUuid);
        if (! $doc || ! DocumentStorage::exists($doc['storage_path'], $user->account_id)) {
            abort(404);
        }

        return response(DocumentStorage::get($doc['storage_path'], $user->account_id), 200, [
            'Content-Type' => $doc['content_type'] ?? 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                basename(str_replace('\\', '/', $doc['filename'] ?? 'document'))
            ),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, string $templateId, string $attachmentUuid)
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($templateId);

        $documents = collect($template->documents ?? []);
        $doc = $documents->firstWhere('uuid', $attachmentUuid);

        $template->update([
            'documents' => $documents->reject(fn ($d) => $d['uuid'] === $attachmentUuid)->values()->toArray(),
            'schema' => collect($template->schema ?? [])
                ->reject(fn ($s) => ($s['attachment_uuid'] ?? null) === $attachmentUuid)
                ->values()
                ->toArray(),
            'fields' => collect($template->fields ?? [])
                ->map(fn ($f) => [
                    ...$f,
                    'areas' => collect($f['areas'] ?? [])
                        ->reject(fn ($a) => ($a['attachment_uuid'] ?? null) === $attachmentUuid)
                        ->values()
                        ->toArray(),
                ])
                ->toArray(),
        ]);

        // Cloned templates and in-flight submissions may still reference the
        // exact same opaque media identifiers. Removing the template entry is
        // not proof that package media is unreferenced; defer physical deletion
        // until a reference-aware retention/garbage-collection process exists.

        return response()->json(['deleted' => $attachmentUuid]);
    }

    public function storeThumbnail(Request $request, string $templateId, string $attachmentUuid)
    {
        $request->validate(['data' => 'required|string|max:6990520']);

        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($templateId);

        $existingDocument = collect($template->documents ?? [])->firstWhere('uuid', $attachmentUuid);
        abort_unless($existingDocument, 404);

        $dataUrl = $request->input('data');
        if (! str_starts_with($dataUrl, 'data:image/jpeg;base64,')) {
            return response()->json(['error' => 'Invalid image data'], 422);
        }

        [, $base64] = explode(',', $dataUrl, 2);
        $imageData = base64_decode($base64, true);

        if ($imageData === false || strlen($imageData) > 5 * 1024 * 1024 ||
            (new \finfo(FILEINFO_MIME_TYPE))->buffer($imageData) !== 'image/jpeg') {
            return response()->json(['error' => 'Invalid or oversized JPEG thumbnail.'], 422);
        }
        $thumbPath = DocumentStorage::putContent($imageData, $attachmentUuid.'.jpg', $user->account_id, 'template-thumbnail', $user->id, ['template_id' => $template->id]);

        $documents = collect($template->documents ?? [])->map(function ($doc) use ($attachmentUuid, $thumbPath) {
            if ($doc['uuid'] === $attachmentUuid) {
                $doc['preview_path'] = $thumbPath;
            }

            return $doc;
        })->toArray();

        try {
            $template->update(['documents' => $documents]);
        } catch (\Throwable $error) {
            DocumentStorage::delete($thumbPath, $user->account_id);
            throw $error;
        }

        // Preserve the previous thumbnail: template clones can share its media
        // reference. Reclamation must be done by a separate reference-aware job.

        return response()->json(['ok' => true]);
    }

    public function thumbnail(Request $request, string $templateId, string $attachmentUuid)
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($templateId);

        $doc = collect($template->documents ?? [])->firstWhere('uuid', $attachmentUuid);
        $thumbPath = $doc['preview_path'] ?? null;

        if (! $thumbPath || ! DocumentStorage::exists($thumbPath, $user->account_id)) {
            abort(404);
        }

        return response(DocumentStorage::get($thumbPath, $user->account_id), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function getPdfPageCount(string $storagePath, int $accountId): int
    {
        try {
            $content = DocumentStorage::get($storagePath, $accountId);
            $count = preg_match_all('/\/Type[\s]*\/Page[^s]/', $content, $matches);

            return max(1, $count ?: 1);
        } catch (\Throwable) {
            return 1;
        }
    }
}
