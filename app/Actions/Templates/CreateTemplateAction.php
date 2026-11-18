<?php

namespace App\Actions\Templates;

use App\Jobs\DispatchWebhookEventJob;
use App\Jobs\ReindexSearchEntryJob;
use App\Models\Template;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Support\Str;

class CreateTemplateAction
{
    public function execute(User $author, string $name, ?int $folderId = null, string $source = 'builder'): Template
    {
        if (! $folderId) {
            $folder = TemplateFolder::firstOrCreate(
                ['account_id' => $author->account_id, 'name' => 'Default'],
                ['author_id' => $author->id],
            );
            $folderId = $folder->id;
        }

        // Folder ownership is independent of its ID; reject cross-tenant IDs.
        abort_unless(
            TemplateFolder::whereKey($folderId)->where('account_id', $author->account_id)->exists(),
            403,
            'Folder belongs to another account.'
        );

        $template = Template::create([
            'slug' => Str::random(14),
            'name' => $name,
            'schema' => [],
            'fields' => [],
            'submitters' => [['name' => 'First Party', 'uuid' => (string) Str::uuid()]],
            'author_id' => $author->id,
            'account_id' => $author->account_id,
            'source' => $source,
            'folder_id' => $folderId,
            'preferences' => [],
            'shared_link' => false,
        ]);

        DispatchWebhookEventJob::dispatch('template.created', $author->account_id);
        ReindexSearchEntryJob::dispatch('Template', $template->id);

        return $template;
    }
}
