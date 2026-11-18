<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Models\CompletedDocument;
use App\Models\Submission;
use App\Models\Submitter;
use App\Models\User;
use App\Services\DocumentStorage;
use App\Support\SubmissionPreviewLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmissionPreviewSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    #[Test]
    public function a_submission_slug_alone_never_exposes_public_preview_or_pdf_bytes(): void
    {
        $submission = Submission::factory()->create();
        $signer = Submitter::factory()->create([
            'submission_id' => $submission->id, 'account_id' => $submission->account_id,
            'completed_at' => now(),
        ]);
        $reference = app(DocumentStorage::class)->putContent('private bytes', 'signed.pdf', $submission->account_id, 'signed-document');
        $doc = CompletedDocument::factory()->create([
            'submitter_id' => $signer->id,
            'filename' => 'signed.pdf',
            'storage_path' => $reference,
        ]);

        $this->get('/e/'.$submission->slug)->assertNotFound();
        $this->get('/e/'.$submission->slug.'/download/'.$doc->id)->assertNotFound();
        $this->get(SubmissionPreviewLink::for($submission))->assertOk();
        $this->get(\Illuminate\Support\Facades\URL::temporarySignedRoute(
            'submission-preview.download', now()->addMinutes(30),
            ['slug' => $submission->slug, 'doc_id' => $doc->id]
        ))->assertOk()->assertSee('private bytes', false);
    }

    #[Test]
    public function a_member_of_another_workspace_cannot_view_the_preview_without_a_signed_link(): void
    {
        $submission = Submission::factory()->create();
        Submitter::factory()->create([
            'submission_id' => $submission->id, 'account_id' => $submission->account_id,
            'completed_at' => now(),
        ]);
        $otherAccount = \App\Models\Account::factory()->create();
        $user = User::factory()->create(['account_id' => $otherAccount->id]);
        $this->actingAs($user)->get('/e/'.$submission->slug)->assertNotFound();
    }
}
