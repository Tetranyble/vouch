<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Submission;
use App\Models\Submitter;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmissionsTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────
    // Authorization
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_redirects_guests_to_login(): void
    {
        $this->get(route('submissions.index'))
            ->assertRedirect(route('login'));
    }

    // ──────────────────────────────────────────────────────────────
    // Index — account isolation
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_shows_only_current_accounts_submissions(): void
    {
        Queue::fake();

        $user  = User::factory()->create();
        $other = User::factory()->create();

        $mine   = Submission::factory()->create(['account_id' => $user->account_id]);
        $theirs = Submission::factory()->create(['account_id' => $other->account_id]);

        $response = $this->actingAs($user)->get(route('submissions.index'));

        $response->assertOk();
        $response->assertSee($mine->name);
        $response->assertDontSee($theirs->name);
    }

    // ──────────────────────────────────────────────────────────────
    // Show — account isolation
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_returns_404_when_accessing_another_accounts_submission(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $submission = Submission::factory()->create(['account_id' => $other->account_id]);

        $this->actingAs($user)
            ->get(route('submissions.show', $submission->id))
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Archive
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_archives_a_submission(): void
    {
        Queue::fake();

        $user       = User::factory()->create();
        $template   = Template::factory()->create(['account_id' => $user->account_id]);
        $submission = Submission::factory()->create([
            'account_id'  => $user->account_id,
            'template_id' => $template->id,
            'archived_at' => null,
        ]);

        $this->actingAs($user)
            ->delete(route('submissions.destroy', $submission->id))
            ->assertRedirect();

        $submission->refresh();
        $this->assertNotNull($submission->archived_at, 'archived_at should be set after archiving');
    }

    #[Test]
    public function it_cannot_archive_another_accounts_submission(): void
    {
        Queue::fake();

        $user  = User::factory()->create();
        $other = User::factory()->create();

        $submission = Submission::factory()->create(['account_id' => $other->account_id]);

        $this->actingAs($user)
            ->delete(route('submissions.destroy', $submission->id))
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Restore
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_restores_an_archived_submission(): void
    {
        Queue::fake();

        $user       = User::factory()->create();
        $submission = Submission::factory()->create([
            'account_id'  => $user->account_id,
            'archived_at' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->put(route('submissions.restore', $submission->id))
            ->assertRedirect();

        $this->assertDatabaseHas('submissions', ['id' => $submission->id, 'archived_at' => null]);
    }

    #[Test]
    public function it_cannot_restore_another_accounts_submission(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $submission = Submission::factory()->create([
            'account_id'  => $other->account_id,
            'archived_at' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->put(route('submissions.restore', $submission->id))
            ->assertNotFound();
    }

    // ──────────────────────────────────────────────────────────────
    // Status filter
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_filters_submissions_by_completed_status(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        // Completed submission — every submitter has completed_at set
        $completed = Submission::factory()->create([
            'account_id' => $user->account_id,
            'name'       => 'Completed Sub',
        ]);
        Submitter::factory()->create([
            'submission_id' => $completed->id,
            'account_id'    => $user->account_id,
            'completed_at'  => now(),
            'declined_at'   => null,
        ]);

        // Pending submission — submitter has no completed_at
        $pending = Submission::factory()->create([
            'account_id' => $user->account_id,
            'name'       => 'Pending Sub',
        ]);
        Submitter::factory()->create([
            'submission_id' => $pending->id,
            'account_id'    => $user->account_id,
            'completed_at'  => null,
            'declined_at'   => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('submissions.index', ['status' => 'completed']));

        $response->assertOk();
        $response->assertSee('Completed Sub');
        $response->assertDontSee('Pending Sub');
    }

    #[Test]
    public function it_filters_submissions_by_declined_status(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $declined = Submission::factory()->create([
            'account_id' => $user->account_id,
            'name'       => 'Declined Sub',
        ]);
        Submitter::factory()->create([
            'submission_id' => $declined->id,
            'account_id'    => $user->account_id,
            'declined_at'   => now(),
            'completed_at'  => null,
        ]);

        $pending = Submission::factory()->create([
            'account_id' => $user->account_id,
            'name'       => 'Pending Sub',
        ]);
        Submitter::factory()->create([
            'submission_id' => $pending->id,
            'account_id'    => $user->account_id,
            'declined_at'   => null,
            'completed_at'  => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('submissions.index', ['status' => 'declined']));

        $response->assertOk();
        $response->assertSee('Declined Sub');
        $response->assertDontSee('Pending Sub');
    }

    #[Test]
    public function it_filters_submissions_by_pending_status(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $pending = Submission::factory()->create([
            'account_id' => $user->account_id,
            'name'       => 'Pending Sub',
        ]);
        Submitter::factory()->create([
            'submission_id' => $pending->id,
            'account_id'    => $user->account_id,
            'completed_at'  => null,
            'declined_at'   => null,
        ]);

        // A completed one that should be excluded
        $completed = Submission::factory()->create([
            'account_id' => $user->account_id,
            'name'       => 'Completed Sub',
        ]);
        Submitter::factory()->create([
            'submission_id' => $completed->id,
            'account_id'    => $user->account_id,
            'completed_at'  => now(),
            'declined_at'   => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('submissions.index', ['status' => 'pending']));

        $response->assertOk();
        $response->assertSee('Pending Sub');
        $response->assertDontSee('Completed Sub');
    }

    // ──────────────────────────────────────────────────────────────
    // Search
    // ──────────────────────────────────────────────────────────────

    #[Test]
    public function it_searches_submissions_by_submitter_email(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $targetSubmission = Submission::factory()->create([
            'account_id' => $user->account_id,
            'name'       => 'Target Doc',
        ]);
        Submitter::factory()->create([
            'submission_id' => $targetSubmission->id,
            'account_id'    => $user->account_id,
            'email'         => 'findme@example.com',
        ]);

        $otherSubmission = Submission::factory()->create([
            'account_id' => $user->account_id,
            'name'       => 'Other Doc',
        ]);
        Submitter::factory()->create([
            'submission_id' => $otherSubmission->id,
            'account_id'    => $user->account_id,
            'email'         => 'nobody@example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('submissions.index', ['q' => 'findme']));

        $response->assertOk();
        $response->assertSee('Target Doc');
        $response->assertDontSee('Other Doc');
    }
}
