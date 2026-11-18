<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Submission;
use App\Models\Submitter;
use App\Models\Template;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmittersApiTest extends TestCase
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

    private function makeSubmitterForAccount(Account $account, User $user, array $overrides = []): Submitter
    {
        $folder = TemplateFolder::factory()->create([
            'account_id' => $account->id,
            'author_id' => $user->id,
        ]);
        $template = Template::factory()->create([
            'account_id' => $account->id,
            'author_id' => $user->id,
            'folder_id' => $folder->id,
        ]);
        $submission = Submission::factory()->create([
            'template_id' => $template->id,
            'account_id' => $account->id,
            'created_by_user_id' => $user->id,
        ]);

        return Submitter::factory()->create(array_merge([
            'submission_id' => $submission->id,
            'account_id' => $account->id,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // GET /api/submitters
    // -------------------------------------------------------------------------

    #[Test]
    public function index_returns_submitters_for_account(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();

        $this->makeSubmitterForAccount($account, $user);
        $this->makeSubmitterForAccount($account, $user);

        // Submitter belonging to another account — must not appear
        Submitter::factory()->create();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->getJson('/api/submitters');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'pagination'])
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function index_filters_by_submission_id(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();

        $folder = TemplateFolder::factory()->create(['account_id' => $account->id, 'author_id' => $user->id]);
        $template = Template::factory()->create([
            'account_id' => $account->id,
            'author_id' => $user->id,
            'folder_id' => $folder->id,
        ]);

        $submissionA = Submission::factory()->create([
            'template_id' => $template->id,
            'account_id' => $account->id,
            'created_by_user_id' => $user->id,
        ]);
        $submissionB = Submission::factory()->create([
            'template_id' => $template->id,
            'account_id' => $account->id,
            'created_by_user_id' => $user->id,
        ]);

        Submitter::factory()->create(['submission_id' => $submissionA->id, 'account_id' => $account->id]);
        Submitter::factory()->create(['submission_id' => $submissionB->id, 'account_id' => $account->id]);
        Submitter::factory()->create(['submission_id' => $submissionB->id, 'account_id' => $account->id]);

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->getJson("/api/submitters?submission_id={$submissionB->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function unauthenticated_index_returns_401(): void
    {
        $response = $this->getJson('/api/submitters');

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // GET /api/submitters/{id}
    // -------------------------------------------------------------------------

    #[Test]
    public function show_returns_submitter_with_values(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();

        $submitter = $this->makeSubmitterForAccount($account, $user, [
            'values' => ['field_x' => 'sample'],
        ]);

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->getJson("/api/submitters/{$submitter->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $submitter->id])
            ->assertJsonStructure(['values']);
    }

    #[Test]
    public function show_returns_404_for_submitter_from_another_account(): void
    {
        ['rawToken' => $rawToken] = $this->createApiToken();

        $otherSubmitter = Submitter::factory()->create();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->getJson("/api/submitters/{$otherSubmitter->id}");

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PUT /api/submitters/{id}
    // -------------------------------------------------------------------------

    #[Test]
    public function update_changes_submitter_name_and_email(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();

        $submitter = $this->makeSubmitterForAccount($account, $user, [
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->putJson("/api/submitters/{$submitter->id}", [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'New Name', 'email' => 'new@example.com']);

        $this->assertDatabaseHas('submitters', [
            'id' => $submitter->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    #[Test]
    public function update_returns_404_for_submitter_from_another_account(): void
    {
        ['rawToken' => $rawToken] = $this->createApiToken();

        $otherSubmitter = Submitter::factory()->create();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->putJson("/api/submitters/{$otherSubmitter->id}", ['name' => 'Hacked']);

        $response->assertStatus(404);
    }

    #[Test]
    public function update_validates_email_format(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();

        $submitter = $this->makeSubmitterForAccount($account, $user);

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->putJson("/api/submitters/{$submitter->id}", [
                'email' => 'not-a-valid-email',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // -------------------------------------------------------------------------
    // GET /api/users/me
    // -------------------------------------------------------------------------

    #[Test]
    public function me_returns_authenticated_user_info(): void
    {
        ['user' => $user, 'rawToken' => $rawToken, 'account' => $account] = $this->createApiToken();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->getJson('/api/users/me');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $user->id,
                'email' => $user->email,
            ])
            ->assertJsonStructure(['id', 'email', 'first_name', 'last_name', 'name', 'account']);
    }

    #[Test]
    public function me_includes_account_information(): void
    {
        ['rawToken' => $rawToken, 'account' => $account] = $this->createApiToken();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->getJson('/api/users/me');

        $response->assertStatus(200)
            ->assertJsonPath('account.id', $account->id)
            ->assertJsonPath('account.name', $account->name);
    }

    #[Test]
    public function me_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/users/me');

        $response->assertStatus(401);
    }

    #[Test]
    public function bearer_token_format_also_authenticates(): void
    {
        ['user' => $user, 'rawToken' => $rawToken] = $this->createApiToken();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$rawToken])
            ->getJson('/api/users/me');

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $user->id]);
    }

    #[Test]
    public function completed_signer_values_are_immutable_through_the_api(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();
        $submitter = $this->makeSubmitterForAccount($account, $user, [
            'completed_at' => now(),
            'values' => ['signed_field' => 'original'],
        ]);

        $this->withHeaders($this->authHeaders($rawToken))
            ->putJson("/api/submitters/{$submitter->id}", ['values' => ['signed_field' => 'tampered']])
            ->assertStatus(422);

        $this->assertSame('original', $submitter->fresh()->values['signed_field']);
    }
}
