<?php

declare(strict_types=1);

namespace Tests\Feature\Storage;

use App\Models\Account;
use App\Models\User;
use App\Services\DocumentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tetranyble\Storage\Modules\Media\Infrastructure\Persistence\Eloquent\Models\Media;

class DocumentStorageIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    #[Test]
    public function it_registers_uploads_in_tetranyble_and_scopes_them_to_the_account(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        $otherAccount = Account::factory()->create();
        $storage = app(DocumentStorage::class);
        $source = UploadedFile::fake()->createWithContent('agreement.pdf', "%PDF-1.4\n%%EOF");

        $reference = $storage->upload($source, $account->id, 'template-document', $user->id);
        $this->assertTrue($storage->isMediaReference($reference));
        $this->assertSame("%PDF-1.4\n%%EOF", $storage->get($reference, $account->id));
        $this->assertNull($storage->findMedia($reference, $otherAccount->id));

        $media = $storage->findMedia($reference, $account->id);
        $this->assertInstanceOf(Media::class, $media);
        $this->assertTrue($storage->exists($reference, $account->id));
        $this->assertSame($account->id, $media->workspace_id);
        $this->assertGreaterThan(0, $account->fresh()->storage_used_bytes);
    }

    #[Test]
    public function every_storage_operation_rejects_media_from_another_workspace(): void
    {
        $account = Account::factory()->create();
        $unrelated = Account::factory()->create();
        $storage = app(DocumentStorage::class);
        $reference = $storage->putContent('secret data', 'secret.txt', $account->id, 'document');

        $this->assertFalse($storage->exists($reference, $unrelated->id));
        $this->assertNull($storage->findMedia($reference, $unrelated->id));

        foreach ([
            fn () => $storage->get($reference, $unrelated->id),
            fn () => $storage->size($reference, $unrelated->id),
            fn () => $storage->mimeType($reference, $unrelated->id),
            fn () => $storage->url($reference, $unrelated->id),
            fn () => $storage->download($reference, $unrelated->id),
            fn () => $storage->delete($reference, $unrelated->id),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('Cross-workspace access unexpectedly succeeded.');
            } catch (\RuntimeException $expected) {
                $this->assertSame('Document not found in this account.', $expected->getMessage());
            }
        }
        $this->assertSame('secret data', $storage->get($reference, $account->id));
    }

    #[Test]
    public function it_downloads_private_media_only_with_a_valid_signed_link(): void
    {
        $account = Account::factory()->create();
        $storage = app(DocumentStorage::class);
        $reference = $storage->putContent('private file', 'secret.txt', $account->id, 'document');
        $media = $storage->findMedia($reference, $account->id);

        $this->get('/media/signed/'.$media->id)->assertForbidden();
        $this->get($storage->url($reference, $account->id))->assertOk()->assertSee('private file');
    }

    #[Test]
    public function a_signed_link_cannot_be_reused_for_another_workspace(): void
    {
        $account = Account::factory()->create();
        $other = Account::factory()->create();
        $storage = app(DocumentStorage::class);
        $reference = $storage->putContent('private tenant bytes', 'private.pdf', $account->id, 'document');
        $media = $storage->findMedia($reference, $account->id);

        $this->get('/media/signed/'.$media->id)->assertForbidden();
        $this->get('/media/signed/'.$media->id.'?account='.$account->id)->assertForbidden();

        $valid = $storage->url($reference, $account->id);
        $this->get($valid)->assertOk();
        $this->get(str_replace('account='.$account->id, 'account='.$other->id, $valid))
            ->assertForbidden();
    }

    #[Test]
    public function raw_filesystem_paths_are_rejected_and_no_legacy_route_exists(): void
    {
        Storage::disk('local')->put('vouch/templates/old.pdf', 'old-file');
        $storage = app(DocumentStorage::class);
        $account = Account::factory()->create();

        $this->assertFalse($storage->exists('vouch/templates/old.pdf', $account->id));
        $this->expectException(\InvalidArgumentException::class);
        $storage->get('vouch/templates/old.pdf', $account->id);
    }

    #[Test]
    public function old_download_endpoint_is_not_registered(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('media.legacy-signed-download'));
    }

    #[Test]
    public function expired_and_tampered_private_links_are_rejected(): void
    {
        $account = Account::factory()->create();
        $storage = app(DocumentStorage::class);
        $reference = $storage->putContent('confidential', 'secure.txt', $account->id, 'document');
        $media = $storage->findMedia($reference, $account->id);

        $this->get('/media/signed/'.$media->id.'?expires=1&signature=invalid')->assertForbidden();
        $this->get(\Illuminate\Support\Facades\URL::temporarySignedRoute(
            'media.signed-download', now()->subMinute(), ['media' => $media->id, 'account' => $account->id]
        ))->assertForbidden();
    }

}
