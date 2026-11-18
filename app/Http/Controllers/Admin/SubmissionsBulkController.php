<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Submissions\CreateSubmissionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkSubmissionRequest;
use App\Models\Template;

class SubmissionsBulkController extends Controller
{
    public function store(BulkSubmissionRequest $request, string $templateId, CreateSubmissionAction $action)
    {
        $template    = Template::forAccount($request->user()->account_id)->findOrFail($templateId);
        $recipients  = $request->validRecipients();
        $created     = 0;

        foreach ($recipients as $recipientData) {
            $action->execute(
                template:        $template,
                creator:         $request->user(),
                submitterInputs: [$recipientData],
                source:          'bulk_invite',
            );
            $created++;
        }

        return back()->with('success', "{$created} invitation(s) sent.");
    }
}
