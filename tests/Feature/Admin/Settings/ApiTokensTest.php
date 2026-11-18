<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiTokensTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_cannot_manage_api_tokens(): void
    {
        $this->get(route('settings.api-tokens.index'))->assertRedirect(route('login'));
        $this->post(route('settings.api-tokens.store'))->assertRedirect(route('login'));
    }

    #[Test]
    public function it_issues_and_displays_a_token_only_once(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('settings.api-tokens.store'));
        $response->assertRedirect(route('settings.api-tokens.index'));

        $token = $user->tokens()->firstOrFail();
        $this->assertSame(64, strlen($token->token));
        $this->assertSame('vouch-api', $token->name);

        $response = $this->actingAs($user)->get(route('settings.api-tokens.index'));
        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/ApiTokens')
            ->where('token.id', $token->id)
            ->where('token.masked', '••••••••••••••••••••••••••••••••')
            ->has('newToken'));

        // Session flash secret must be consumed after the first GET.
        $this->actingAs($user)->get(route('settings.api-tokens.index'))
            ->assertInertia(fn ($page) => $page->where('newToken', null));
    }

    #[Test]
    public function it_replaces_prior_tokens_on_regeneration(): void
    {
        $user = User::factory()->create();
        $original = $user->createToken('vouch-api')->accessToken;
        $this->actingAs($user)->post(route('settings.api-tokens.store'));
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $original->id]);
        $this->assertSame(1, $user->tokens()->count());
    }

    #[Test]
    public function it_revokes_only_the_owners_tokens(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->createToken('vouch-api');
        $otherToken = $other->createToken('vouch-api')->accessToken;

        $this->actingAs($user)->delete(route('settings.api-tokens.destroy'))
            ->assertRedirect(route('settings.api-tokens.index'));

        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->id]);
    }

    #[Test]
    public function plaintext_tokens_cannot_be_retrieved(): void
    {
        $user = User::factory()->create();
        $user->createToken('vouch-api');
        $this->actingAs($user)->postJson(route('settings.api-tokens.reveal'), ['password' => 'any'])
            ->assertStatus(410);
    }
}
