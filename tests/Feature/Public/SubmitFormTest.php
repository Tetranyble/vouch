<?php

namespace Tests\Feature\Public;

use App\Jobs\GenerateAuditTrailJob;
use App\Jobs\GenerateCombinedPdfJob;
use App\Jobs\GenerateSubmissionPdfJob;
use App\Jobs\SendSubmissionCompletedJob;
use App\Models\Account;
use App\Models\CompletedDocument;
use App\Services\DocumentStorage;
use App\Support\SubmitterDownloadLink;
use Illuminate\Support\Facades\URL;
use App\Models\Submission;
use App\Models\Submitter;
use App\Models\Template;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmitFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a complete submission + submitter in a consistent way.
     */
    private function makeSubmitter(array $submitterOverrides = []): Submitter
    {
        $account   = Account::factory()->create();
        $author    = User::factory()->create(['account_id' => $account->id]);
        $folder    = TemplateFolder::factory()->create(['account_id' => $account->id, 'author_id' => $author->id]);
        $submitterUuid = (string) \Illuminate\Support\Str::uuid();

        $template = Template::factory()->create([
            'account_id'  => $account->id,
            'author_id'   => $author->id,
            'folder_id'   => $folder->id,
            'fields'      => [],
            'schema'      => [],
            'documents'   => [],
            'submitters'  => [['name' => 'First Party', 'uuid' => $submitterUuid]],
        ]);

        $submission = Submission::factory()->create([
            'template_id'         => $template->id,
            'account_id'          => $account->id,
            'created_by_user_id'  => $author->id,
            'template_fields'     => [],
            'template_schema'     => [],
            'template_submitters' => $template->submitters,
        ]);

        return Submitter::factory()->create(array_merge([
            'submission_id' => $submission->id,
            'account_id'    => $account->id,
            'uuid'          => $submitterUuid,
            'order'         => 0,
            'values'        => [],
            'completed_at'  => null,
            'declined_at'   => null,
        ], $submitterOverrides));
    }

    // -------------------------------------------------------------------------
    // GET /s/{slug}
    // -------------------------------------------------------------------------

    #[Test]
    public function get_submit_form_with_valid_slug_renders_signing_page(): void
    {
        $submitter = $this->makeSubmitter();

        $response = $this->get("/s/{$submitter->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Public/SubmitForm/Show')
                ->has('submitter')
                ->where('submitter.slug', $submitter->slug)
                ->has('fields')
                ->has('documents')
                ->has('isCompleted')
        );
    }

    #[Test]
    public function get_submit_form_with_invalid_slug_returns_404(): void
    {
        $response = $this->get('/s/completely-invalid-slug');

        $response->assertStatus(404);
    }

    #[Test]
    public function get_submit_form_records_opened_at_on_first_view(): void
    {
        $submitter = $this->makeSubmitter(['opened_at' => null]);

        $this->get("/s/{$submitter->slug}");

        $this->assertDatabaseHas('submitters', [
            'id'        => $submitter->id,
            'opened_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    #[Test]
    public function get_submit_form_shows_completed_flag_when_submitter_is_done(): void
    {
        $submitter = $this->makeSubmitter(['completed_at' => now()]);

        $response = $this->get("/s/{$submitter->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->where('isCompleted', true)
        );
    }

    // -------------------------------------------------------------------------
    // PUT /s/{slug}
    // -------------------------------------------------------------------------

    #[Test]
    public function put_submit_form_marks_submitter_as_completed(): void
    {
        Queue::fake();

        $submitter = $this->makeSubmitter();

        $submitter->submission->update([
            'template_fields' => [['uuid' => 'field_1', 'submitter_uuid' => $submitter->uuid, 'type' => 'text']],
        ]);
        $response = $this->put("/s/{$submitter->slug}", [
            'values' => ['field_1' => 'test value'],
        ]);

        $response->assertRedirect("/s/{$submitter->slug}");

        $this->assertDatabaseHas('submitters', [
            'id'     => $submitter->id,
            'values' => json_encode(['field_1' => 'test value']),
        ]);

        $updated = $submitter->fresh();
        $this->assertNotNull($updated->completed_at);
    }

    #[Test]
    public function malformed_signing_values_do_not_complete_the_form(): void
    {
        Queue::fake();
        $submitter = $this->makeSubmitter();

        $this->put('/s/'.$submitter->slug, ['values' => 'invalid-string'])
            ->assertSessionHasErrors('values');

        $this->assertNull($submitter->fresh()->completed_at);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function put_submit_form_dispatches_generation_jobs_on_completion(): void
    {
        Bus::fake();
        $submitter = $this->makeSubmitter();
        $this->put("/s/{$submitter->slug}", ['values' => []]);
        Bus::assertChained([
            GenerateSubmissionPdfJob::class,
            GenerateAuditTrailJob::class,
            GenerateCombinedPdfJob::class,
            SendSubmissionCompletedJob::class,
        ]);
    }

    #[Test]
    public function a_signer_cannot_write_fields_assigned_to_another_role(): void
    {
        Queue::fake();
        $submitter = $this->makeSubmitter();
        $submitter->submission->update(['template_fields' => [
            ['uuid' => 'foreign', 'submitter_uuid' => (string) \Illuminate\Support\Str::uuid()],
        ]]);
        $this->put('/s/'.$submitter->slug, ['values' => ['foreign' => 'forged']])
            ->assertStatus(422);
        $this->assertNull($submitter->fresh()->completed_at);
    }

    #[Test]
    public function a_required_field_is_enforced_on_the_server(): void
    {
        Queue::fake();
        $submitter = $this->makeSubmitter();
        $submitter->submission->update(['template_fields' => [
            ['uuid' => 'signature', 'submitter_uuid' => $submitter->uuid,
                'type' => 'signature', 'required' => true],
        ]]);
        $this->put('/s/'.$submitter->slug, ['values' => []])->assertStatus(422);
        $this->assertNull($submitter->fresh()->completed_at);
    }

    #[Test]
    public function a_browser_cannot_forge_a_payment_intent_during_signing(): void
    {
        Queue::fake();
        $submitter = $this->makeSubmitter();
        $submitter->submission->update(['template_fields' => [
            ['uuid' => 'payment', 'submitter_uuid' => $submitter->uuid,
                'type' => 'payment', 'required' => true],
        ]]);
        $this->put('/s/'.$submitter->slug, ['values' => ['payment' => 'pi_forged']])
            ->assertStatus(422);
        $this->assertNull($submitter->fresh()->completed_at);
    }

    #[Test]
    public function repeated_completion_does_not_write_duplicate_audit_events(): void
    {
        Queue::fake();
        $submitter = $this->makeSubmitter();
        $this->put('/s/'.$submitter->slug, ['values' => []])->assertRedirect();
        $this->put('/s/'.$submitter->slug, ['values' => []])->assertRedirect();
        $this->assertDatabaseCount('submission_events', 1);
    }

    #[Test]
    public function put_submit_form_on_already_completed_submitter_redirects_idempotently(): void
    {
        Queue::fake();

        $alreadyCompleted = now()->subHour();
        $submitter        = $this->makeSubmitter(['completed_at' => $alreadyCompleted]);

        $response = $this->put("/s/{$submitter->slug}", ['values' => ['field' => 'new']]);

        $response->assertRedirect("/s/{$submitter->slug}");

        // Values should NOT have changed (completed_at guard)
        $this->assertDatabaseHas('submitters', [
            'id'         => $submitter->id,
            'completed_at' => $alreadyCompleted->format('Y-m-d H:i:s'),
        ]);

        // No new jobs dispatched for a no-op
        Queue::assertNothingPushed();
    }

    // -------------------------------------------------------------------------
    // GET /s/{slug}/values
    // -------------------------------------------------------------------------

    #[Test]
    public function get_values_returns_current_submitter_values_as_json(): void
    {
        $submitter = $this->makeSubmitter(['values' => ['field_a' => 'hello']]);

        $response = $this->getJson("/s/{$submitter->slug}/values");

        $response->assertStatus(200)
            ->assertJson([
                'slug'   => $submitter->slug,
                'values' => ['field_a' => 'hello'],
            ]);
    }

    #[Test]
    public function get_values_returns_empty_array_when_no_values_set(): void
    {
        $submitter = $this->makeSubmitter(['values' => []]);

        $response = $this->getJson("/s/{$submitter->slug}/values");

        $response->assertStatus(200)
            ->assertJson(['values' => []]);
    }

    #[Test]
    public function get_values_returns_404_for_unknown_slug(): void
    {
        $response = $this->getJson('/s/unknown-slug-here/values');

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // GET /s/{slug}/download
    // -------------------------------------------------------------------------

    #[Test]
    public function get_download_returns_404_when_submitter_not_completed(): void
    {
        $submitter = $this->makeSubmitter(['completed_at' => null]);

        $response = $this->get("/s/{$submitter->slug}/download");

        $response->assertStatus(404);
    }

    #[Test]
    public function get_download_returns_404_when_completed_but_no_documents(): void
    {
        $submitter = $this->makeSubmitter(['completed_at' => now()]);
        // No completed documents created — should 404

        $response = $this->get("/s/{$submitter->slug}/download");

        $response->assertStatus(404);
    }

    #[Test]
    public function signed_document_download_requires_more_than_the_signer_slug(): void
    {
        $submitter = $this->makeSubmitter(['completed_at' => now()]);
        $this->get("/s/{$submitter->slug}/download")->assertNotFound();
        $this->get("/s/{$submitter->slug}/download?expires=1&signature=invalid")->assertNotFound();
        $expired = URL::temporarySignedRoute('submit-form.download.index', now()->subMinute(), ['slug' => $submitter->slug]);
        $this->get($expired)->assertNotFound();
    }

    #[Test]
    public function valid_signed_link_returns_only_the_owning_account_completed_pdf(): void
    {
        $submitter = $this->makeSubmitter(['completed_at' => now()]);
        $storage = app(DocumentStorage::class);
        $reference = $storage->putContent('%PDF-1.4 signed test', 'signed.pdf', $submitter->account_id, 'signed-document');
        CompletedDocument::factory()->create([
            'submitter_id' => $submitter->id,
            'filename' => 'signed.pdf',
            'storage_path' => $reference,
        ]);

        $response = $this->get(SubmitterDownloadLink::for($submitter));
        $response->assertOk()->assertSee('%PDF-1.4 signed test', false);

        // Symfony can normalize Cache-Control directive ordering. Both
        // directives are mandatory; their order is not security-relevant.
        $directives = array_map('trim', explode(',', $response->headers->get('Cache-Control', '')));
        $this->assertContains('private', $directives);
        $this->assertContains('no-store', $directives);
    }

    #[Test]
    public function forged_media_reference_from_another_account_is_not_downloaded(): void
    {
        $submitter = $this->makeSubmitter(['completed_at' => now()]);
        $otherAccount = Account::factory()->create();
        $reference = app(DocumentStorage::class)->putContent('other tenant', 'secret.pdf', $otherAccount->id, 'signed-document');
        CompletedDocument::factory()->create([
            'submitter_id' => $submitter->id,
            'filename' => 'secret.pdf',
            'storage_path' => $reference,
        ]);
        $this->get(SubmitterDownloadLink::for($submitter))->assertNotFound();
    }

    #[Test]
    public function owner_can_download_without_signed_url_but_other_account_cannot(): void
    {
        $submitter = $this->makeSubmitter(['completed_at' => now()]);
        $reference = app(DocumentStorage::class)->putContent('owning tenant', 'copy.pdf', $submitter->account_id, 'signed-document');
        CompletedDocument::factory()->create([
            'submitter_id' => $submitter->id, 'filename' => 'copy.pdf', 'storage_path' => $reference,
        ]);
        $otherUser = User::factory()->create(['account_id' => Account::factory()->create()->id]);
        $this->actingAs($otherUser)->get("/s/{$submitter->slug}/download")->assertNotFound();
        $owner = User::factory()->create(['account_id' => $submitter->account_id]);
        $this->actingAs($owner)->get("/s/{$submitter->slug}/download")->assertOk();
    }

    #[Test]
    public function combined_document_is_not_repeated_with_originals(): void
    {
        $submitter = $this->makeSubmitter(['completed_at' => now()]);
        $storage = app(DocumentStorage::class);
        foreach (['first.pdf' => 'first-original', 'second.pdf' => 'second-original', 'combined.pdf' => 'combined-result'] as $name => $body) {
            $reference = $storage->putContent($body, $name, $submitter->account_id, 'signed-document');
            CompletedDocument::factory()->create([
                'submitter_id' => $submitter->id, 'filename' => $name, 'storage_path' => $reference,
            ]);
        }
        $this->get(SubmitterDownloadLink::for($submitter))
            ->assertOk()->assertSee('combined-result', false)->assertDontSee('first-original', false);
    }
}
