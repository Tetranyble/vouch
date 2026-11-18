<?php

namespace Tests\Unit\Actions\Templates;

use App\Actions\Templates\CloneTemplateAction;
use App\Jobs\ReindexSearchEntryJob;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CloneTemplateActionTest extends TestCase
{
    use RefreshDatabase;

    private CloneTemplateAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->action = new CloneTemplateAction();
    }

    public function test_creates_clone_with_copy_suffix(): void
    {
        $source = Template::factory()->create(['name' => 'Original Contract']);
        $user   = User::factory()->create(['account_id' => $source->account_id]);

        $clone = $this->action->execute($source, $user);

        $this->assertEquals('Original Contract (Copy)', $clone->name);
    }

    public function test_clone_inherits_source_schema_and_fields(): void
    {
        $fields  = [['id' => 'f1', 'type' => 'text']];
        $schema  = [['attachment_uuid' => 'abc']];
        $source  = Template::factory()->create(['fields' => $fields, 'schema' => $schema]);
        $user    = User::factory()->create(['account_id' => $source->account_id]);

        $clone = $this->action->execute($source, $user);

        $this->assertEquals($fields, $clone->fields);
        $this->assertEquals($schema, $clone->schema);
    }

    public function test_clone_inherits_submitters(): void
    {
        $submitters = [['name' => 'Signer', 'uuid' => 'abc123']];
        $source     = Template::factory()->create(['submitters' => $submitters]);
        $user       = User::factory()->create(['account_id' => $source->account_id]);

        $clone = $this->action->execute($source, $user);

        $this->assertEquals($submitters, $clone->submitters);
    }

    public function test_clone_belongs_to_cloning_user_and_account(): void
    {
        $source = Template::factory()->create();
        $user   = User::factory()->create(['account_id' => $source->account_id]);

        $clone = $this->action->execute($source, $user);

        $this->assertEquals($user->id, $clone->author_id);
        $this->assertEquals($user->account_id, $clone->account_id);
    }

    public function test_clone_source_is_set_to_clone(): void
    {
        $source = Template::factory()->create();
        $user   = User::factory()->create(['account_id' => $source->account_id]);

        $clone = $this->action->execute($source, $user);

        $this->assertEquals('clone', $clone->source);
    }

    public function test_clone_stays_in_same_folder(): void
    {
        $source = Template::factory()->create();
        $user   = User::factory()->create(['account_id' => $source->account_id]);

        $clone = $this->action->execute($source, $user);

        $this->assertEquals($source->folder_id, $clone->folder_id);
    }

    public function test_clone_gets_unique_slug(): void
    {
        $source = Template::factory()->create();
        $user   = User::factory()->create(['account_id' => $source->account_id]);

        $clone = $this->action->execute($source, $user);

        $this->assertNotEquals($source->slug, $clone->slug);
    }

    public function test_dispatches_reindex_job_for_clone(): void
    {
        $source = Template::factory()->create();
        $user   = User::factory()->create(['account_id' => $source->account_id]);

        $clone = $this->action->execute($source, $user);

        Queue::assertPushed(ReindexSearchEntryJob::class, fn ($job) =>
            $job->recordType === 'Template' && $job->recordId === $clone->id
        );
    }
    public function test_rejects_cloning_a_template_from_another_account(): void
    {
        $source = Template::factory()->create();
        $user = User::factory()->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->action->execute($source, $user);
    }

}
