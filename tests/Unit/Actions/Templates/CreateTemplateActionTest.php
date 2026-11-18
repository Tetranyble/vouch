<?php

namespace Tests\Unit\Actions\Templates;

use App\Actions\Templates\CreateTemplateAction;
use App\Jobs\DispatchWebhookEventJob;
use App\Jobs\ReindexSearchEntryJob;
use App\Models\Template;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateTemplateActionTest extends TestCase
{
    use RefreshDatabase;

    private CreateTemplateAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->action = new CreateTemplateAction;
    }

    public function test_creates_template_with_given_name(): void
    {
        $user = User::factory()->create();

        $template = $this->action->execute($user, 'My Contract');

        $this->assertDatabaseHas('templates', [
            'name' => 'My Contract',
            'author_id' => $user->id,
            'account_id' => $user->account_id,
            'source' => 'builder',
        ]);
        $this->assertInstanceOf(Template::class, $template);
        $this->assertEquals('My Contract', $template->name);
    }

    public function test_creates_default_folder_when_none_provided(): void
    {
        $user = User::factory()->create();

        $this->action->execute($user, 'NDA');

        $this->assertDatabaseHas('template_folders', [
            'name' => 'Default',
            'account_id' => $user->account_id,
        ]);
    }

    public function test_reuses_existing_default_folder(): void
    {
        $user = User::factory()->create();
        $folder = TemplateFolder::factory()->create(['name' => 'Default', 'account_id' => $user->account_id]);

        $this->action->execute($user, 'Doc 1');
        $this->action->execute($user, 'Doc 2');

        $this->assertEquals(1, TemplateFolder::where('account_id', $user->account_id)->where('name', 'Default')->count());
    }

    public function test_uses_provided_folder_id(): void
    {
        $user = User::factory()->create();
        $folder = TemplateFolder::factory()->create(['account_id' => $user->account_id]);

        $template = $this->action->execute($user, 'Invoice', folderId: $folder->id);

        $this->assertEquals($folder->id, $template->folder_id);
    }

    public function test_rejects_a_folder_from_a_different_account(): void
    {
        $user = User::factory()->create();
        $otherFolder = TemplateFolder::factory()->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->action->execute($user, 'Blocked Contract', folderId: $otherFolder->id);
    }

    public function test_seeds_template_with_one_submitter(): void
    {
        $user = User::factory()->create();

        $template = $this->action->execute($user, 'Agreement');

        $this->assertCount(1, $template->submitters);
        $this->assertEquals('First Party', $template->submitters[0]['name']);
        $this->assertArrayHasKey('uuid', $template->submitters[0]);
    }

    public function test_dispatches_webhook_and_reindex_jobs(): void
    {
        $user = User::factory()->create();

        $template = $this->action->execute($user, 'Contract');

        Queue::assertPushed(DispatchWebhookEventJob::class, fn ($job) => $job->eventType === 'template.created' && $job->accountId === $user->account_id
        );
        Queue::assertPushed(ReindexSearchEntryJob::class, fn ($job) => $job->recordType === 'Template' && $job->recordId === $template->id
        );
    }

    public function test_custom_source_is_stored(): void
    {
        $user = User::factory()->create();

        $template = $this->action->execute($user, 'Template', source: 'upload');

        $this->assertEquals('upload', $template->source);
    }
}
