<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiTokenSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function bearer_token_authenticates_a_user(): void
    {
        $user = User::factory()->create();
        $raw = $user->createToken('vouch-api')->plainTextToken;

        $this->withToken($raw)->getJson('/api/users/me')->assertOk();
    }

    #[Test]
    public function legacy_token_headers_are_rejected(): void
    {
        $user = User::factory()->create();
        $raw = $user->createToken('vouch-api')->plainTextToken;
        $this->withHeader('Authorization', 'Token '.$raw)->getJson('/api/users/me')->assertUnauthorized();
        $this->withHeaders(['Authorization' => '', 'X-Auth-Token' => $raw])->getJson('/api/users/me')->assertUnauthorized();
    }

    #[Test]
    public function query_string_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        $raw = $user->createToken('vouch-api')->plainTextToken;
        $this->getJson('/api/users/me?token='.urlencode($raw))->assertUnauthorized();
    }

    #[Test]
    public function revoked_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        $raw = $user->createToken('vouch-api')->plainTextToken;
        $user->tokens()->delete();
        $this->withToken($raw)->getJson('/api/users/me')->assertUnauthorized();
    }

    #[Test]
    public function archived_users_cannot_use_previous_api_tokens(): void
    {
        $user = User::factory()->create();
        $raw = $user->createToken('vouch-api')->plainTextToken;
        $user->delete();

        $this->withToken($raw)->getJson('/api/users/me')->assertUnauthorized();
    }
}
