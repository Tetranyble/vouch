<?php

namespace App\Actions\Templates;

use App\Jobs\ReindexSearchEntryJob;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Str;

class CloneTemplateAction
{
    public function execute(Template $source, User $clonedBy): Template
    {
        abort_unless($source->account_id === $clonedBy->account_id, 403, 'Template belongs to another account.');

        $clone = Template::create([
            'slug'        => Str::random(14),
            'name'        => $source->name . ' (Copy)',
            'schema'      => $source->schema,
            'fields'      => $source->fields,
            'submitters'  => $source->submitters,
            'documents'   => $source->documents,
            'author_id'   => $clonedBy->id,
            'account_id'  => $clonedBy->account_id,
            'folder_id'   => $source->folder_id,
            'source'      => 'clone',
            'preferences' => $source->preferences ?? [],
            'shared_link' => false,
        ]);

        ReindexSearchEntryJob::dispatch('Template', $clone->id);

        return $clone;
    }
}
