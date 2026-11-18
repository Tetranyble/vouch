<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StartFormAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function unpublished_templates_cannot_be_started_using_a_guessed_slug(): void
    {
        $template = Template::factory()->create(['shared_link' => false]);
        $this->get('/d/'.$template->slug)->assertNotFound();
        $this->put('/d/'.$template->slug)->assertNotFound();
        $this->assertDatabaseCount('submissions', 0);
    }

    #[Test]
    public function archived_templates_cannot_be_started_even_if_a_public_link_was_enabled(): void
    {
        $template = Template::factory()->create(['shared_link' => true, 'archived_at' => now()]);
        $this->get('/d/'.$template->slug)->assertNotFound();
        $this->put('/d/'.$template->slug)->assertNotFound();
    }

    #[Test]
    public function malformed_signer_emails_do_not_leave_partially_created_submissions(): void
    {
        $template = Template::factory()->create(['shared_link' => true]);
        $this->put('/d/'.$template->slug, [
            'submitters' => [['email' => 'invalid-not-email']],
        ])->assertSessionHasErrors('submitters.0.email');
        $this->assertDatabaseCount('submissions', 0);
    }
}
