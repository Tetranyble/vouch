<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Submission;
use App\Models\Template;
use App\Models\Submitter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_api_token_cannot_upload_into_a_different_accounts_submission(): void
    {
        $user = User::factory()->create();
        $signer = Submitter::factory()->create();
        $token = $user->createToken('attachment-test')->plainTextToken;

        $this->withToken($token)->post('/api/attachments', [
            'submitter_slug' => $signer->slug,
            'file' => UploadedFile::fake()->create('authorization-test.pdf', 2, 'application/pdf'),
        ])->assertForbidden();

    }

    #[Test]
    public function completed_signers_cannot_upload_more_files(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'author_id' => $user->id]);
        $submission = Submission::factory()->create(['template_id' => $template->id]);
        $signer = Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $user->account_id,
            'completed_at' => now(),
        ]);
        $token = $user->createToken('attachment-test')->plainTextToken;

        $this->withToken($token)->post('/api/attachments', [
            'submitter_slug' => $signer->slug,
            'file' => UploadedFile::fake()->create('authorization-test.pdf', 2, 'application/pdf'),
        ])->assertStatus(422);
    }
}
