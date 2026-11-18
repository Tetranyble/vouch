<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Account;
use App\Models\Template;
use App\Models\TemplateSharing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateSharingPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function explicit_account_shares_allow_read_and_only_write_shares_allow_edits(): void
    {
        $owner = User::factory()->create();
        $guest = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $owner->account_id, 'author_id' => $owner->id]);

        $this->assertFalse(Gate::forUser($guest)->allows('view', $template));
        $this->assertFalse(Gate::forUser($guest)->allows('update', $template));

        $share = TemplateSharing::factory()->create([
            'template_id' => $template->id,
            'account_id' => $guest->account_id,
            'ability' => 'read',
        ]);
        $this->assertTrue(Gate::forUser($guest)->allows('view', $template));
        $this->assertFalse(Gate::forUser($guest)->allows('update', $template));

        $share->update(['ability' => 'write']);
        $this->assertTrue(Gate::forUser($guest)->allows('update', $template));
        $this->assertFalse(Gate::forUser($guest)->allows('delete', $template));
    }
}
