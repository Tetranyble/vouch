<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Template;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplatesTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_redirects_guests_to_login(): void
    {
        $this->get(route('templates.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function it_shows_only_current_accounts_templates(): void
    {
        Queue::fake();

        $user  = User::factory()->create();
        $other = User::factory()->create(); // different account

        $mine  = Template::factory()->create(['account_id' => $user->account_id, 'author_id' => $user->id]);
        $theirs = Template::factory()->create(['account_id' => $other->account_id, 'author_id' => $other->id]);

        $response = $this->actingAs($user)->get(route('templates.index'));

        $response->assertOk();
        $response->assertSee($mine->name);
        $response->assertDontSee($theirs->name);
    }

    // ──────────────────────────────────────────────────────────────
    // Create / Store
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_creates_a_template_and_redirects_to_builder(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('templates.store'), [
            'name' => 'My New Template',
        ]);

        $this->assertDatabaseHas('templates', [
            'name'       => 'My New Template',
            'account_id' => $user->account_id,
            'author_id'  => $user->id,
            'source'     => 'builder',
        ]);

        $template = Template::where('name', 'My New Template')->first();
        $response->assertRedirect(route('templates.builder.show', $template->id));
    }

    #[Test]
    public function it_requires_a_name_when_creating_a_template(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('templates.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    // ──────────────────────────────────────────────────────────────
    // Update
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_updates_template_name(): void
    {
        Queue::fake();

        $user     = User::factory()->create();
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'author_id'  => $user->id,
            'name'       => 'Old Name',
        ]);

        $response = $this->actingAs($user)->put(route('templates.update', $template->id), [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('templates.show', $template->id));
        $this->assertDatabaseHas('templates', ['id' => $template->id, 'name' => 'New Name']);
    }

    #[Test]
    public function it_cannot_update_another_accounts_template(): void
    {
        Queue::fake();

        $user  = User::factory()->create();
        $other = User::factory()->create();

        $template = Template::factory()->create([
            'account_id' => $other->account_id,
            'author_id'  => $other->id,
        ]);

        $this->actingAs($user)
            ->put(route('templates.update', $template->id), ['name' => 'Hacked'])
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Archive (soft delete)
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_archives_a_template(): void
    {
        Queue::fake();

        $user     = User::factory()->create();
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'author_id'  => $user->id,
            'archived_at' => null,
        ]);

        $this->actingAs($user)
            ->delete(route('templates.destroy', $template->id))
            ->assertRedirect(route('templates.index'));

        $template->refresh();
        $this->assertNotNull($template->archived_at, 'archived_at should be set after archiving');
    }

    // ──────────────────────────────────────────────────────────────
    // Restore
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_restores_an_archived_template(): void
    {
        Queue::fake();

        $user     = User::factory()->create();
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'author_id'  => $user->id,
            'archived_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->put(route('templates.restore', $template->id))
            ->assertRedirect();

        $this->assertDatabaseHas('templates', ['id' => $template->id, 'archived_at' => null]);
    }

    #[Test]
    public function it_cannot_restore_another_accounts_archived_template(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $template = Template::factory()->create([
            'account_id' => $other->account_id,
            'author_id'  => $other->id,
            'archived_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->put(route('templates.restore', $template->id))
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Clone
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_clones_a_template(): void
    {
        Queue::fake();

        $user     = User::factory()->create();
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'author_id'  => $user->id,
            'name'       => 'Original Template',
        ]);

        $response = $this->actingAs($user)
            ->post(route('templates.clone.store', $template->id));

        $clone = Template::where('name', 'Original Template (Copy)')->first();
        $this->assertNotNull($clone, 'A cloned template should exist');
        $this->assertSame($user->account_id, $clone->account_id);
        $this->assertSame('clone', $clone->source);
        $this->assertNotSame($template->id, $clone->id);
        $response->assertRedirect(route('templates.builder.show', $clone->id));
    }

    #[Test]
    public function it_cannot_clone_another_accounts_template(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $template = Template::factory()->create([
            'account_id' => $other->account_id,
            'author_id'  => $other->id,
        ]);

        $this->actingAs($user)
            ->post(route('templates.clone.store', $template->id))
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Search
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_filters_templates_by_search_query(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Template::factory()->create([
            'account_id' => $user->account_id,
            'author_id'  => $user->id,
            'name'       => 'Invoice Contract',
        ]);
        Template::factory()->create([
            'account_id' => $user->account_id,
            'author_id'  => $user->id,
            'name'       => 'NDA Agreement',
        ]);

        $response = $this->actingAs($user)
            ->get(route('templates.index', ['q' => 'Invoice']));

        $response->assertOk();
        $response->assertSee('Invoice Contract');
        $response->assertDontSee('NDA Agreement');
    }

    // ──────────────────────────────────────────────────────────────
    // Move to folder
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_moves_a_template_to_a_folder(): void
    {
        Queue::fake();

        $user     = User::factory()->create();
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'author_id'  => $user->id,
        ]);
        $folder = TemplateFolder::factory()->create([
            'account_id' => $user->account_id,
            'author_id'  => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('templates.move', $template->id), ['folder_id' => $folder->id])
            ->assertRedirect();

        $this->assertDatabaseHas('templates', ['id' => $template->id, 'folder_id' => $folder->id]);
    }

    #[Test]
    public function it_cannot_move_template_to_another_accounts_folder(): void
    {
        $user     = User::factory()->create();
        $otherUser = User::factory()->create();

        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'author_id'  => $user->id,
        ]);
        $otherFolder = TemplateFolder::factory()->create([
            'account_id' => $otherUser->account_id,
            'author_id'  => $otherUser->id,
        ]);

        $this->actingAs($user)
            ->put(route('templates.move', $template->id), ['folder_id' => $otherFolder->id])
            ->assertNotFound();
    }
}
