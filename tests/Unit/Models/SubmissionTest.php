<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\SearchEntry;
use App\Models\Submission;
use App\Models\Submitter;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // scopeActive
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_only_non_archived_submissions_with_active_scope(): void
    {
        $active   = Submission::factory()->create(['archived_at' => null]);
        $archived = Submission::factory()->create(['archived_at' => now()]);

        $results = Submission::active()->pluck('id');

        $this->assertContains($active->id, $results);
        $this->assertNotContains($archived->id, $results);
    }

    // -------------------------------------------------------------------------
    // scopeArchived
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_only_archived_submissions_with_archived_scope(): void
    {
        $active   = Submission::factory()->create(['archived_at' => null]);
        $archived = Submission::factory()->create(['archived_at' => now()]);

        $results = Submission::archived()->pluck('id');

        $this->assertContains($archived->id, $results);
        $this->assertNotContains($active->id, $results);
    }

    // -------------------------------------------------------------------------
    // scopePending
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_submissions_with_at_least_one_incomplete_submitter_as_pending(): void
    {
        // Pending submission: one submitter without completed_at
        $pending = Submission::factory()->create();
        Submitter::factory()->create([
            'submission_id' => $pending->id,
            'account_id'    => $pending->account_id,
            'completed_at'  => null,
        ]);

        // Completed submission: all submitters have completed_at
        $completed = Submission::factory()->create();
        Submitter::factory()->create([
            'submission_id' => $completed->id,
            'account_id'    => $completed->account_id,
            'completed_at'  => now(),
        ]);

        $results = Submission::pending()->pluck('id');

        $this->assertContains($pending->id, $results);
        $this->assertNotContains($completed->id, $results);
    }

    // -------------------------------------------------------------------------
    // scopeCompleted
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_submissions_where_all_submitters_are_completed(): void
    {
        // All submitters completed
        $completed = Submission::factory()->create();
        Submitter::factory()->create([
            'submission_id' => $completed->id,
            'account_id'    => $completed->account_id,
            'completed_at'  => now(),
        ]);

        // Still pending (one submitter without completed_at)
        $pending = Submission::factory()->create();
        Submitter::factory()->create([
            'submission_id' => $pending->id,
            'account_id'    => $pending->account_id,
            'completed_at'  => null,
        ]);

        // No submitters at all — should NOT appear in completed
        $empty = Submission::factory()->create();

        $results = Submission::completed()->pluck('id');

        $this->assertContains($completed->id, $results);
        $this->assertNotContains($pending->id, $results);
        $this->assertNotContains($empty->id, $results);
    }

    // -------------------------------------------------------------------------
    // scopeDeclined
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_submissions_with_at_least_one_declined_submitter(): void
    {
        $declined = Submission::factory()->create();
        Submitter::factory()->create([
            'submission_id' => $declined->id,
            'account_id'    => $declined->account_id,
            'declined_at'   => now(),
        ]);

        $normal = Submission::factory()->create();
        Submitter::factory()->create([
            'submission_id' => $normal->id,
            'account_id'    => $normal->account_id,
            'declined_at'   => null,
        ]);

        $results = Submission::declined()->pluck('id');

        $this->assertContains($declined->id, $results);
        $this->assertNotContains($normal->id, $results);
    }

    // -------------------------------------------------------------------------
    // scopeForAccount
    // -------------------------------------------------------------------------

    #[Test]
    public function it_filters_submissions_by_account_id(): void
    {
        $accountA = Account::factory()->create();
        $accountB = Account::factory()->create();

        $submissionA = Submission::factory()->create(['account_id' => $accountA->id]);
        $submissionB = Submission::factory()->create(['account_id' => $accountB->id]);

        $results = Submission::forAccount($accountA->id)->pluck('id');

        $this->assertContains($submissionA->id, $results);
        $this->assertNotContains($submissionB->id, $results);
    }

    // -------------------------------------------------------------------------
    // scopeSearch
    // -------------------------------------------------------------------------

    #[Test]
    public function it_finds_a_submission_by_its_name(): void
    {
        $match  = Submission::factory()->create(['name' => 'Contract Alpha']);
        $noMatch = Submission::factory()->create(['name' => 'Invoice Beta']);

        $results = Submission::search('Alpha')->pluck('id');

        $this->assertContains($match->id, $results);
        $this->assertNotContains($noMatch->id, $results);
    }

    #[Test]
    public function it_finds_a_submission_by_submitter_email(): void
    {
        $match = Submission::factory()->create();
        Submitter::factory()->create([
            'submission_id' => $match->id,
            'account_id'    => $match->account_id,
            'email'         => 'alice@example.com',
        ]);

        $noMatch = Submission::factory()->create();
        Submitter::factory()->create([
            'submission_id' => $noMatch->id,
            'account_id'    => $noMatch->account_id,
            'email'         => 'bob@other.com',
        ]);

        $results = Submission::search('alice')->pluck('id');

        $this->assertContains($match->id, $results);
        $this->assertNotContains($noMatch->id, $results);
    }

    #[Test]
    public function it_finds_a_submission_by_submitter_name(): void
    {
        $match = Submission::factory()->create();
        Submitter::factory()->create([
            'submission_id' => $match->id,
            'account_id'    => $match->account_id,
            'name'          => 'Charlie Brown',
        ]);

        $noMatch = Submission::factory()->create();
        Submitter::factory()->create([
            'submission_id' => $noMatch->id,
            'account_id'    => $noMatch->account_id,
            'name'          => 'Diana Prince',
        ]);

        $results = Submission::search('Charlie')->pluck('id');

        $this->assertContains($match->id, $results);
        $this->assertNotContains($noMatch->id, $results);
    }

    // -------------------------------------------------------------------------
    // status accessor
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_pending_status_when_no_submitters_are_present(): void
    {
        $submission = Submission::factory()->create(['expire_at' => null]);

        $this->assertSame('pending', $submission->status);
    }

    #[Test]
    public function it_returns_completed_status_when_all_submitters_have_completed_at(): void
    {
        $submission = Submission::factory()->create(['expire_at' => null]);
        Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id'    => $submission->account_id,
            'completed_at'  => now(),
            'declined_at'   => null,
        ]);

        $submission->refresh();
        $submission->load('submitters');

        $this->assertSame('completed', $submission->status);
    }

    #[Test]
    public function it_returns_declined_status_when_any_submitter_has_declined_at(): void
    {
        $submission = Submission::factory()->create(['expire_at' => null]);
        Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id'    => $submission->account_id,
            'declined_at'   => now(),
            'completed_at'  => null,
        ]);

        $submission->refresh();
        $submission->load('submitters');

        $this->assertSame('declined', $submission->status);
    }

    #[Test]
    public function it_returns_expired_status_when_expire_at_is_in_the_past(): void
    {
        $submission = Submission::factory()->create([
            'expire_at' => now()->subDay(),
        ]);

        $this->assertSame('expired', $submission->status);
    }

    #[Test]
    public function it_returns_pending_status_when_some_submitters_are_still_incomplete(): void
    {
        $submission = Submission::factory()->create(['expire_at' => null]);
        Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id'    => $submission->account_id,
            'completed_at'  => now(),
            'declined_at'   => null,
        ]);
        Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id'    => $submission->account_id,
            'completed_at'  => null,
            'declined_at'   => null,
        ]);

        $submission->refresh();
        $submission->load('submitters');

        $this->assertSame('pending', $submission->status);
    }
}
