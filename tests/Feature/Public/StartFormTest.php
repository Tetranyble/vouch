<?php

namespace Tests\Feature\Public;

use App\Models\Account;
use App\Models\Template;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StartFormTest extends TestCase
{
    use RefreshDatabase;

    private function makeTemplate(array $overrides = []): Template
    {
        $account = Account::factory()->create();
        $author = User::factory()->create(['account_id' => $account->id]);
        $folder = TemplateFolder::factory()->create(['account_id' => $account->id, 'author_id' => $author->id]);

        $submitters = [
            ['name' => 'First Party', 'uuid' => (string) \Illuminate\Support\Str::uuid()],
        ];

        return Template::factory()->create(array_merge([
            'account_id' => $account->id,
            'author_id' => $author->id,
            'folder_id' => $folder->id,
            'shared_link' => true,
            'submitters' => $submitters,
        ], $overrides));
    }

    #[Test]
    public function get_start_form_with_valid_slug_renders_page(): void
    {
        $template = $this->makeTemplate();

        $response = $this->get("/d/{$template->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Public/StartForm/Show')
            ->has('template')
            ->where('template.slug', $template->slug)
            ->where('template.name', $template->name)
        );
    }

    #[Test]
    public function get_start_form_with_invalid_slug_returns_404(): void
    {
        $response = $this->get('/d/nonexistent-slug');

        $response->assertStatus(404);
    }

    #[Test]
    public function put_start_form_creates_submission_and_submitters_and_redirects(): void
    {
        $template = $this->makeTemplate();

        $response = $this->put("/d/{$template->slug}", [
            'submitters' => [
                ['name' => 'Alice', 'email' => 'alice@example.com'],
            ],
        ]);

        // Should redirect to the first submitter's signing page
        $response->assertRedirect();

        // A submission was created for this template
        $this->assertDatabaseHas('submissions', [
            'template_id' => $template->id,
            'account_id' => $template->account_id,
            'source' => 'link',
        ]);

        // A submitter was created
        $this->assertDatabaseHas('submitters', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'account_id' => $template->account_id,
        ]);
    }

    #[Test]
    public function put_start_form_without_submitter_data_still_creates_submission(): void
    {
        $template = $this->makeTemplate();

        $response = $this->put("/d/{$template->slug}", []);

        $response->assertRedirect();

        $this->assertDatabaseHas('submissions', [
            'template_id' => $template->id,
            'source' => 'link',
        ]);
    }

    #[Test]
    public function put_start_form_creates_correct_number_of_submitters(): void
    {
        $submitters = [
            ['name' => 'First Party',  'uuid' => (string) \Illuminate\Support\Str::uuid()],
            ['name' => 'Second Party', 'uuid' => (string) \Illuminate\Support\Str::uuid()],
        ];
        $template = $this->makeTemplate(['submitters' => $submitters]);

        $this->put("/d/{$template->slug}", [
            'submitters' => [
                ['name' => 'Alice', 'email' => 'alice@example.com'],
                ['name' => 'Bob',   'email' => 'bob@example.com'],
            ],
        ]);

        $this->assertDatabaseHas('submitters', ['name' => 'Alice', 'email' => 'alice@example.com']);
        $this->assertDatabaseHas('submitters', ['name' => 'Bob',   'email' => 'bob@example.com']);
    }

    #[Test]
    public function a_submission_preserves_original_documents_after_template_edits(): void
    {
        $original = [[
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'filename' => 'original.pdf', 'storage_path' => 'media:123',
        ]];
        $template = $this->makeTemplate(['documents' => $original]);
        $this->put('/d/'.$template->slug)->assertRedirect();
        $submission = \App\Models\Submission::where('template_id', $template->id)->firstOrFail();
        $template->update(['documents' => []]);
        $this->assertSame($original, $submission->fresh()->template_documents);
    }

    #[Test]
    public function a_cross_account_browser_session_is_not_recorded_as_the_submission_creator(): void
    {
        $template = $this->makeTemplate();
        $differentAccount = Account::factory()->create();
        $otherUser = User::factory()->create(['account_id' => $differentAccount->id]);
        $this->actingAs($otherUser)->put('/d/'.$template->slug)->assertRedirect();
        $submission = \App\Models\Submission::where('template_id', $template->id)->firstOrFail();
        $this->assertNull($submission->created_by_user_id);
    }

    #[Test]
    public function get_start_form_with_invalid_slug_does_not_leak_other_template_data(): void
    {
        // Ensure an existing template is in the DB, but a different slug returns 404
        $this->makeTemplate(['slug' => 'real-slug-here00']);

        $response = $this->get('/d/completely-wrong-slug');

        $response->assertStatus(404);
    }
}
