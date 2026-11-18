<?php

namespace Tests\Feature\Admin\Settings;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_shows_the_account_settings_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.account.index'))
            ->assertOk();
    }

    #[Test]
    public function it_redirects_guests_from_account_settings(): void
    {
        $this->get(route('settings.account.index'))
            ->assertRedirect(route('login'));
    }

    // ──────────────────────────────────────────────────────────────
    // Update account settings
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_updates_account_name_locale_and_timezone(): void
    {
        $user    = User::factory()->create();
        $account = Account::find($user->account_id);

        $this->actingAs($user)
            ->patch(route('settings.account.update'), [
                'name'     => 'Updated Corp',
                'locale'   => 'fr',
                'timezone' => 'Europe/Paris',
            ])
            ->assertRedirect(route('settings.account.index'));

        $this->assertDatabaseHas('accounts', [
            'id'       => $account->id,
            'name'     => 'Updated Corp',
            'locale'   => 'fr',
            'timezone' => 'Europe/Paris',
        ]);
    }

    #[Test]
    public function it_requires_all_fields_when_updating_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('settings.account.update'), [
                'name'     => '',
                'locale'   => '',
                'timezone' => '',
            ])
            ->assertSessionHasErrors(['name', 'locale', 'timezone']);
    }

    #[Test]
    public function it_rejects_an_invalid_timezone(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('settings.account.update'), [
                'name'     => 'Acme',
                'locale'   => 'en',
                'timezone' => 'Not/ATimezone',
            ])
            ->assertSessionHasErrors('timezone');
    }

    // ──────────────────────────────────────────────────────────────
    // Invite user
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_invites_a_new_user_and_sends_password_reset(): void
    {
        // Swap the Password broker so the sendResetLink call does not require
        // the password_reset_tokens table to exist in the test database.
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(\Illuminate\Auth\Passwords\PasswordBroker::RESET_LINK_SENT);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('settings.account.users.store'), [
                'first_name' => 'Jane',
                'last_name'  => 'Doe',
                'email'      => 'jane.doe@example.com',
                'role'       => 'admin',
            ]);

        $response->assertRedirect(route('settings.account.index'));

        $this->assertDatabaseHas('users', [
            'email'      => 'jane.doe@example.com',
            'account_id' => $user->account_id,
            'role'       => 'admin',
        ]);
    }

    #[Test]
    public function it_rejects_invite_with_duplicate_email(): void
    {
        $user      = User::factory()->create();
        $existing  = User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user)
            ->post(route('settings.account.users.store'), [
                'first_name' => 'Bob',
                'last_name'  => 'Smith',
                'email'      => 'taken@example.com',
                'role'       => 'admin',
            ])
            ->assertSessionHasErrors('email');
    }

    // ──────────────────────────────────────────────────────────────
    // Remove user
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_removes_another_user_from_the_account(): void
    {
        $user   = User::factory()->create();
        $member = User::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->delete(route('settings.account.users.destroy', $member->id))
            ->assertRedirect(route('settings.account.index'));

        // Account membership removal is a soft delete: retain the audit trail
        // without allowing the former member to appear in active-user queries.
        $this->assertSoftDeleted('users', [
            'id' => $member->id,
            'account_id' => $user->account_id,
        ]);
        $this->assertNull(User::query()->find($member->id));
        $this->assertNotNull(User::withTrashed()->find($member->id));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    #[Test]
    public function it_cannot_remove_yourself(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.account.users.destroy', $user->id))
            ->assertRedirect(route('settings.account.index'))
            ->assertSessionHas('error');

        $this->assertNotNull(User::query()->find($user->id));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    #[Test]
    public function it_cannot_remove_a_user_from_another_account(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create(); // different account

        $this->actingAs($user)
            ->delete(route('settings.account.users.destroy', $other->id))
            ->assertNotFound();

        $this->assertNotNull(User::query()->find($other->id));
        $this->assertDatabaseHas('users', ['id' => $other->id, 'deleted_at' => null]);
    }
}
