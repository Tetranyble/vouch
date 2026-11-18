<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CompletedDocument;
use App\Models\Submitter;
use App\Services\DocumentStorage;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

/**
 * Download signed documents, never granting access merely from a signing-page slug.
 * A valid, expiring URL signature (emailed to the signer) or an authenticated user
 * belonging to the owning account is required. All storage I/O goes through
 * the workspace-scoped Tetranyble adapter.
 */
final class SubmitFormDownloadController extends Controller
{
    private const MAX_ZIP_BYTES = 64 * 1024 * 1024;
    private const MAX_ZIP_DOCUMENTS = 50;

    public function __construct(private readonly DocumentStorage $documents) {}

    public function index(Request $request, string $slug): Response
    {
        $submitter = Submitter::query()
            ->where('slug', $slug)
            ->with(['submission.account', 'completedDocuments'])
            ->firstOrFail();

        // Do not reveal whether the slug identifies a valid signing record.
        $submission = $submitter->submission;
        abort_unless($submission && $submission->account && ! $submission->account->archived_at, 404);
        abort_unless((int) $submitter->account_id === (int) $submission->account_id, 404);

        $sameAccountUser = $request->user()
            && (int) $request->user()->account_id === (int) $submitter->account_id;
        abort_unless($sameAccountUser || $request->hasValidSignature(), 404);
        abort_unless($submitter->completed_at, 404, 'Submission not yet completed.');

        $accountId = (int) $submitter->account_id;
        $docs = $submitter->completedDocuments;

        $originals = $docs->filter(fn (CompletedDocument $doc): bool =>
            $doc->storage_path && ! in_array($doc->filename, ['audit_trail.pdf', 'combined.pdf'], true)
        )->values();

        if ($originals->count() === 1) {
            return $this->displayPdf($originals->first(), $accountId);
        }

        $combined = $docs->firstWhere('filename', 'combined.pdf');
        if ($combined && $this->documents->exists($combined->storage_path, $accountId)) {
            return $this->displayPdf($combined, $accountId);
        }

        abort_if($originals->isEmpty(), 404, 'No completed documents found.');
        abort_if($originals->count() > self::MAX_ZIP_DOCUMENTS, 413, 'Too many documents for one download.');

        // ZIP is a transient delivery artifact, not persisted application storage.
        // Do not perform expensive PDF assembly on a publicly reachable GET request.
        $estimatedSize = $originals->sum(function (CompletedDocument $doc) use ($accountId): int {
            abort_unless($this->documents->exists($doc->storage_path, $accountId), 404);
            return $this->documents->size($doc->storage_path, $accountId);
        });
        abort_if($estimatedSize > self::MAX_ZIP_BYTES, 413, 'Download exceeds the ZIP size limit.');

        $tmpZip = tempnam(sys_get_temp_dir(), 'vouch_export_');
        if ($tmpZip === false) {
            throw new RuntimeException('Unable to create a temporary ZIP.');
        }
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpZip);
            throw new RuntimeException('Unable to open temporary ZIP.');
        }

        try {
            foreach ($originals as $doc) {
                // Prevent path traversal / directory entries and duplicate filenames.
                $filename = $this->safeFilename($doc->filename, 'document-'.$doc->id.'.pdf');
                $filename = $doc->id.'-'.$filename;
                if (! $zip->addFromString($filename, $this->documents->get($doc->storage_path, $accountId))) {
                    throw new RuntimeException('Unable to add a signed document to the ZIP.');
                }
            }
            if (! $zip->close()) {
                throw new RuntimeException('Unable to finalize the signed document ZIP.');
            }
        } catch (\Throwable $error) {
            @$zip->close();
            @unlink($tmpZip);
            throw $error;
        }

        return response()->download($tmpZip, 'signed-documents.zip', [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    private function displayPdf(CompletedDocument $doc, int $accountId): Response
    {
        abort_unless($doc->storage_path && $this->documents->exists($doc->storage_path, $accountId), 404);

        $filename = $this->safeFilename($doc->filename, 'signed-document.pdf');
        return response($this->documents->get($doc->storage_path, $accountId), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $filename),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    private function safeFilename(?string $filename, string $fallback): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename(str_replace('\\', '/', $filename ?? '')));
        return ($safe === '' || $safe === '.' || $safe === '..') ? $fallback : $safe;
    }
}
