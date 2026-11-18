<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function googleIdentity(string $email, bool $verified, string $providerId = 'google-123'): GoogleUser
    {
        return (new GoogleUser)->setRaw([
            'sub' => $providerId,
            'email' => $email,
            'email_verified' => $verified,
            'name' => 'Jane Doe',
        ])->map([
            'id' => $providerId,
            'email' => $email,
            'name' => 'Jane Doe',
        ]);
    }

    #[Test]
    public function unverified_google_identity_cannot_take_over_local_user(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com', 'google_id' => null]);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn(
            Mockery::mock()->shouldReceive('user')->once()->andReturn(
                $this->googleIdentity($user->email, false)
            )->getMock()
        );
        $this->get('/auth/google/callback')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
    }

    #[Test]
    public function verified_google_identity_can_link_and_sign_in(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'jane@example.com', 'google_id' => null]);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn(
            Mockery::mock()->shouldReceive('user')->once()->andReturn(
                $this->googleIdentity($user->email, true)
            )->getMock()
        );
        $this->get('/auth/google/callback')->assertRedirect(route('templates.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->fresh()->google_id);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
