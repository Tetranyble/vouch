<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\SearchEntry;
use App\Models\Template;
use App\Models\TemplateFolder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // scopeActive
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_only_non_archived_templates_with_active_scope(): void
    {
        $active = Template::factory()->create(['archived_at' => null]);
        $archived = Template::factory()->create(['archived_at' => now()]);

        $results = Template::active()->pluck('id');

        $this->assertContains($active->id, $results);
        $this->assertNotContains($archived->id, $results);
    }

    // -------------------------------------------------------------------------
    // scopeArchived
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_only_archived_templates_with_archived_scope(): void
    {
        $active = Template::factory()->create(['archived_at' => null]);
        $archived = Template::factory()->create(['archived_at' => now()]);

        $results = Template::archived()->pluck('id');

        $this->assertContains($archived->id, $results);
        $this->assertNotContains($active->id, $results);
    }

    // -------------------------------------------------------------------------
    // scopeForAccount
    // -------------------------------------------------------------------------

    #[Test]
    public function it_filters_templates_by_account_id(): void
    {
        $accountA = Account::factory()->create();
        $accountB = Account::factory()->create();

        $templateA = Template::factory()->create(['account_id' => $accountA->id]);
        $templateB = Template::factory()->create(['account_id' => $accountB->id]);

        $results = Template::forAccount($accountA->id)->pluck('id');

        $this->assertContains($templateA->id, $results);
        $this->assertNotContains($templateB->id, $results);
    }

    // -------------------------------------------------------------------------
    // scopeSearch
    // -------------------------------------------------------------------------

    #[Test]
    public function it_finds_templates_by_name_using_search_scope(): void
    {
        $match = Template::factory()->create(['name' => 'Invoice Template']);
        $noMatch = Template::factory()->create(['name' => 'Contract Form']);

        $results = Template::search('Invoice')->pluck('id');

        $this->assertContains($match->id, $results);
        $this->assertNotContains($noMatch->id, $results);
    }

    #[Test]
    public function it_performs_a_case_insensitive_partial_match_in_search_scope(): void
    {
        $match = Template::factory()->create(['name' => 'NDA Agreement']);

        $results = Template::search('nda')->pluck('id');

        $this->assertContains($match->id, $results);
    }

    // -------------------------------------------------------------------------
    // folderName accessor
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_the_folder_name_when_a_folder_is_assigned(): void
    {
        $folder = TemplateFolder::factory()->create(['name' => 'Legal Docs']);
        $template = Template::factory()->create(['folder_id' => $folder->id]);

        $this->assertSame('Legal Docs', $template->folder_name);
    }

    #[Test]
    public function it_returns_default_when_no_folder_relation_is_loaded(): void
    {
        // getFolderNameAttribute returns $this->folder?->name ?? 'Default'.
        // When the 'folder' relation is explicitly set to null on the model
        // (simulating a missing/deleted folder), the accessor falls back to 'Default'.
        $template = Template::factory()->make();

        // Force the relation to resolve as null without touching the database
        $template->setRelation('folder', null);

        $this->assertSame('Default', $template->folder_name);
    }

    // -------------------------------------------------------------------------
    // searchEntry morphOne relationship
    // -------------------------------------------------------------------------

    #[Test]
    public function it_has_a_morph_one_search_entry_relationship(): void
    {
        $template = Template::factory()->create(['name' => 'Searchable Template']);

        // The morphOne relationship stores the full class name as record_type
        SearchEntry::create([
            'record_type' => $template->getMorphClass(),
            'record_id' => $template->id,
            'account_id' => $template->account_id,
            'tsvector' => $template->name,
            'ngram' => $template->name,
        ]);

        $entry = $template->searchEntry;

        $this->assertNotNull($entry);
        $this->assertSame($template->getMorphClass(), $entry->record_type);
        $this->assertSame($template->id, $entry->record_id);
    }
}
