<?php

namespace Tests\Feature\Admin\Settings;

use App\Models\User;
use App\Models\WebhookUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhooksTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_redirects_guests_from_webhooks_page(): void
    {
        $this->get(route('settings.webhooks.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function it_shows_the_webhooks_settings_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.webhooks.index'))
            ->assertOk();
    }

    // ──────────────────────────────────────────────────────────────
    // Create
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_creates_a_webhook_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.webhooks.store'), [
                'url'    => 'https://example.com/webhook',
                'events' => ['form.completed', 'submission.created'],
            ])
            ->assertRedirect(route('settings.webhooks.index'));

        $this->assertDatabaseHas('webhook_urls', [
            'account_id' => $user->account_id,
            'url'        => 'https://example.com/webhook',
        ]);

        $webhook = WebhookUrl::where('account_id', $user->account_id)->first();
        $this->assertContains('form.completed', $webhook->events);
        $this->assertContains('submission.created', $webhook->events);
        $this->assertNotNull($webhook->secret, 'A secret should be auto-generated on creation');
    }

    #[Test]
    public function it_rejects_an_invalid_url_when_creating_webhook(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.webhooks.store'), [
                'url' => 'not-a-url',
            ])
            ->assertSessionHasErrors('url');
    }

    #[Test]
    public function it_rejects_unknown_event_types(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.webhooks.store'), [
                'url'    => 'https://example.com/hook',
                'events' => ['bogus.event'],
            ])
            ->assertSessionHasErrors('events.0');
    }

    // ──────────────────────────────────────────────────────────────
    // Update
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_updates_a_webhook_url_and_events(): void
    {
        $user    = User::factory()->create();
        $webhook = WebhookUrl::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->patch(route('settings.webhooks.update', $webhook->id), [
                'url'    => 'https://new.example.com/hook',
                'events' => ['template.created'],
            ])
            ->assertRedirect(route('settings.webhooks.index'));

        $webhook->refresh();
        $this->assertSame('https://new.example.com/hook', $webhook->url);
        $this->assertContains('template.created', $webhook->events);
    }

    #[Test]
    public function it_cannot_update_another_accounts_webhook(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $webhook = WebhookUrl::factory()->create(['account_id' => $other->account_id]);

        $this->actingAs($user)
            ->patch(route('settings.webhooks.update', $webhook->id), [
                'url'    => 'https://evil.com/steal',
                'events' => [],
            ])
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Delete
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_deletes_a_webhook_url(): void
    {
        $user    = User::factory()->create();
        $webhook = WebhookUrl::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->delete(route('settings.webhooks.destroy', $webhook->id))
            ->assertRedirect(route('settings.webhooks.index'));

        $this->assertDatabaseMissing('webhook_urls', ['id' => $webhook->id]);
    }

    #[Test]
    public function it_cannot_delete_another_accounts_webhook(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $webhook = WebhookUrl::factory()->create(['account_id' => $other->account_id]);

        $this->actingAs($user)
            ->delete(route('settings.webhooks.destroy', $webhook->id))
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Webhook secret — generate
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_generates_a_new_webhook_secret(): void
    {
        $user    = User::factory()->create();
        $webhook = WebhookUrl::factory()->create([
            'account_id' => $user->account_id,
            'secret'     => 'old-secret',
        ]);

        $this->actingAs($user)
            ->post(route('settings.webhooks.secret.store', $webhook->id))
            ->assertRedirect();

        $webhook->refresh();
        $this->assertNotNull($webhook->secret);
        $this->assertNotSame('old-secret', $webhook->secret, 'Secret should be regenerated');
    }

    #[Test]
    public function it_cannot_generate_secret_for_another_accounts_webhook(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $webhook = WebhookUrl::factory()->create(['account_id' => $other->account_id]);

        $this->actingAs($user)
            ->post(route('settings.webhooks.secret.store', $webhook->id))
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Webhook secret — delete
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_deletes_the_webhook_secret(): void
    {
        $user    = User::factory()->create();
        $webhook = WebhookUrl::factory()->create([
            'account_id' => $user->account_id,
            'secret'     => 'existing-secret',
        ]);

        $this->actingAs($user)
            ->delete(route('settings.webhooks.secret.destroy', $webhook->id))
            ->assertRedirect();

        $this->assertDatabaseHas('webhook_urls', ['id' => $webhook->id, 'secret' => null]);
    }

    #[Test]
    public function it_cannot_delete_secret_for_another_accounts_webhook(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $webhook = WebhookUrl::factory()->create(['account_id' => $other->account_id]);

        $this->actingAs($user)
            ->delete(route('settings.webhooks.secret.destroy', $webhook->id))
            ->assertNotFound();
    }
}
