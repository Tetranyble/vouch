<?php

namespace Tests\Unit\Actions\Submissions;

use App\Actions\Submissions\CreateSubmissionAction;
use App\Actions\Submitters\InviteSubmitterAction;
use App\Jobs\DispatchWebhookEventJob;
use App\Jobs\ReindexSearchEntryJob;
use App\Jobs\SendSubmitterInvitationJob;
use App\Jobs\SendSubmitterSmsInvitationJob;
use App\Models\Submission;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateSubmissionActionTest extends TestCase
{
    use RefreshDatabase;

    private CreateSubmissionAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->action = new CreateSubmissionAction(new InviteSubmitterAction);
    }

    private function templateWithSubmitters(int $count = 1, array $overrides = []): Template
    {
        $submitters = collect(range(0, $count - 1))->map(fn ($i) => [
            'name' => "Party {$i}",
            'uuid' => (string) Str::uuid(),
        ])->all();

        return Template::factory()->create(array_merge(['submitters' => $submitters], $overrides));
    }

    public function test_creates_submission_record(): void
    {
        $template = $this->templateWithSubmitters();
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $submission = $this->action->execute($template, $user, [['email' => 'a@b.com']], 'invite');

        $this->assertDatabaseHas('submissions', [
            'template_id' => $template->id,
            'account_id' => $user->account_id,
            'created_by_user_id' => $user->id,
            'source' => 'invite',
        ]);
        $this->assertInstanceOf(Submission::class, $submission);
    }

    public function test_creates_one_submitter_per_template_submitter(): void
    {
        $template = $this->templateWithSubmitters(2);
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $submission = $this->action->execute($template, $user, [
            ['email' => 'first@b.com'],
            ['email' => 'second@b.com'],
        ]);

        $this->assertCount(2, $submission->submitters);
    }

    public function test_submitter_gets_correct_email_from_input(): void
    {
        $template = $this->templateWithSubmitters();
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $submission = $this->action->execute($template, $user, [['email' => 'test@example.com']]);

        $this->assertEquals('test@example.com', $submission->submitters->first()->email);
    }

    public function test_submitter_gets_correct_phone_from_input(): void
    {
        $template = $this->templateWithSubmitters();
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $submission = $this->action->execute($template, $user, [['phone' => '+1234567890']]);

        $this->assertEquals('+1234567890', $submission->submitters->first()->phone);
    }

    public function test_first_submitter_sent_at_is_set_when_email_provided(): void
    {
        $template = $this->templateWithSubmitters();
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $submission = $this->action->execute($template, $user, [['email' => 'a@b.com']]);

        $this->assertNotNull($submission->submitters->first()->sent_at);
    }

    public function test_first_submitter_sent_at_is_null_when_no_contact(): void
    {
        $template = $this->templateWithSubmitters();
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $submission = $this->action->execute($template, $user, [['name' => 'John']]);

        $this->assertNull($submission->submitters->first()->sent_at);
    }

    public function test_subsequent_submitters_sent_at_is_null(): void
    {
        $template = $this->templateWithSubmitters(2);
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $submission = $this->action->execute($template, $user, [
            ['email' => 'first@b.com'],
            ['email' => 'second@b.com'],
        ], submittersOrder: 'preserved');

        $this->assertNull($submission->submitters->get(1)->sent_at);
    }

    public function test_stores_expire_at_on_submission(): void
    {
        $template = $this->templateWithSubmitters();
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $submission = $this->action->execute($template, $user, [], expireAt: '2030-12-31');

        $this->assertNotNull($submission->expire_at);
    }

    public function test_dispatches_webhook_created_event(): void
    {
        $template = $this->templateWithSubmitters();
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $submission = $this->action->execute($template, $user, [['email' => 'a@b.com']]);

        Queue::assertPushed(DispatchWebhookEventJob::class, fn ($job) => $job->eventType === 'submission.created'
            && $job->accountId === $user->account_id
            && $job->submissionId === $submission->id
        );
    }

    public function test_dispatches_reindex_job(): void
    {
        $template = $this->templateWithSubmitters();
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $submission = $this->action->execute($template, $user, [['email' => 'a@b.com']]);

        Queue::assertPushed(ReindexSearchEntryJob::class, fn ($job) => $job->recordType === 'Submission' && $job->recordId === $submission->id
        );
    }

    public function test_dispatches_email_invitation_for_first_submitter(): void
    {
        $template = $this->templateWithSubmitters();
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $this->action->execute($template, $user, [['email' => 'invite@example.com']]);

        Queue::assertPushed(SendSubmitterInvitationJob::class);
    }

    public function test_dispatches_sms_invitation_when_first_submitter_has_only_phone(): void
    {
        $template = $this->templateWithSubmitters();
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $this->action->execute($template, $user, [['phone' => '+1234567890']]);

        Queue::assertPushed(SendSubmitterSmsInvitationJob::class);
        Queue::assertNotPushed(SendSubmitterInvitationJob::class);
    }

    public function test_no_invitation_dispatched_when_first_submitter_has_no_contact(): void
    {
        $template = $this->templateWithSubmitters();
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $this->action->execute($template, $user, [['name' => 'Anonymous']]);

        Queue::assertNotPushed(SendSubmitterInvitationJob::class);
        Queue::assertNotPushed(SendSubmitterSmsInvitationJob::class);
    }

    public function test_source_is_stored_on_submission(): void
    {
        $template = $this->templateWithSubmitters();
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $submission = $this->action->execute($template, $user, [], source: 'bulk_invite');

        $this->assertEquals('bulk_invite', $submission->source);
    }

    public function test_returns_submission_with_loaded_submitters(): void
    {
        $template = $this->templateWithSubmitters(2);
        $user = User::factory()->create(['account_id' => $template->account_id]);

        $submission = $this->action->execute($template, $user, [
            ['email' => 'a@b.com'],
            ['email' => 'c@d.com'],
        ]);

        $this->assertTrue($submission->relationLoaded('submitters'));
        $this->assertCount(2, $submission->submitters);
    }

    public function test_a_failed_signer_insert_does_not_leave_an_orphaned_submission(): void
    {
        // A malformed signer role causes the second insert to fail; transaction rolls back the first.
        $template = $this->templateWithSubmitters(1, [
            'submitters' => [['name' => 'Missing UUID']],
        ]);
        $user = User::factory()->create(['account_id' => $template->account_id]);
        try {
            $this->action->execute($template, $user, [['email' => 'someone@example.test']]);
            $this->fail('Expected invalid signer role to be rejected.');
        } catch (\ErrorException $e) {
            $this->assertDatabaseCount('submissions', 0);
            $this->assertDatabaseCount('submitters', 0);
            Queue::assertNothingPushed();
        }
    }

    public function test_rejects_template_from_another_account_before_creating_records(): void
    {
        $template = $this->templateWithSubmitters();
        $user = User::factory()->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->action->execute($template, $user, [['email' => 'attacker@example.com']]);
    }
}
