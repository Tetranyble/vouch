<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ReindexSearchEntryJob;
use App\Models\SearchEntry;
use App\Models\Submission;
use App\Models\Submitter;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReindexSearchEntryJobTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Template
    // -------------------------------------------------------------------------

    #[Test]
    public function it_creates_a_search_entry_for_a_template_containing_its_name(): void
    {
        $template = Template::factory()->create(['name' => 'Vendor Contract']);

        (new ReindexSearchEntryJob('Template', $template->id))->handle();

        $entry = SearchEntry::where('record_type', 'Template')
            ->where('record_id', $template->id)
            ->firstOrFail();

        $this->assertStringContainsString('Vendor Contract', $entry->tsvector);
        $this->assertSame($template->account_id, $entry->account_id);
    }

    // -------------------------------------------------------------------------
    // Submission
    // -------------------------------------------------------------------------

    #[Test]
    public function it_creates_a_search_entry_for_a_submission_containing_submitter_email(): void
    {
        $submission = Submission::factory()->create(['name' => 'Q4 Agreement']);
        Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id'    => $submission->account_id,
            'email'         => 'vendor@acme.com',
            'name'          => null,
            'phone'         => null,
        ]);

        (new ReindexSearchEntryJob('Submission', $submission->id))->handle();

        $entry = SearchEntry::where('record_type', 'Submission')
            ->where('record_id', $submission->id)
            ->firstOrFail();

        $this->assertStringContainsString('vendor@acme.com', $entry->tsvector);
        $this->assertSame($submission->account_id, $entry->account_id);
    }

    #[Test]
    public function it_includes_the_submission_name_in_the_search_entry_tsvector(): void
    {
        $submission = Submission::factory()->create(['name' => 'Master Services Agreement']);
        Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id'    => $submission->account_id,
            'email'         => 'client@example.com',
        ]);

        (new ReindexSearchEntryJob('Submission', $submission->id))->handle();

        $entry = SearchEntry::where('record_type', 'Submission')
            ->where('record_id', $submission->id)
            ->firstOrFail();

        $this->assertStringContainsString('Master Services Agreement', $entry->tsvector);
    }

    // -------------------------------------------------------------------------
    // Submitter
    // -------------------------------------------------------------------------

    #[Test]
    public function it_creates_a_search_entry_for_a_submitter_with_name_and_email(): void
    {
        $submitter = Submitter::factory()->create([
            'name'  => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => null,
        ]);

        (new ReindexSearchEntryJob('Submitter', $submitter->id))->handle();

        $entry = SearchEntry::where('record_type', 'Submitter')
            ->where('record_id', $submitter->id)
            ->firstOrFail();

        $this->assertStringContainsString('Jane Doe', $entry->tsvector);
        $this->assertStringContainsString('jane@example.com', $entry->tsvector);
        $this->assertSame($submitter->account_id, $entry->account_id);
    }

    #[Test]
    public function it_includes_phone_in_the_submitter_search_entry_when_present(): void
    {
        $submitter = Submitter::factory()->create([
            'name'  => null,
            'email' => null,
            'phone' => '+15550001234',
        ]);

        (new ReindexSearchEntryJob('Submitter', $submitter->id))->handle();

        $entry = SearchEntry::where('record_type', 'Submitter')
            ->where('record_id', $submitter->id)
            ->firstOrFail();

        $this->assertStringContainsString('+15550001234', $entry->tsvector);
    }

    // -------------------------------------------------------------------------
    // Idempotency (update, not duplicate)
    // -------------------------------------------------------------------------

    #[Test]
    public function it_updates_an_existing_search_entry_instead_of_creating_a_duplicate(): void
    {
        $template = Template::factory()->create(['name' => 'Original Name']);

        // First indexing
        (new ReindexSearchEntryJob('Template', $template->id))->handle();

        $this->assertSame(1, SearchEntry::where('record_type', 'Template')
            ->where('record_id', $template->id)
            ->count());

        // Update the template name and reindex
        $template->update(['name' => 'Updated Name']);
        (new ReindexSearchEntryJob('Template', $template->id))->handle();

        $entries = SearchEntry::where('record_type', 'Template')
            ->where('record_id', $template->id)
            ->get();

        $this->assertCount(1, $entries);
        $this->assertStringContainsString('Updated Name', $entries->first()->tsvector);
    }

    // -------------------------------------------------------------------------
    // Missing record — no-op
    // -------------------------------------------------------------------------

    #[Test]
    public function it_does_not_crash_when_the_record_id_does_not_exist(): void
    {
        $nonExistentId = 999999;

        // Should not throw any exception
        (new ReindexSearchEntryJob('Template', $nonExistentId))->handle();

        $this->assertSame(0, SearchEntry::where('record_type', 'Template')
            ->where('record_id', $nonExistentId)
            ->count());
    }

    #[Test]
    public function it_does_not_crash_for_a_missing_submission_id(): void
    {
        $nonExistentId = 999999;

        (new ReindexSearchEntryJob('Submission', $nonExistentId))->handle();

        $this->assertSame(0, SearchEntry::where('record_type', 'Submission')
            ->where('record_id', $nonExistentId)
            ->count());
    }

    #[Test]
    public function it_does_not_crash_for_a_missing_submitter_id(): void
    {
        $nonExistentId = 999999;

        (new ReindexSearchEntryJob('Submitter', $nonExistentId))->handle();

        $this->assertSame(0, SearchEntry::where('record_type', 'Submitter')
            ->where('record_id', $nonExistentId)
            ->count());
    }
}
