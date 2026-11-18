<?php

declare(strict_types=1);

namespace Tests\Feature\Storage;

use App\Models\CompletedDocument;
use App\Models\Submission;
use App\Models\Submitter;
use App\Services\CompletedDocumentWriter;
use App\Services\DocumentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompletedDocumentWriterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    #[Test]
    public function retries_keep_one_record_and_preserve_unchanged_media_links(): void
    {
        $submission = Submission::factory()->create();
        $signer = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $submission->account_id,
        ]);
        $writer = app(CompletedDocumentWriter::class);
        $storage = app(DocumentStorage::class);

        $first = $writer->write($signer, 'signed.pdf', 'doc-1', 'content v1', 'signed-document');
        $second = $writer->write($signer, 'signed.pdf', 'doc-1', 'content v1', 'signed-document');
        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->storage_path, $second->storage_path);
        $this->assertSame(1, CompletedDocument::where('submitter_id', $signer->id)->count());
        $this->assertSame('content v1', $storage->get($first->storage_path, $signer->account_id));
    }

    #[Test]
    public function changed_content_replaces_the_record_and_retires_old_private_media(): void
    {
        $submission = Submission::factory()->create();
        $signer = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $submission->account_id,
        ]);
        $writer = app(CompletedDocumentWriter::class);
        $storage = app(DocumentStorage::class);

        $first = $writer->write($signer, 'combined.pdf', null, 'content v1', 'combined-pdf');
        $oldReference = $first->storage_path;
        $updated = $writer->write($signer, 'combined.pdf', null, 'content v2', 'combined-pdf');

        $this->assertSame($first->id, $updated->id);
        $this->assertNotSame($oldReference, $updated->storage_path);
        $this->assertFalse($storage->exists($oldReference, $signer->account_id));
        $this->assertSame('content v2', $storage->get($updated->storage_path, $signer->account_id));
    }

    #[Test]
    public function mismatched_submitter_and_submission_workspaces_cannot_write_media(): void
    {
        $submission = Submission::factory()->create();
        $unrelated = \App\Models\Account::factory()->create();
        $signer = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $unrelated->id,
        ]);
        $this->expectException(\RuntimeException::class);
        app(CompletedDocumentWriter::class)->write($signer, 'signed.pdf', null, 'bytes', 'signed-document');
    }
}
