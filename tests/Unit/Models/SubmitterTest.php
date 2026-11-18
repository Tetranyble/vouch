<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\SearchEntry;
use App\Models\Submission;
use App\Models\Submitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmitterTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // status attribute
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_declined_status_when_declined_at_is_set(): void
    {
        $submitter = Submitter::factory()->create([
            'declined_at'  => now(),
            'completed_at' => null,
            'opened_at'    => null,
            'sent_at'      => null,
        ]);

        $this->assertSame('declined', $submitter->status);
    }

    #[Test]
    public function it_returns_completed_status_when_completed_at_is_set(): void
    {
        $submitter = Submitter::factory()->create([
            'declined_at'  => null,
            'completed_at' => now(),
            'opened_at'    => null,
            'sent_at'      => null,
        ]);

        $this->assertSame('completed', $submitter->status);
    }

    #[Test]
    public function it_returns_opened_status_when_opened_at_is_set(): void
    {
        $submitter = Submitter::factory()->create([
            'declined_at'  => null,
            'completed_at' => null,
            'opened_at'    => now(),
            'sent_at'      => null,
        ]);

        $this->assertSame('opened', $submitter->status);
    }

    #[Test]
    public function it_returns_sent_status_when_only_sent_at_is_set(): void
    {
        $submitter = Submitter::factory()->create([
            'declined_at'  => null,
            'completed_at' => null,
            'opened_at'    => null,
            'sent_at'      => now(),
        ]);

        $this->assertSame('sent', $submitter->status);
    }

    #[Test]
    public function it_returns_awaiting_status_when_no_timestamps_are_set(): void
    {
        $submitter = Submitter::factory()->create([
            'declined_at'  => null,
            'completed_at' => null,
            'opened_at'    => null,
            'sent_at'      => null,
        ]);

        $this->assertSame('awaiting', $submitter->status);
    }

    #[Test]
    public function it_prioritises_declined_over_completed_when_both_are_set(): void
    {
        $submitter = Submitter::factory()->create([
            'declined_at'  => now(),
            'completed_at' => now(),
        ]);

        $this->assertSame('declined', $submitter->status);
    }

    // -------------------------------------------------------------------------
    // displayName attribute
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_name_as_display_name_when_name_is_present(): void
    {
        $submitter = Submitter::factory()->make([
            'name'  => 'Alice Smith',
            'email' => 'alice@example.com',
            'phone' => '+15551234567',
        ]);

        $this->assertSame('Alice Smith', $submitter->display_name);
    }

    #[Test]
    public function it_falls_back_to_email_when_name_is_absent(): void
    {
        $submitter = Submitter::factory()->make([
            'name'  => null,
            'email' => 'bob@example.com',
            'phone' => '+15557654321',
        ]);

        $this->assertSame('bob@example.com', $submitter->display_name);
    }

    #[Test]
    public function it_falls_back_to_phone_when_name_and_email_are_absent(): void
    {
        $submitter = Submitter::factory()->make([
            'name'  => null,
            'email' => null,
            'phone' => '+15551112222',
        ]);

        $this->assertSame('+15551112222', $submitter->display_name);
    }

    #[Test]
    public function it_returns_unknown_when_name_email_and_phone_are_all_absent(): void
    {
        $submitter = Submitter::factory()->make([
            'name'  => null,
            'email' => null,
            'phone' => null,
        ]);

        $this->assertSame('Unknown', $submitter->display_name);
    }

    // -------------------------------------------------------------------------
    // searchEntry morphOne relationship
    // -------------------------------------------------------------------------

    #[Test]
    public function it_has_a_morph_one_search_entry_relationship(): void
    {
        $submitter = Submitter::factory()->create();

        // The morphOne relationship stores the full class name as record_type
        SearchEntry::create([
            'record_type' => $submitter->getMorphClass(),
            'record_id'   => $submitter->id,
            'account_id'  => $submitter->account_id,
            'tsvector'    => $submitter->name . ' ' . $submitter->email,
            'ngram'       => $submitter->name . ' ' . $submitter->email,
        ]);

        $entry = $submitter->searchEntry;

        $this->assertNotNull($entry);
        $this->assertSame($submitter->getMorphClass(), $entry->record_type);
        $this->assertSame($submitter->id, $entry->record_id);
    }
}
