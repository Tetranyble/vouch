<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompletedDocument;
use App\Models\Submitter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Persist generated PDFs once per submitter/document identity.
 * Media bytes live in Tetranyble; Eloquent only stores the opaque reference.
 */
final class CompletedDocumentWriter
{
    public function __construct(private readonly DocumentStorage $documents) {}

    public function write(
        Submitter $submitter,
        string $filename,
        ?string $attachmentUuid,
        string $contents,
        string $purpose,
    ): CompletedDocument {
        $submission = $submitter->submission;
        if (! $submission || (int) $submission->account_id !== (int) $submitter->account_id) {
            throw new RuntimeException('Submission and signer workspaces do not match.');
        }

        $accountId = (int) $submitter->account_id;
        $hash = hash('sha256', $contents);
        $existing = CompletedDocument::query()
            ->where('submitter_id', $submitter->id)
            ->where('attachment_uuid', $attachmentUuid)
            ->when($attachmentUuid === null, fn ($query) => $query->where('filename', $filename))
            ->first();
        if ($existing && $existing->filename === $filename && $existing->sha256 === $hash &&
            $existing->storage_path && $this->documents->exists($existing->storage_path, $accountId)) {
            return $existing;
        }

        $newReference = $this->documents->putContent($contents, $filename, $accountId, $purpose);
        $oldReference = null;
        $reusedPrevious = false;
        try {
            $record = DB::transaction(function () use ($submitter, $filename, $attachmentUuid, $contents, $hash, $newReference, $accountId, &$oldReference, &$reusedPrevious): CompletedDocument {
                // Lock the parent row so multiple retrying queue workers serialize,
                // even if no completed document exists yet to lock.
                Submitter::query()->whereKey($submitter->id)->lockForUpdate()->firstOrFail();
                $record = CompletedDocument::query()
                    ->where('submitter_id', $submitter->id)
                    ->where('attachment_uuid', $attachmentUuid)
                    ->when($attachmentUuid === null, fn ($query) => $query->where('filename', $filename))
                    ->first();

                if ($record && $record->sha256 === $hash && $record->storage_path &&
                    $this->documents->exists($record->storage_path, $accountId)) {
                    // A retry with unchanged content must preserve existing signed links.
                    $reusedPrevious = true;
                    if ($record->filename !== $filename) {
                        $record->update(['filename' => $filename]);
                    }

                    return $record;
                }

                $attributes = [
                    'filename' => $filename,
                    'storage_path' => $newReference,
                    'filesize' => strlen($contents),
                    'sha256' => $hash,
                ];
                if ($record) {
                    $oldReference = $record->storage_path;
                    $record->update($attributes);
                } else {
                    $record = CompletedDocument::query()->create([
                        'submitter_id' => $submitter->id,
                        'attachment_uuid' => $attachmentUuid,
                        'filename' => $filename,
                        ...$attributes,
                    ]);
                }

                return $record;
            });
        } catch (\Throwable $error) {
            $this->deleteObsolete($newReference, $accountId);
            throw $error;
        }

        if ($reusedPrevious) {
            $this->deleteObsolete($newReference, $accountId);

            return $record;
        }

        if ($oldReference && $oldReference !== $newReference &&
            ! CompletedDocument::query()->where('storage_path', $oldReference)->exists()) {
            $this->deleteObsolete($oldReference, $accountId);
        }

        return $record;
    }

    private function deleteObsolete(string $reference, int $accountId): void
    {
        try {
            if ($this->documents->exists($reference, $accountId)) {
                $this->documents->delete($reference, $accountId);
            }
        } catch (\Throwable $error) {
            // Never lose a successful DB commit because stale media cleanup failed.
            // Operations can reconcile the logged obsolete reference later.
            Log::warning('Vouch generated media cleanup failed', [
                'reference' => $reference,
                'account_id' => $accountId,
                'exception' => $error::class,
            ]);
        }
    }
}
