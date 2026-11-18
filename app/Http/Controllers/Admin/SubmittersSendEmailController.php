<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendSubmitterInvitationJob;
use App\Models\Submitter;
use Illuminate\Http\Request;

class SubmittersSendEmailController extends Controller
{
    public function store(Request $request, string $id)
    {
        $user = $request->user();
        $submitter = Submitter::whereHas('submission', fn ($q) => $q->forAccount($user->account_id))
            ->findOrFail($id);

        if ($submitter->completed_at || $submitter->declined_at) {
            return back()->with('error', 'Cannot resend to a completed or declined submitter.');
        }

        $submitter->update(['sent_at' => now()]);

        $submission = $submitter->submission()->with('template', 'createdByUser')->first();
        $templateName = $submission->template?->name ?? 'Document';
        $senderName = $submission->createdByUser?->full_name ?: ($submission->createdByUser?->email ?? config('app.name', 'Vouch'));

        SendSubmitterInvitationJob::dispatch($submitter->id, $senderName, $templateName);

        return back()->with('success', 'Invitation email resent.');
    }
}
