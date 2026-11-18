<?php

declare(strict_types=1);

namespace Tests\Feature\Storage;

use App\Actions\Templates\CloneTemplateAction;
use App\Models\Account;
use App\Models\Template;
use App\Models\User;
use App\Services\DocumentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SharedTemplateMediaRetentionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function deleting_document_from_source_template_preserves_media_used_by_a_clone(): void
    {
        Storage::fake('local');
        Queue::fake();
        $account = Account::factory()->create();
        $author = User::factory()->create(['account_id' => $account->id]);
        $storage = app(DocumentStorage::class);
        $reference = $storage->putContent('shared template bytes', 'template.txt', $account->id, 'template-document');
        $uuid = (string) \Illuminate\Support\Str::uuid();
        $template = Template::factory()->create([
            'account_id' => $account->id,
            'author_id' => $author->id,
            'documents' => [[
                'uuid' => $uuid,
                'filename' => 'template.txt',
                'content_type' => 'text/plain',
                'storage_path' => $reference,
            ]],
            'schema' => [['attachment_uuid' => $uuid, 'name' => 'Template']],
        ]);
        $clone = app(CloneTemplateAction::class)->execute($template, $author);

        $this->actingAs($author)->delete(route('templates.documents.destroy', [
            'template' => $template->id, 'uuid' => $uuid,
        ]))->assertOk();

        $this->assertTrue($storage->exists($reference, $account->id));
        $this->assertSame($reference, $clone->fresh()->documents[0]['storage_path']);
        $this->assertSame('shared template bytes', $storage->get($reference, $account->id));
        $this->assertSame([], $template->fresh()->documents);
    }
}
