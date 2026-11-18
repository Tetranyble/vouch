<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Models\Submission;
use App\Models\Submitter;
use App\Models\Template;
use App\Models\User;
use App\Models\UserConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SignerPrefillPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function signer(): Submitter
    {
        $template = Template::factory()->create();
        $submission = Submission::factory()->create(['template_id' => $template->id]);

        return Submitter::factory()->create([
            'submission_id' => $submission->id,
            'account_id' => $template->account_id,
            'email' => 'recipient@example.test',
        ]);
    }

    #[Test]
    public function a_signing_link_does_not_disclose_another_users_stored_signature(): void
    {
        $submitter = $this->signer();
        $owner = User::factory()->create(['account_id' => $submitter->account_id]);
        UserConfig::factory()->create([
            'user_id' => $owner->id,
            'key' => 'signature',
            'value' => 'private-signature',
        ]);

        $this->actingAs($owner)
            ->get('/s/'.$submitter->slug)
            ->assertInertia(fn ($page) => $page
                ->where('previousSignature', null)
                ->where('previousInitials', null));
    }

    #[Test]
    public function a_signing_link_for_the_same_verified_session_can_prefill_its_own_signature(): void
    {
        $submitter = $this->signer();
        $owner = User::factory()->create([
            'account_id' => $submitter->account_id,
            'email' => $submitter->email,
        ]);
        UserConfig::factory()->create([
            'user_id' => $owner->id,
            'key' => 'signature',
            'value' => 'my-signature',
        ]);

        $this->actingAs($owner)
            ->get('/s/'.$submitter->slug)
            ->assertInertia(fn ($page) => $page->where('previousSignature', 'my-signature'));
    }
}
