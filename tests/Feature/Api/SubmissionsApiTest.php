<?php

namespace Tests\Feature\Api;

use App\Jobs\SendSubmitterInvitationJob;
use App\Models\Account;
use App\Models\Submission;
use App\Models\Submitter;
use App\Models\Template;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmissionsApiTest extends TestCase
{
    use RefreshDatabase;

    private function createApiToken(?Account $account = null): array
    {
        $account = $account ?? Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        $token = $user->createToken('vouch-api')->plainTextToken;

        return ['account' => $account, 'user' => $user, 'rawToken' => $token];
    }

    private function authHeaders(string $rawToken): array
    {
        return ['Authorization' => 'Bearer '.$rawToken];
    }

    private function makeTemplate(Account $account, User $user): Template
    {
        $folder = TemplateFolder::factory()->create([
            'account_id' => $account->id,
            'author_id' => $user->id,
        ]);
        $submitterUuid = (string) \Illuminate\Support\Str::uuid();

        return Template::factory()->create([
            'account_id' => $account->id,
            'author_id' => $user->id,
            'folder_id' => $folder->id,
            'submitters' => [['name' => 'First Party', 'uuid' => $submitterUuid]],
            'fields' => [],
            'schema' => [],
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/submissions
    // -------------------------------------------------------------------------

    #[Test]
    public function index_returns_submissions_for_account(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();
        $template = $this->makeTemplate($account, $user);

        Submission::factory()->count(2)->create([
            'template_id' => $template->id,
            'account_id' => $account->id,
            'created_by_user_id' => $user->id,
        ]);

        // Submission from another account — must not appear
        Submission::factory()->create();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->getJson('/api/submissions');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'pagination'])
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function index_does_not_return_archived_submissions(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();
        $template = $this->makeTemplate($account, $user);

        Submission::factory()->create([
            'template_id' => $template->id,
            'account_id' => $account->id,
            'created_by_user_id' => $user->id,
            'archived_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->getJson('/api/submissions');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function unauthenticated_index_returns_401(): void
    {
        $response = $this->getJson('/api/submissions');

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // POST /api/submissions
    // -------------------------------------------------------------------------

    #[Test]
    public function store_creates_submission_with_submitters(): void
    {
        Queue::fake();

        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();
        $template = $this->makeTemplate($account, $user);

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->postJson('/api/submissions', [
                'template_id' => $template->id,
                'submitters' => [
                    ['email' => 'signer@example.com', 'name' => 'Signer One'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'submitters']);

        $this->assertDatabaseHas('submissions', [
            'template_id' => $template->id,
            'account_id' => $account->id,
            'source' => 'api',
        ]);

        $this->assertDatabaseHas('submitters', [
            'email' => 'signer@example.com',
            'account_id' => $account->id,
        ]);
    }

    #[Test]
    public function store_dispatches_invitation_job_for_first_submitter(): void
    {
        Queue::fake();

        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();
        $template = $this->makeTemplate($account, $user);

        $this->withHeaders($this->authHeaders($rawToken))
            ->postJson('/api/submissions', [
                'template_id' => $template->id,
                'submitters' => [
                    ['email' => 'signer@example.com', 'name' => 'Signer One'],
                ],
            ]);

        Queue::assertPushed(SendSubmitterInvitationJob::class);
    }

    #[Test]
    public function store_requires_template_id_and_submitters(): void
    {
        ['rawToken' => $rawToken] = $this->createApiToken();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->postJson('/api/submissions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['template_id', 'submitters']);
    }

    #[Test]
    public function store_returns_404_for_template_from_another_account(): void
    {
        Queue::fake();

        ['rawToken' => $rawToken] = $this->createApiToken();

        // Template belonging to a completely different account
        $otherTemplate = Template::factory()->create();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->postJson('/api/submissions', [
                'template_id' => $otherTemplate->id,
                'submitters' => [['email' => 'attacker@example.com']],
            ]);

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // GET /api/submissions/{id}/documents
    // -------------------------------------------------------------------------

    /** Account-isolated document access must succeed for the token owner. */
    #[Test]
    public function documents_endpoint_returns_documents_for_authenticated_account(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();
        $template = $this->makeTemplate($account, $user);
        $submission = Submission::factory()->create([
            'template_id' => $template->id,
            'account_id' => $account->id,
            'created_by_user_id' => $user->id,
        ]);

        Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $account->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->getJson("/api/submissions/{$submission->id}/documents");

        $response->assertOk()->assertJsonPath('id', $submission->id);
    }

    #[Test]
    public function documents_endpoint_rejects_cross_account_submission(): void
    {
        ['rawToken' => $rawToken] = $this->createApiToken();
        $otherSubmission = Submission::factory()->create();
        $this->withHeaders($this->authHeaders($rawToken))
            ->getJson("/api/submissions/{$otherSubmission->id}/documents")
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // DELETE /api/submissions/{id}
    // -------------------------------------------------------------------------

    #[Test]
    public function destroy_archives_submission(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();
        $template = $this->makeTemplate($account, $user);
        $submission = Submission::factory()->create([
            'template_id' => $template->id,
            'account_id' => $account->id,
            'created_by_user_id' => $user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->deleteJson("/api/submissions/{$submission->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $submission->id]);

        $this->assertNotNull($submission->fresh()->archived_at);
        $this->assertDatabaseHas('submissions', ['id' => $submission->id]);
    }

    #[Test]
    public function destroy_hard_deletes_with_permanently_flag(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();
        $template = $this->makeTemplate($account, $user);
        $submission = Submission::factory()->create([
            'template_id' => $template->id,
            'account_id' => $account->id,
            'created_by_user_id' => $user->id,
        ]);

        $this->withHeaders($this->authHeaders($rawToken))
            ->deleteJson("/api/submissions/{$submission->id}?permanently=true");

        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
    }

    #[Test]
    public function destroy_returns_404_for_another_accounts_submission(): void
    {
        ['rawToken' => $rawToken] = $this->createApiToken();
        $otherSubmission = Submission::factory()->create();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->deleteJson("/api/submissions/{$otherSubmission->id}");

        $response->assertStatus(404);
    }
}
