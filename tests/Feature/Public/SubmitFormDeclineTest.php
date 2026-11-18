<?php

namespace Tests\Feature\Public;

use App\Models\Account;
use App\Models\Submission;
use App\Models\Submitter;
use App\Models\Template;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmitFormDeclineTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubmitter(array $overrides = []): Submitter
    {
        $account = Account::factory()->create();
        $author = User::factory()->create(['account_id' => $account->id]);
        $folder = TemplateFolder::factory()->create(['account_id' => $account->id, 'author_id' => $author->id]);
        $submitterUuid = (string) \Illuminate\Support\Str::uuid();

        $template = Template::factory()->create([
            'account_id' => $account->id,
            'author_id' => $author->id,
            'folder_id' => $folder->id,
            'submitters' => [['name' => 'First Party', 'uuid' => $submitterUuid]],
        ]);

        $submission = Submission::factory()->create([
            'template_id' => $template->id,
            'account_id' => $account->id,
            'created_by_user_id' => $author->id,
            'template_submitters' => $template->submitters,
        ]);

        return Submitter::factory()->create(array_merge([
            'submission_id' => $submission->id,
            'account_id' => $account->id,
            'uuid' => $submitterUuid,
            'order' => 0,
            'completed_at' => null,
            'declined_at' => null,
            'metadata' => [],
        ], $overrides));
    }

    #[Test]
    public function post_decline_sets_declined_at_on_submitter(): void
    {
        $submitter = $this->makeSubmitter();

        $response = $this->postJson("/s/{$submitter->slug}/decline");

        $response->assertStatus(200)
            ->assertJson(['slug' => $submitter->slug, 'status' => 'declined']);

        $this->assertNotNull($submitter->fresh()->declined_at);
    }

    #[Test]
    public function post_decline_without_reason_stores_empty_string_in_metadata(): void
    {
        $submitter = $this->makeSubmitter();

        $this->postJson("/s/{$submitter->slug}/decline");

        $updated = $submitter->fresh();
        $this->assertNotNull($updated->declined_at);
        $this->assertEquals('', $updated->metadata['decline_reason'] ?? null);
    }

    #[Test]
    public function post_decline_with_reason_stores_reason_in_metadata(): void
    {
        $submitter = $this->makeSubmitter();

        $this->postJson("/s/{$submitter->slug}/decline", [
            'reason' => 'I do not agree with the terms.',
        ]);

        $updated = $submitter->fresh();
        $this->assertNotNull($updated->declined_at);
        $this->assertEquals('I do not agree with the terms.', $updated->metadata['decline_reason']);
    }

    #[Test]
    public function post_decline_creates_a_form_declined_submission_event(): void
    {
        $submitter = $this->makeSubmitter();

        $this->postJson("/s/{$submitter->slug}/decline", ['reason' => 'Not interested']);

        $this->assertDatabaseHas('submission_events', [
            'submitter_id' => $submitter->id,
            'event_type' => 'form.declined',
        ]);
    }

    #[Test]
    public function post_decline_returns_422_when_submitter_already_completed(): void
    {
        $submitter = $this->makeSubmitter(['completed_at' => now()]);

        $response = $this->postJson("/s/{$submitter->slug}/decline");

        $response->assertStatus(422)
            ->assertJson(['error' => 'Already processed']);

        // declined_at must remain null
        $this->assertNull($submitter->fresh()->declined_at);
    }

    #[Test]
    public function post_decline_returns_422_when_submitter_already_declined(): void
    {
        $alreadyDeclinedAt = now()->subHour();
        $submitter = $this->makeSubmitter(['declined_at' => $alreadyDeclinedAt]);

        $response = $this->postJson("/s/{$submitter->slug}/decline");

        $response->assertStatus(422)
            ->assertJson(['error' => 'Already processed']);

        // declined_at should not have changed
        $this->assertEquals(
            $alreadyDeclinedAt->format('Y-m-d H:i:s'),
            $submitter->fresh()->declined_at->format('Y-m-d H:i:s'),
        );
    }

    #[Test]
    public function post_decline_returns_404_for_unknown_slug(): void
    {
        $response = $this->postJson('/s/nonexistent-slug/decline');

        $response->assertStatus(404);
    }
}
