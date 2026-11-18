<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use RuntimeException;
use Tetranyble\Storage\Http\Adapters\LaravelIncomingFile;
use Tetranyble\Storage\Modules\Media\Application\UploadMedia;
use Tetranyble\Storage\Modules\Media\Infrastructure\Persistence\Eloquent\Models\Media;
use Tetranyble\Storage\Modules\Media\Infrastructure\Storage\Media\MediaDeletionService;
use Tetranyble\Storage\Modules\Storage\Application\Contracts\FileSystemContract;
use Tetranyble\Storage\Modules\Storage\Application\DTO\MediaUploadOptions;
use Tetranyble\Storage\Modules\Storage\Domain\Enums\Disk;

/**
 * Single application boundary for document bytes.
 * Documents are private, workspace-owned Tetranyble media. There is no legacy disk fallback.
 */
final class DocumentStorage
{
    public function __construct(
        private readonly UploadMedia $uploads,
        private readonly FileSystemContract $files,
        private readonly MediaDeletionService $deletion,
    ) {}

    public function upload(UploadedFile $file, int $accountId, string $purpose, ?int $userId = null, array $properties = []): string
    {
        $workspace = Account::query()->findOrFail($accountId);
        $media = $this->uploads->handle(
            $workspace,
            LaravelIncomingFile::fromUploadedFile($file),
            MediaUploadOptions::forStandalone(
                workspaceId: $accountId,
                userId: $userId,
                disk: Disk::PRIVATE,
                directory: 'vouch/'.preg_replace('/[^a-z0-9_-]/', '-', strtolower($purpose)),
                module: 'vouch',
                temporary: false,
                customProperties: $properties,
            ),
            $userId === null ? null : $workspace->users()->findOrFail($userId),
        );

        return 'media:'.$media->getKey();
    }

    public function putContent(string $content, string $filename, int $accountId, string $purpose, ?int $userId = null, array $properties = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'vouch_media_');
        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary media file.');
        }
        try {
            if (file_put_contents($path, $content) === false) {
                throw new RuntimeException('Unable to write a temporary media file.');
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';

            return $this->upload(new UploadedFile($path, $filename, $mime, null, true), $accountId, $purpose, $userId, $properties);
        } finally {
            @unlink($path);
        }
    }

    public function isMediaReference(string $reference): bool
    {
        return (bool) preg_match('/^media:[1-9][0-9]*$/D', $reference);
    }

    public function findMedia(string $reference, int $accountId): ?Media
    {
        if (! $this->isMediaReference($reference)) {
            return null;
        }
        $query = Media::query()->whereKey((int) substr($reference, 6));
        $query->where('workspace_id', $accountId);

        return $query->first();
    }

    private function media(string $reference, int $accountId): Media
    {
        return $this->findMedia($reference, $accountId) ?? throw new RuntimeException('Document not found in this account.');
    }

    public function exists(?string $reference, int $accountId): bool
    {
        if (! is_string($reference) || $reference === '') {
            return false;
        }
        if ($this->isMediaReference($reference)) {
            $media = $this->findMedia($reference, $accountId);

            return $media !== null && $this->files->exists($media->path, $media->disk);
        }

        return false;
    }

    public function get(string $reference, int $accountId): string
    {
        if ($this->isMediaReference($reference)) {
            $media = $this->media($reference, $accountId);

            return $this->files->get($media->path, $media->disk);
        }
        throw new InvalidArgumentException('Expected a Tetranyble media reference.');
    }

    public function size(string $reference, int $accountId): int
    {
        if ($this->isMediaReference($reference)) {
            $media = $this->media($reference, $accountId);

            return (int) $media->size;
        }
        throw new InvalidArgumentException('Expected a Tetranyble media reference.');
    }

    public function mimeType(string $reference, int $accountId): ?string
    {
        if ($this->isMediaReference($reference)) {
            return $this->media($reference, $accountId)->mime_type;
        }
        throw new InvalidArgumentException('Expected a Tetranyble media reference.');
    }

    public function url(string $reference, int $accountId): string
    {
        if ($this->isMediaReference($reference)) {
            $media = $this->media($reference, $accountId);

            // Private files must never be exposed by a static public filesystem URL.
            return URL::temporarySignedRoute('media.signed-download', now()->addHour(), [
                'media' => $media->id,
                'account' => $accountId,
            ]);
        }
        throw new InvalidArgumentException('Expected a Tetranyble media reference.');
    }

    public function download(string $reference, int $accountId, ?string $filename = null): \Symfony\Component\HttpFoundation\Response
    {
        if ($this->isMediaReference($reference)) {
            $media = $this->media($reference, $accountId);

            return response($this->get($reference, $accountId), 200, [
                'Content-Type' => $media->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => \Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition(
                    \Symfony\Component\HttpFoundation\HeaderUtils::DISPOSITION_ATTACHMENT,
                    $filename ?? $media->original_name ?? 'document',
                ),
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }
        throw new InvalidArgumentException('Expected a Tetranyble media reference.');
    }

    public function delete(string $reference, int $accountId): bool
    {
        if ($this->isMediaReference($reference)) {
            $this->deletion->delete($this->media($reference, $accountId));

            return true;
        }
        throw new InvalidArgumentException('Expected a Tetranyble media reference.');
    }
}
