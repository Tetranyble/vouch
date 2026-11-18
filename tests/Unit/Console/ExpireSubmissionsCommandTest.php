<?php

namespace Tests\Unit\Console;

use App\Jobs\DispatchWebhookEventJob;
use App\Models\Submission;
use App\Models\SubmissionEvent;
use App\Models\Submitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpireSubmissionsCommandTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a submission that is past its expire_at date and has at least one
     * pending (not completed, not declined) submitter.
     */
    private function createOverdueSubmission(array $submitterOverrides = []): array
    {
        $submission = Submission::factory()->create([
            'expire_at' => now()->subHour(),
            'archived_at' => null,
        ]);

        $submitter = Submitter::factory()->create(array_merge([
            'submission_id' => $submission->id,
            'account_id' => $submission->account_id,
            'completed_at' => null,
            'declined_at' => null,
        ], $submitterOverrides));

        return [$submission, $submitter];
    }

    // -------------------------------------------------------------------------
    // Core expiry behaviour
    // -------------------------------------------------------------------------

    #[Test]
    public function it_marks_overdue_submitters_as_expired(): void
    {
        Queue::fake();

        [$submission, $submitter] = $this->createOverdueSubmission();

        $this->artisan('vouch:expire-submissions')->assertSuccessful();

        $this->assertNotNull($submitter->fresh()->declined_at);
    }

    #[Test]
    public function it_creates_a_submission_expired_event_for_each_expired_submitter(): void
    {
        Queue::fake();

        [$submission, $submitter] = $this->createOverdueSubmission();

        $this->artisan('vouch:expire-submissions')->assertSuccessful();

        $event = SubmissionEvent::where('submission_id', $submission->id)
            ->where('submitter_id', $submitter->id)
            ->where('event_type', 'submission.expired')
            ->first();

        $this->assertNotNull($event, 'Expected a submission.expired SubmissionEvent to be created.');
        $this->assertSame('expire_at exceeded', $event->data['reason'] ?? null);
    }

    #[Test]
    public function it_dispatches_a_webhook_event_job_for_each_expired_submitter(): void
    {
        Queue::fake();

        [$submission, $submitter] = $this->createOverdueSubmission();

        $this->artisan('vouch:expire-submissions')->assertSuccessful();

        Queue::assertPushed(DispatchWebhookEventJob::class, function ($job) use ($submission, $submitter) {
            return $job->eventType === 'submission.expired'
                && $job->accountId === $submission->account_id
                && $job->submitterId === $submitter->id
                && $job->submissionId === $submission->id;
        });
    }

    // -------------------------------------------------------------------------
    // Submissions without expire_at are not touched
    // -------------------------------------------------------------------------

    #[Test]
    public function it_does_not_expire_submissions_that_have_no_expire_at_set(): void
    {
        Queue::fake();

        $submission = Submission::factory()->create([
            'expire_at' => null,
            'archived_at' => null,
        ]);
        $submitter = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $submission->account_id,
            'completed_at' => null,
            'declined_at' => null,
        ]);

        $this->artisan('vouch:expire-submissions')->assertSuccessful();

        $this->assertNull($submitter->fresh()->declined_at);
        Queue::assertNothingPushed();
    }

    // -------------------------------------------------------------------------
    // Future expire_at is not touched
    // -------------------------------------------------------------------------

    #[Test]
    public function it_does_not_expire_submissions_whose_expire_at_is_in_the_future(): void
    {
        Queue::fake();

        $submission = Submission::factory()->create([
            'expire_at' => now()->addDay(),
            'archived_at' => null,
        ]);
        $submitter = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $submission->account_id,
            'completed_at' => null,
            'declined_at' => null,
        ]);

        $this->artisan('vouch:expire-submissions')->assertSuccessful();

        $this->assertNull($submitter->fresh()->declined_at);
        Queue::assertNothingPushed();
    }

    // -------------------------------------------------------------------------
    // Already-completed submitters are not touched
    // -------------------------------------------------------------------------

    #[Test]
    public function it_does_not_touch_already_completed_submitters_on_an_overdue_submission(): void
    {
        Queue::fake();

        $submission = Submission::factory()->create([
            'expire_at' => now()->subHour(),
            'archived_at' => null,
        ]);

        $completed = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $submission->account_id,
            'completed_at' => now()->subDay(),
            'declined_at' => null,
        ]);

        // There must also be a pending submitter for the submission to be
        // picked up by the query (whereHas pending submitters).
        $pending = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $submission->account_id,
            'completed_at' => null,
            'declined_at' => null,
        ]);

        $this->artisan('vouch:expire-submissions')->assertSuccessful();

        // The already-completed submitter should remain untouched
        $this->assertNull($completed->fresh()->declined_at);
        // The pending submitter should be expired
        $this->assertNotNull($pending->fresh()->declined_at);
    }

    // -------------------------------------------------------------------------
    // Already-declined submitters are not touched
    // -------------------------------------------------------------------------

    #[Test]
    public function it_does_not_touch_already_declined_submitters_on_an_overdue_submission(): void
    {
        Queue::fake();

        $submission = Submission::factory()->create([
            'expire_at' => now()->subHour(),
            'archived_at' => null,
        ]);

        $alreadyDeclinedAt = now()->subDays(2);
        $declined = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $submission->account_id,
            'completed_at' => null,
            'declined_at' => $alreadyDeclinedAt,
        ]);

        // A pending submitter so the submission is picked up by the query
        $pending = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $submission->account_id,
            'completed_at' => null,
            'declined_at' => null,
        ]);

        $this->artisan('vouch:expire-submissions')->assertSuccessful();

        // The previously-declined submitter's declined_at must not change
        $this->assertEquals(
            $alreadyDeclinedAt->toDateTimeString(),
            $declined->fresh()->declined_at->toDateTimeString(),
        );
    }

    // -------------------------------------------------------------------------
    // Archived submissions are not touched
    // -------------------------------------------------------------------------

    #[Test]
    public function it_does_not_expire_archived_submissions(): void
    {
        Queue::fake();

        $submission = Submission::factory()->create([
            'expire_at' => now()->subHour(),
            'archived_at' => now()->subDay(),
        ]);
        $submitter = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $submission->account_id,
            'completed_at' => null,
            'declined_at' => null,
        ]);

        $this->artisan('vouch:expire-submissions')->assertSuccessful();

        $this->assertNull($submitter->fresh()->declined_at);
        Queue::assertNothingPushed();
    }

    // -------------------------------------------------------------------------
    // Multiple submitters on one submission
    // -------------------------------------------------------------------------

    #[Test]
    public function it_expires_all_pending_submitters_on_one_overdue_submission(): void
    {
        Queue::fake();

        $submission = Submission::factory()->create([
            'expire_at' => now()->subHour(),
            'archived_at' => null,
        ]);

        $submitterA = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $submission->account_id,
            'completed_at' => null,
            'declined_at' => null,
        ]);

        $submitterB = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $submission->account_id,
            'completed_at' => null,
            'declined_at' => null,
        ]);

        $this->artisan('vouch:expire-submissions')->assertSuccessful();

        $this->assertNotNull($submitterA->fresh()->declined_at);
        $this->assertNotNull($submitterB->fresh()->declined_at);

        Queue::assertPushed(DispatchWebhookEventJob::class, 2);
    }
}
