<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Template;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplatesApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an account, a user, and an AccessToken. Return the raw token string
     * so tests can include it in the Authorization header.
     *
     * Sanctum stores only the hash and requires Bearer credentials.
     */
    private function createApiToken(?Account $account = null): array
    {
        $account  = $account ?? Account::factory()->create();
        $user     = User::factory()->create(['account_id' => $account->id]);
        $token    = $user->createToken('vouch-api')->plainTextToken;
        $rawToken = $token;

        return ['account' => $account, 'user' => $user, 'rawToken' => $rawToken];
    }

    private function authHeaders(string $rawToken): array
    {
        return ['Authorization' => 'Bearer ' . $rawToken];
    }

    // -------------------------------------------------------------------------
    // Auth guard
    // -------------------------------------------------------------------------

    #[Test]
    public function unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/templates');

        $response->assertStatus(401);
    }

    #[Test]
    public function request_with_invalid_token_returns_401(): void
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer invalid-token-value'])
            ->getJson('/api/templates');

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // GET /api/templates
    // -------------------------------------------------------------------------

    #[Test]
    public function index_returns_paginated_templates_for_the_account(): void
    {
        ['account' => $account, 'rawToken' => $rawToken] = $this->createApiToken();
        $author = User::factory()->create(['account_id' => $account->id]);
        $folder = TemplateFolder::factory()->create(['account_id' => $account->id, 'author_id' => $author->id]);

        Template::factory()->count(3)->create([
            'account_id' => $account->id,
            'author_id'  => $author->id,
            'folder_id'  => $folder->id,
        ]);

        // Template belonging to a different account — must NOT appear in results
        Template::factory()->create();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->getJson('/api/templates');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'pagination'])
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function index_does_not_return_archived_templates(): void
    {
        ['account' => $account, 'rawToken' => $rawToken] = $this->createApiToken();
        $author = User::factory()->create(['account_id' => $account->id]);
        $folder = TemplateFolder::factory()->create(['account_id' => $account->id, 'author_id' => $author->id]);

        Template::factory()->create([
            'account_id'  => $account->id,
            'author_id'   => $author->id,
            'folder_id'   => $folder->id,
            'archived_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->getJson('/api/templates');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // -------------------------------------------------------------------------
    // POST /api/templates
    // -------------------------------------------------------------------------

    #[Test]
    public function store_creates_a_template(): void
    {
        ['rawToken' => $rawToken, 'account' => $account] = $this->createApiToken();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->postJson('/api/templates', ['name' => 'My New Template']);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'My New Template']);

        $this->assertDatabaseHas('templates', [
            'name'       => 'My New Template',
            'account_id' => $account->id,
            'source'     => 'api',
        ]);
    }

    #[Test]
    public function store_requires_name_field(): void
    {
        ['rawToken' => $rawToken] = $this->createApiToken();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->postJson('/api/templates', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // -------------------------------------------------------------------------
    // GET /api/templates/{id}
    // -------------------------------------------------------------------------

    #[Test]
    public function show_returns_template_data_for_own_account(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();
        $folder   = TemplateFolder::factory()->create(['account_id' => $account->id, 'author_id' => $user->id]);
        $template = Template::factory()->create([
            'account_id' => $account->id,
            'author_id'  => $user->id,
            'folder_id'  => $folder->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->getJson("/api/templates/{$template->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $template->id, 'name' => $template->name]);
    }

    #[Test]
    public function show_returns_404_for_another_accounts_template(): void
    {
        ['rawToken' => $rawToken] = $this->createApiToken();

        // Template owned by a different account
        $otherTemplate = Template::factory()->create();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->getJson("/api/templates/{$otherTemplate->id}");

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PUT /api/templates/{id}
    // -------------------------------------------------------------------------

    #[Test]
    public function update_changes_template_name(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();
        $folder   = TemplateFolder::factory()->create(['account_id' => $account->id, 'author_id' => $user->id]);
        $template = Template::factory()->create([
            'account_id' => $account->id,
            'author_id'  => $user->id,
            'folder_id'  => $folder->id,
            'name'       => 'Old Name',
        ]);

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->putJson("/api/templates/{$template->id}", ['name' => 'Updated Name']);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('templates', [
            'id'   => $template->id,
            'name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function update_returns_404_for_another_accounts_template(): void
    {
        ['rawToken' => $rawToken] = $this->createApiToken();
        $otherTemplate = Template::factory()->create();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->putJson("/api/templates/{$otherTemplate->id}", ['name' => 'Hacked']);

        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/templates/{id}
    // -------------------------------------------------------------------------

    #[Test]
    public function destroy_archives_the_template(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();
        $folder   = TemplateFolder::factory()->create(['account_id' => $account->id, 'author_id' => $user->id]);
        $template = Template::factory()->create([
            'account_id' => $account->id,
            'author_id'  => $user->id,
            'folder_id'  => $folder->id,
        ]);

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->deleteJson("/api/templates/{$template->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $template->id]);

        // Template should be soft-archived, not hard-deleted
        $this->assertNotNull($template->fresh()->archived_at);
        $this->assertDatabaseHas('templates', ['id' => $template->id]);
    }

    #[Test]
    public function destroy_hard_deletes_with_permanently_flag(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();
        $folder   = TemplateFolder::factory()->create(['account_id' => $account->id, 'author_id' => $user->id]);
        $template = Template::factory()->create([
            'account_id' => $account->id,
            'author_id'  => $user->id,
            'folder_id'  => $folder->id,
        ]);

        $this->withHeaders($this->authHeaders($rawToken))
            ->deleteJson("/api/templates/{$template->id}?permanently=true");

        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
    }

    #[Test]
    public function destroy_returns_404_for_another_accounts_template(): void
    {
        ['rawToken' => $rawToken] = $this->createApiToken();
        $otherTemplate = Template::factory()->create();

        $response = $this->withHeaders($this->authHeaders($rawToken))
            ->deleteJson("/api/templates/{$otherTemplate->id}");

        $response->assertStatus(404);
    }
    #[Test]
    public function update_rejects_schema_fields_with_the_wrong_data_type(): void
    {
        ['account' => $account, 'rawToken' => $rawToken, 'user' => $user] = $this->createApiToken();
        $template = Template::factory()->create(['account_id' => $account->id, 'author_id' => $user->id]);

        $this->withHeaders($this->authHeaders($rawToken))
            ->putJson("/api/templates/{$template->id}", ['fields' => 'invalid-scalar'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fields']);
    }

}
