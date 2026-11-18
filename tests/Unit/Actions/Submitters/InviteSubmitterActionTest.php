<?php

namespace Tests\Unit\Actions\Submitters;

use App\Actions\Submitters\InviteSubmitterAction;
use App\Jobs\SendSubmitterInvitationJob;
use App\Jobs\SendSubmitterSmsInvitationJob;
use App\Models\Submitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InviteSubmitterActionTest extends TestCase
{
    use RefreshDatabase;

    private InviteSubmitterAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->action = new InviteSubmitterAction;
    }

    public function test_dispatches_email_invitation_when_submitter_has_email(): void
    {
        $submitter = Submitter::factory()->create(['email' => 'user@example.com', 'phone' => null]);

        $this->action->execute($submitter, 'Sender Name', 'My Template');

        Queue::assertPushed(SendSubmitterInvitationJob::class, fn ($job) => $job->submitterId === $submitter->id
            && $job->senderName === 'Sender Name'
            && $job->templateName === 'My Template'
        );
        Queue::assertNotPushed(SendSubmitterSmsInvitationJob::class);
    }

    public function test_dispatches_sms_invitation_when_submitter_has_only_phone(): void
    {
        $submitter = Submitter::factory()->create(['email' => null, 'phone' => '+1234567890']);

        $this->action->execute($submitter, 'Sender Name', 'My Template');

        Queue::assertPushed(SendSubmitterSmsInvitationJob::class, fn ($job) => $job->submitterId === $submitter->id
            && $job->senderName === 'Sender Name'
            && $job->templateName === 'My Template'
        );
        Queue::assertNotPushed(SendSubmitterInvitationJob::class);
    }

    public function test_prefers_email_over_sms_when_both_present(): void
    {
        $submitter = Submitter::factory()->create(['email' => 'user@example.com', 'phone' => '+1234567890']);

        $this->action->execute($submitter, 'Sender', 'Template');

        Queue::assertPushed(SendSubmitterInvitationJob::class);
        Queue::assertNotPushed(SendSubmitterSmsInvitationJob::class);
    }

    public function test_dispatches_nothing_when_submitter_has_no_contact(): void
    {
        $submitter = Submitter::factory()->create(['email' => null, 'phone' => null]);

        $this->action->execute($submitter, 'Sender', 'Template');

        Queue::assertNothingPushed();
    }
}
