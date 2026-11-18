<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendSubmitterInvitationJob;
use App\Models\Submitter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubmittersResubmitController extends Controller
{
    public function store(Request $request, string $id)
    {
        $user = $request->user();
        $submitter = Submitter::whereHas('submission', fn ($q) => $q->forAccount($user->account_id))
            ->findOrFail($id);

        // Reset the submitter so they can fill in the form again
        $submitter->update([
            'slug'         => Str::random(14),
            'completed_at' => null,
            'declined_at'  => null,
            'values'       => null,
            'opened_at'    => null,
            'sent_at'      => now(),
        ]);

        $submission = $submitter->submission()->with('template', 'createdByUser')->first();

        // If this was a completed submission, reset it too
        $submission->update(['archived_at' => null]);

        $templateName = $submission->template?->name ?? 'Document';
        $senderName = $submission->createdByUser?->full_name ?: ($submission->createdByUser?->email ?? config('app.name', 'Vouch'));

        if ($submitter->email) {
            SendSubmitterInvitationJob::dispatch($submitter->id, $senderName, $templateName);
        }

        return back()->with('success', 'Form has been reset. A new invitation link has been sent.');
    }
}
